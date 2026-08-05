<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CreditScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Explorador de datos del owner: acceso de sólo lectura a todas las tablas
 * del sistema con filtros dinámicos, búsqueda, ordenamiento, totales y
 * exportación CSV. Cada entidad se define de forma declarativa (joins,
 * columnas, filtros) y el motor aplica todo genéricamente.
 */
class OwnerExploradorController extends Controller
{
    private const PER_PAGE_OPTIONS = [25, 50, 100, 250, 500];
    private const CSV_MAX_ROWS     = 50000;

    public function index(Request $request)
    {
        $entities = $this->entities();

        $tabla = $request->query('t', 'prestamos');
        if (!isset($entities[$tabla])) {
            $tabla = 'prestamos';
        }
        $cfg = $entities[$tabla];

        // Lista de admins para el filtro (incluye admins sombra de carteras financiadas,
        // porque sus préstamos/clientes existen bajo ese admin_id)
        $admins = User::where('puesto', 'admin')
            ->orderBy('nombre')->orderBy('usuario')->get()
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'label' => ($u->nombre ?: $u->usuario) . ($u->cartera_financiada_de ? ' (cartera financiada)' : ''),
            ])->values()->all();

        $query = ($cfg['query'])();

        // ── SELECT: todas las columnas declaradas ─────────────────────
        $selects = [];
        foreach ($cfg['columns'] as $key => $col) {
            $selects[] = DB::raw($col['sql'] . ' as `' . $key . '`');
        }
        $query->select($selects);

        // ── Búsqueda de texto libre ───────────────────────────────────
        $q = trim((string) $request->query('q', ''));
        if ($q !== '' && !empty($cfg['search'])) {
            $query->where(function ($w) use ($cfg, $q) {
                foreach ($cfg['search'] as $expr) {
                    $w->orWhere(DB::raw($expr), 'like', "%{$q}%");
                }
            });
        }

        // ── Filtros dinámicos ─────────────────────────────────────────
        $filtros = [];
        foreach ($cfg['filters'] as $f) {
            $p = 'f_' . $f['param'];
            switch ($f['type']) {
                case 'text':
                    $val = trim((string) $request->query($p, ''));
                    if ($val !== '') {
                        $query->where(DB::raw($f['sql']), 'like', "%{$val}%");
                    }
                    $f['value'] = $val;
                    break;

                case 'exact':
                    $val = trim((string) $request->query($p, ''));
                    if ($val !== '') {
                        $query->where(DB::raw($f['sql']), '=', $val);
                    }
                    $f['value'] = $val;
                    break;

                case 'select':
                    $val = (string) $request->query($p, '');
                    if ($val !== '') {
                        $query->where(DB::raw($f['sql']), '=', $val);
                    }
                    $f['value'] = $val;
                    break;

                case 'range':
                    $min = (string) $request->query($p . '_min', '');
                    $max = (string) $request->query($p . '_max', '');
                    if ($min !== '' && is_numeric($min)) {
                        $query->where(DB::raw($f['sql']), '>=', (float) $min);
                    }
                    if ($max !== '' && is_numeric($max)) {
                        $query->where(DB::raw($f['sql']), '<=', (float) $max);
                    }
                    $f['min'] = $min;
                    $f['max'] = $max;
                    break;

                case 'daterange':
                    $de = (string) $request->query($p . '_de', '');
                    $a  = (string) $request->query($p . '_a', '');
                    if ($de !== '') {
                        $query->whereRaw('DATE(' . $f['sql'] . ') >= ?', [$de]);
                    }
                    if ($a !== '') {
                        $query->whereRaw('DATE(' . $f['sql'] . ') <= ?', [$a]);
                    }
                    $f['de'] = $de;
                    $f['a']  = $a;
                    break;
            }
            $filtros[] = $f;
        }

        // ── Ordenamiento (whitelist de columnas declaradas) ───────────
        [$defSort, $defDir] = $cfg['default_sort'];
        $sort = $request->query('sort', $defSort);
        if (!isset($cfg['columns'][$sort])) {
            $sort = $defSort;
        }
        $dir = strtolower((string) $request->query('dir', $defDir)) === 'asc' ? 'asc' : 'desc';
        $query->orderByRaw($cfg['columns'][$sort]['sql'] . ' ' . $dir);
        if ($sort !== 'id' && isset($cfg['columns']['id'])) {
            $query->orderByRaw($cfg['columns']['id']['sql'] . ' desc');
        }

        // ── Totales del conjunto filtrado ─────────────────────────────
        $aggQ = clone $query;
        $aggQ->orders  = null;
        $aggQ->columns = null;
        $aggSelect = ['COUNT(*) as _n'];
        foreach ($cfg['sums'] ?? [] as $key) {
            $aggSelect[] = 'SUM(' . $cfg['columns'][$key]['sql'] . ') as `' . $key . '`';
        }
        $tot = $aggQ->selectRaw(implode(', ', $aggSelect))->first();

        // ── Exportación CSV ───────────────────────────────────────────
        if ($request->query('export') === 'csv') {
            return $this->exportCsv($tabla, $cfg, $query);
        }

        // ── Paginación ────────────────────────────────────────────────
        $pp = (int) $request->query('pp', 50);
        if (!in_array($pp, self::PER_PAGE_OPTIONS, true)) {
            $pp = 50;
        }
        $rows = $query->paginate($pp)->withQueryString();

        return view('owner.explorador', compact(
            'entities', 'tabla', 'cfg', 'admins', 'filtros',
            'rows', 'tot', 'q', 'sort', 'dir', 'pp'
        ) + ['perPageOptions' => self::PER_PAGE_OPTIONS]);
    }

    private function exportCsv(string $tabla, array $cfg, $query)
    {
        $filename = 'explorador_' . $tabla . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($cfg, $query) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel abra el UTF-8 correctamente
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($c) => $c['label'], $cfg['columns']));

            $keys = array_keys($cfg['columns']);
            foreach ($query->limit(self::CSV_MAX_ROWS)->cursor() as $row) {
                $line = [];
                foreach ($keys as $k) {
                    $v = $row->$k;
                    if (($cfg['columns'][$k]['type'] ?? '') === 'bool') {
                        $v = $v ? 'Sí' : 'No';
                    }
                    $line[] = $v;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Definición declarativa de cada tabla explorable.
     * columns: key => [label, sql (expresión SELECT/orden/filtro), type]
     *   type: int | text | money | pct | date | datetime | bool | badge
     * search: expresiones SQL para el buscador de texto libre (OR LIKE)
     * filters: select (options lista u asociativo, o 'admins') | range | daterange
     * sums: keys de columnas money a totalizar sobre el conjunto filtrado
     */
    private function entities(): array
    {
        $adminName = "COALESCE(NULLIF(a.nombre,''), a.usuario)";
        $boolOpts  = ['1' => 'Sí', '0' => 'No'];

        return [
            'prestamos' => [
                'label' => 'Préstamos',
                'query' => fn () => DB::table('prestamos as p')
                    ->leftJoin('clientes as c', 'c.id', '=', 'p.cliente_id')
                    ->leftJoin('users as a', 'a.id', '=', 'p.admin_id')
                    ->leftJoin('empleados as prom', 'prom.id', '=', 'p.promotor_id')
                    ->leftJoin('empleados as cob', 'cob.id', '=', 'p.cobrador_id'),
                'columns' => [
                    'id'                => ['label' => 'ID',              'sql' => 'p.id',                'type' => 'int'],
                    'admin'             => ['label' => 'Admin',           'sql' => $adminName,            'type' => 'text'],
                    'cliente'           => ['label' => 'Cliente',         'sql' => 'c.nombre',            'type' => 'text'],
                    'estatus'           => ['label' => 'Estatus',         'sql' => 'p.estatus',           'type' => 'badge'],
                    'monto'             => ['label' => 'Monto acordado',  'sql' => 'p.monto',             'type' => 'money'],
                    'monto_entregado'   => ['label' => 'Entregado',       'sql' => 'p.monto_entregado',   'type' => 'money'],
                    'saldo_actual'      => ['label' => 'Saldo actual',    'sql' => 'p.saldo_actual',      'type' => 'money'],
                    'interes_acumulado' => ['label' => 'Mora acumulada',  'sql' => 'p.interes_acumulado', 'type' => 'money'],
                    'cuota'             => ['label' => 'Cuota',           'sql' => 'p.cuota',             'type' => 'money'],
                    'num_pagos'         => ['label' => '# Pagos',         'sql' => 'p.num_pagos',         'type' => 'int'],
                    'frecuencia'        => ['label' => 'Frecuencia',      'sql' => 'p.frecuencia',        'type' => 'text'],
                    'tasa_diaria'       => ['label' => 'Tasa diaria',     'sql' => 'p.tasa_diaria',       'type' => 'pct'],
                    'interes_diario'    => ['label' => 'Mora diaria',     'sql' => 'p.interes_diario',    'type' => 'money'],
                    'promotor'          => ['label' => 'Promotor',        'sql' => 'prom.nombre',         'type' => 'text'],
                    'cobrador'          => ['label' => 'Cobrador',        'sql' => 'cob.nombre',          'type' => 'text'],
                    'forma_entrega'     => ['label' => 'Forma entrega',   'sql' => 'p.forma_entrega',     'type' => 'text'],
                    'fecha_inicio'      => ['label' => 'Fecha inicio',    'sql' => 'p.fecha_inicio',      'type' => 'date'],
                    'fecha_entrega'     => ['label' => 'Fecha entrega',   'sql' => 'p.fecha_entrega',     'type' => 'date'],
                    'fecha_fin'         => ['label' => 'Fecha fin',       'sql' => 'p.fecha_fin',         'type' => 'date'],
                    'payment_hold'      => ['label' => 'Hold',            'sql' => 'p.payment_hold',      'type' => 'bool'],
                    'interes_activo'    => ['label' => 'Interés activo',  'sql' => 'p.interes_activo',    'type' => 'bool'],
                    'mora_activa'       => ['label' => 'Mora activa',     'sql' => 'p.interes_mora_activo', 'type' => 'bool'],
                    'descanso_domingos' => ['label' => 'Descansa dom.',   'sql' => 'p.descanso_domingos', 'type' => 'bool'],
                    'refinanciado_por'  => ['label' => 'Refin. por (ID)', 'sql' => 'p.refinanciado_por',  'type' => 'int'],
                    'created_at'        => ['label' => 'Creado',          'sql' => 'p.created_at',        'type' => 'datetime'],
                ],
                'search'  => ['c.nombre', 'c.curp', 'c.celular', 'p.id'],
                'filters' => [
                    ['param' => 'admin',    'label' => 'Administrador',  'type' => 'select',    'sql' => 'p.admin_id',      'options' => 'admins'],
                    ['param' => 'estatus',  'label' => 'Estatus',        'type' => 'select',    'sql' => 'p.estatus',       'options' => ['Pendiente', 'Activo', 'Atrasado', 'Finalizado', 'Cancelado', 'Retirado', 'Refinanciado']],
                    ['param' => 'frec',     'label' => 'Frecuencia',     'type' => 'select',    'sql' => 'p.frecuencia',    'options' => ['Diario', 'Semanal', 'Quincenal', 'Mensual']],
                    ['param' => 'forma',    'label' => 'Forma entrega',  'type' => 'select',    'sql' => 'p.forma_entrega', 'options' => ['efectivo', 'transferencia', 'refinanciamiento']],
                    ['param' => 'hold',     'label' => 'Payment hold',   'type' => 'select',    'sql' => 'p.payment_hold',  'options' => $boolOpts],
                    ['param' => 'monto',    'label' => 'Monto',          'type' => 'range',     'sql' => 'p.monto'],
                    ['param' => 'saldo',    'label' => 'Saldo actual',   'type' => 'range',     'sql' => 'p.saldo_actual'],
                    ['param' => 'finicio',  'label' => 'Fecha inicio',   'type' => 'daterange', 'sql' => 'p.fecha_inicio'],
                    ['param' => 'fentrega', 'label' => 'Fecha entrega',  'type' => 'daterange', 'sql' => 'p.fecha_entrega'],
                ],
                'sums'         => ['monto', 'monto_entregado', 'saldo_actual', 'interes_acumulado'],
                'default_sort' => ['id', 'desc'],
            ],

            'pagos' => [
                'label' => 'Pagos',
                'query' => fn () => DB::table('pagos as g')
                    ->leftJoin('prestamos as p', 'p.id', '=', 'g.prestamo_id')
                    ->leftJoin('clientes as c', 'c.id', '=', 'p.cliente_id')
                    ->leftJoin('users as a', 'a.id', '=', 'p.admin_id')
                    ->leftJoin('empleados as cob', 'cob.id', '=', 'g.cobrador_id'),
                'columns' => [
                    'id'               => ['label' => 'ID',            'sql' => 'g.id',               'type' => 'int'],
                    'admin'            => ['label' => 'Admin',         'sql' => $adminName,           'type' => 'text'],
                    'cliente'          => ['label' => 'Cliente',       'sql' => 'c.nombre',           'type' => 'text'],
                    'prestamo_id'      => ['label' => 'Préstamo',      'sql' => 'g.prestamo_id',      'type' => 'int'],
                    'numero_pago'      => ['label' => '# Cuota',       'sql' => 'g.numero_pago',      'type' => 'int'],
                    'estatus'          => ['label' => 'Estatus',       'sql' => 'g.estatus',          'type' => 'badge'],
                    'tipo_pago'        => ['label' => 'Tipo',          'sql' => 'g.tipo_pago',        'type' => 'badge'],
                    'monto_cuota'      => ['label' => 'Cuota',         'sql' => 'g.monto_cuota',      'type' => 'money'],
                    'capital'          => ['label' => 'Capital',       'sql' => 'g.capital',          'type' => 'money'],
                    'interes'          => ['label' => 'Interés',       'sql' => 'g.interes',          'type' => 'money'],
                    'monto_cobrado'    => ['label' => 'Cobrado',       'sql' => 'g.monto_cobrado',    'type' => 'money'],
                    'saldo_restante'   => ['label' => 'Saldo rest.',   'sql' => 'g.saldo_restante',   'type' => 'money'],
                    'tipo_cobro'       => ['label' => 'Tipo cobro',    'sql' => 'g.tipo_cobro',       'type' => 'text'],
                    'fecha_programada' => ['label' => 'Programada',    'sql' => 'g.fecha_programada', 'type' => 'date'],
                    'fecha_pago'       => ['label' => 'Fecha pago',    'sql' => 'g.fecha_pago',       'type' => 'date'],
                    'cobrador'         => ['label' => 'Cobrador',      'sql' => 'cob.nombre',         'type' => 'text'],
                    'nota_cobro'       => ['label' => 'Nota',          'sql' => 'g.nota_cobro',       'type' => 'text'],
                    'created_at'       => ['label' => 'Creado',        'sql' => 'g.created_at',       'type' => 'datetime'],
                ],
                'search'  => ['c.nombre', 'g.prestamo_id', 'g.nota_cobro'],
                'filters' => [
                    ['param' => 'admin',   'label' => 'Administrador',    'type' => 'select',    'sql' => 'p.admin_id',      'options' => 'admins'],
                    ['param' => 'estatus', 'label' => 'Estatus',          'type' => 'select',    'sql' => 'g.estatus',       'options' => ['Pendiente', 'Pagado', 'Parcial', 'Atrasado']],
                    ['param' => 'tipo',    'label' => 'Tipo de pago',     'type' => 'select',    'sql' => 'g.tipo_pago',     'options' => ['plan', 'extra', 'agendado', 'congelado', 'extraordinario', 'liquidado']],
                    ['param' => 'tcobro',  'label' => 'Tipo de cobro',    'type' => 'select',    'sql' => 'g.tipo_cobro',    'options' => ['completo', 'parcial']],
                    ['param' => 'cobrado', 'label' => 'Monto cobrado',    'type' => 'range',     'sql' => 'g.monto_cobrado'],
                    ['param' => 'fprog',   'label' => 'Fecha programada', 'type' => 'daterange', 'sql' => 'g.fecha_programada'],
                    ['param' => 'fpago',   'label' => 'Fecha de pago',    'type' => 'daterange', 'sql' => 'g.fecha_pago'],
                ],
                'sums'         => ['monto_cuota', 'capital', 'interes', 'monto_cobrado'],
                'default_sort' => ['id', 'desc'],
            ],

            'clientes' => [
                'label' => 'Clientes',
                'query' => fn () => DB::table('clientes as c')
                    ->leftJoin('users as a', 'a.id', '=', 'c.admin_id')
                    ->leftJoin('empleados as prom', 'prom.id', '=', 'c.promotor_id')
                    ->leftJoinSub(CreditScore::subconsulta(), CreditScore::ALIAS, function ($join) {
                        $join->on(CreditScore::ALIAS . '.cliente_id', '=', 'c.id');
                    }),
                'columns' => [
                    'id'         => ['label' => 'ID',        'sql' => 'c.id',         'type' => 'int'],
                    'admin'      => ['label' => 'Admin',     'sql' => $adminName,     'type' => 'text'],
                    'nombre'     => ['label' => 'Nombre',    'sql' => 'c.nombre',     'type' => 'text'],
                    'score'      => ['label' => 'Credit Score', 'sql' => CreditScore::expresionSql(), 'type' => 'credit_score'],
                    'celular'    => ['label' => 'Celular',   'sql' => 'c.celular',    'type' => 'text'],
                    'email'      => ['label' => 'Email',     'sql' => 'c.email',      'type' => 'text'],
                    'curp'       => ['label' => 'CURP',      'sql' => 'c.curp',       'type' => 'text'],
                    'ocupacion'  => ['label' => 'Ocupación', 'sql' => 'c.ocupacion',  'type' => 'text'],
                    'direccion'  => ['label' => 'Dirección', 'sql' => 'c.direccion',  'type' => 'text'],
                    'promotor'   => ['label' => 'Promotor',  'sql' => 'prom.nombre',  'type' => 'text'],
                    'activo'     => ['label' => 'Activo',    'sql' => 'c.activo',     'type' => 'bool'],
                    'created_at' => ['label' => 'Alta',      'sql' => 'c.created_at', 'type' => 'datetime'],
                ],
                'search'  => [
                    'c.id', 'c.nombre', 'c.celular', 'c.fijo', 'c.curp', 'c.email', 'c.direccion',
                    'prom.nombre', 'c.contacto_nombre', 'c.contacto_telefono', 'c.contacto_direccion',
                    'c.contacto_nombre2', 'c.contacto_telefono2', 'c.contacto_direccion2',
                ],
                'filters' => [
                    ['param' => 'id',          'label' => 'ID',                    'type' => 'exact',     'sql' => 'c.id',          'input' => 'number'],
                    ['param' => 'admin',       'label' => 'Administrador',         'type' => 'select',    'sql' => 'c.admin_id',    'options' => 'admins'],
                    ['param' => 'nombre',      'label' => 'Nombre',                'type' => 'text',      'sql' => 'c.nombre'],
                    ['param' => 'score',       'label' => 'Credit Score',          'type' => 'range',     'sql' => CreditScore::expresionSql(), 'suffix' => '', 'min_attr' => CreditScore::MIN, 'max_attr' => CreditScore::MAX],
                    ['param' => 'celular',     'label' => 'Celular',               'type' => 'text',      'sql' => 'c.celular'],
                    ['param' => 'fijo',        'label' => 'Teléfono fijo',         'type' => 'text',      'sql' => 'c.fijo'],
                    ['param' => 'email',       'label' => 'Email',                 'type' => 'text',      'sql' => 'c.email'],
                    ['param' => 'promotor',    'label' => 'Promotor',              'type' => 'text',      'sql' => 'prom.nombre'],
                    ['param' => 'activo',      'label' => 'Activo',                'type' => 'select',    'sql' => 'c.activo',     'options' => $boolOpts],
                    ['param' => 'contacto1',   'label' => 'Contacto 1 · Nombre',   'type' => 'text',      'sql' => 'c.contacto_nombre'],
                    ['param' => 'telefono1',   'label' => 'Contacto 1 · Teléfono', 'type' => 'text',      'sql' => 'c.contacto_telefono'],
                    ['param' => 'direccion1',  'label' => 'Contacto 1 · Dirección','type' => 'text',      'sql' => 'c.contacto_direccion'],
                    ['param' => 'contacto2',   'label' => 'Contacto 2 · Nombre',   'type' => 'text',      'sql' => 'c.contacto_nombre2'],
                    ['param' => 'telefono2',   'label' => 'Contacto 2 · Teléfono', 'type' => 'text',      'sql' => 'c.contacto_telefono2'],
                    ['param' => 'direccion2',  'label' => 'Contacto 2 · Dirección','type' => 'text',      'sql' => 'c.contacto_direccion2'],
                    ['param' => 'alta',        'label' => 'Fecha de alta',         'type' => 'daterange', 'sql' => 'c.created_at'],
                    ['param' => 'actualizado', 'label' => 'Última actualización',  'type' => 'daterange', 'sql' => 'c.updated_at'],
                ],
                'sums'         => [],
                'default_sort' => ['id', 'desc'],
            ],

            'empleados' => [
                'label' => 'Empleados',
                'query' => fn () => DB::table('empleados as e')
                    ->leftJoin('users as a', 'a.id', '=', 'e.admin_id')
                    ->leftJoin('users as u', 'u.id', '=', 'e.usuario_id')
                    ->leftJoin('empleados as pr', 'pr.id', '=', 'e.promotor_id'),
                'columns' => [
                    'id'               => ['label' => 'ID',         'sql' => 'e.id',               'type' => 'int'],
                    'admin'            => ['label' => 'Admin',      'sql' => $adminName,           'type' => 'text'],
                    'nombre'           => ['label' => 'Nombre',     'sql' => 'e.nombre',           'type' => 'text'],
                    'usuario'          => ['label' => 'Usuario',    'sql' => 'u.usuario',          'type' => 'text'],
                    'puesto'           => ['label' => 'Puesto',     'sql' => 'e.puesto',           'type' => 'badge'],
                    'rango'            => ['label' => 'Rango',      'sql' => 'e.rango',            'type' => 'text'],
                    'promotor'         => ['label' => 'Promotor',   'sql' => 'pr.nombre',          'type' => 'text'],
                    'celular'          => ['label' => 'Celular',    'sql' => 'e.celular',          'type' => 'text'],
                    'email'            => ['label' => 'Email',      'sql' => 'e.email',            'type' => 'text'],
                    'capacidad_maxima' => ['label' => 'Capacidad',  'sql' => 'e.capacidad_maxima', 'type' => 'money'],
                    'monto_ocupado'    => ['label' => 'Ocupado',    'sql' => 'e.monto_ocupado',    'type' => 'money'],
                    'activo'           => ['label' => 'Activo',     'sql' => 'e.activo',           'type' => 'bool'],
                    'created_at'       => ['label' => 'Alta',       'sql' => 'e.created_at',       'type' => 'datetime'],
                ],
                'search'  => ['e.nombre', 'e.celular', 'e.email', 'u.usuario'],
                'filters' => [
                    ['param' => 'admin',  'label' => 'Administrador', 'type' => 'select', 'sql' => 'e.admin_id', 'options' => 'admins'],
                    ['param' => 'puesto', 'label' => 'Puesto',        'type' => 'select', 'sql' => 'e.puesto',   'options' => ['admin', 'promo', 'collector', 'desembolso']],
                    ['param' => 'rango',  'label' => 'Rango',         'type' => 'select', 'sql' => 'e.rango',    'options' => ['Bronce', 'Plata', 'Oro', 'Platino', 'Diamante']],
                    ['param' => 'activo', 'label' => 'Activo',        'type' => 'select', 'sql' => 'e.activo',   'options' => $boolOpts],
                ],
                'sums'         => ['monto_ocupado'],
                'default_sort' => ['id', 'desc'],
            ],

            'usuarios' => [
                'label' => 'Usuarios',
                'query' => fn () => DB::table('users as u')
                    ->leftJoin('users as cf', 'cf.id', '=', 'u.cartera_financiada_de'),
                'columns' => [
                    'id'          => ['label' => 'ID',           'sql' => 'u.id',          'type' => 'int'],
                    'usuario'     => ['label' => 'Usuario',      'sql' => 'u.usuario',     'type' => 'text'],
                    'nombre'      => ['label' => 'Nombre',       'sql' => 'u.nombre',      'type' => 'text'],
                    'alias'       => ['label' => 'Alias',        'sql' => 'u.alias',       'type' => 'text'],
                    'puesto'      => ['label' => 'Puesto',       'sql' => 'u.puesto',      'type' => 'badge'],
                    'celular'     => ['label' => 'Celular',      'sql' => 'u.celular',     'type' => 'text'],
                    'presupuesto' => ['label' => 'Presupuesto',  'sql' => 'u.presupuesto', 'type' => 'money'],
                    'cartera_de'  => ['label' => 'Cartera fin. de', 'sql' => "COALESCE(NULLIF(cf.nombre,''), cf.usuario)", 'type' => 'text'],
                    'activo'      => ['label' => 'Activo',       'sql' => 'u.activo',      'type' => 'bool'],
                    'created_at'  => ['label' => 'Creado',       'sql' => 'u.created_at',  'type' => 'datetime'],
                ],
                'search'  => ['u.usuario', 'u.nombre', 'u.alias', 'u.celular'],
                'filters' => [
                    ['param' => 'puesto', 'label' => 'Puesto', 'type' => 'select', 'sql' => 'u.puesto', 'options' => ['owner', 'supervisor', 'admin', 'promo', 'collector', 'desembolso']],
                    ['param' => 'activo', 'label' => 'Activo', 'type' => 'select', 'sql' => 'u.activo', 'options' => $boolOpts],
                ],
                'sums'         => ['presupuesto'],
                'default_sort' => ['id', 'desc'],
            ],

            'financiamientos' => [
                'label' => 'Financiamientos',
                'query' => fn () => DB::table('financiamientos as f')
                    ->leftJoin('users as a', 'a.id', '=', 'f.admin_id'),
                'columns' => [
                    'id'              => ['label' => 'ID',           'sql' => 'f.id',              'type' => 'int'],
                    'admin'           => ['label' => 'Admin',        'sql' => $adminName,          'type' => 'text'],
                    'capital_actual'  => ['label' => 'Capital',      'sql' => 'f.capital_actual',  'type' => 'money'],
                    'rendimiento_pct' => ['label' => 'Rendimiento',  'sql' => 'f.rendimiento_pct', 'type' => 'pct'],
                    'frecuencia'      => ['label' => 'Frecuencia',   'sql' => 'f.frecuencia',      'type' => 'text'],
                    'plazo_meses'     => ['label' => 'Plazo (meses)','sql' => 'f.plazo_meses',     'type' => 'int'],
                    'fecha_inicio'    => ['label' => 'Inicio',       'sql' => 'f.fecha_inicio',    'type' => 'date'],
                    'estatus'         => ['label' => 'Estatus',      'sql' => 'f.estatus',         'type' => 'badge'],
                    'notas'           => ['label' => 'Notas',        'sql' => 'f.notas',           'type' => 'text'],
                    'created_at'      => ['label' => 'Creado',       'sql' => 'f.created_at',      'type' => 'datetime'],
                ],
                'search'  => ['a.nombre', 'a.usuario', 'f.notas'],
                'filters' => [
                    ['param' => 'admin',   'label' => 'Administrador', 'type' => 'select', 'sql' => 'f.admin_id',   'options' => 'admins'],
                    ['param' => 'estatus', 'label' => 'Estatus',       'type' => 'select', 'sql' => 'f.estatus',    'options' => ['Activo', 'Finalizado']],
                    ['param' => 'frec',    'label' => 'Frecuencia',    'type' => 'select', 'sql' => 'f.frecuencia', 'options' => ['semanal', 'mensual']],
                ],
                'sums'         => ['capital_actual'],
                'default_sort' => ['id', 'desc'],
            ],

            'inversores' => [
                'label' => 'Inversores',
                'query' => fn () => DB::table('financiamiento_inversores as i')
                    ->leftJoin('financiamientos as f', 'f.id', '=', 'i.financiamiento_id')
                    ->leftJoin('users as a', 'a.id', '=', 'f.admin_id'),
                'columns' => [
                    'id'               => ['label' => 'ID',            'sql' => 'i.id',                'type' => 'int'],
                    'financiamiento'   => ['label' => 'Financ.',       'sql' => 'i.financiamiento_id', 'type' => 'int'],
                    'admin'            => ['label' => 'Admin',         'sql' => $adminName,            'type' => 'text'],
                    'nombre'           => ['label' => 'Inversor',      'sql' => 'i.nombre',            'type' => 'text'],
                    'es_owner'         => ['label' => 'Es owner',      'sql' => 'i.es_owner',          'type' => 'bool'],
                    'aporte'           => ['label' => 'Aporte',        'sql' => 'i.aporte',            'type' => 'money'],
                    'pct_retorno'      => ['label' => '% Retorno',     'sql' => 'i.pct_retorno',       'type' => 'pct'],
                    'retorno_mensual'  => ['label' => 'Retorno mens.', 'sql' => 'i.retorno_mensual',   'type' => 'money'],
                    'fecha_ingreso'    => ['label' => 'Ingreso',       'sql' => 'i.fecha_ingreso',     'type' => 'date'],
                    'fecha_limite'     => ['label' => 'Límite',        'sql' => 'i.fecha_limite',      'type' => 'date'],
                    'estatus'          => ['label' => 'Estatus',       'sql' => 'i.estatus',           'type' => 'badge'],
                    'fecha_salida'     => ['label' => 'Salida',        'sql' => 'i.fecha_salida',      'type' => 'date'],
                ],
                'search'  => ['i.nombre'],
                'filters' => [
                    ['param' => 'admin',   'label' => 'Administrador', 'type' => 'select', 'sql' => 'f.admin_id', 'options' => 'admins'],
                    ['param' => 'estatus', 'label' => 'Estatus',       'type' => 'select', 'sql' => 'i.estatus',  'options' => ['Activo', 'Retirado', 'Transferido']],
                    ['param' => 'owner',   'label' => 'Es owner',      'type' => 'select', 'sql' => 'i.es_owner', 'options' => $boolOpts],
                ],
                'sums'         => ['aporte', 'retorno_mensual'],
                'default_sort' => ['id', 'desc'],
            ],

            'movimientos' => [
                'label' => 'Movimientos fin.',
                'query' => fn () => DB::table('financiamiento_movimientos as m')
                    ->leftJoin('financiamientos as f', 'f.id', '=', 'm.financiamiento_id')
                    ->leftJoin('users as a', 'a.id', '=', 'f.admin_id')
                    ->leftJoin('financiamiento_inversores as i', 'i.id', '=', 'm.inversor_id')
                    ->leftJoin('users as reg', 'reg.id', '=', 'm.registrado_por'),
                'columns' => [
                    'id'                => ['label' => 'ID',          'sql' => 'm.id',                'type' => 'int'],
                    'admin'             => ['label' => 'Admin',       'sql' => $adminName,            'type' => 'text'],
                    'financiamiento'    => ['label' => 'Financ.',     'sql' => 'm.financiamiento_id', 'type' => 'int'],
                    'tipo'              => ['label' => 'Tipo',        'sql' => 'm.tipo',              'type' => 'badge'],
                    'periodo'           => ['label' => 'Periodo',     'sql' => 'm.periodo',           'type' => 'int'],
                    'inversor'          => ['label' => 'Inversor',    'sql' => 'i.nombre',            'type' => 'text'],
                    'monto'             => ['label' => 'Monto',       'sql' => 'm.monto',             'type' => 'money'],
                    'monto_reinversion' => ['label' => 'Reinversión', 'sql' => 'm.monto_reinversion', 'type' => 'money'],
                    'monto_retornado'   => ['label' => 'Retornado',   'sql' => 'm.monto_retornado',   'type' => 'money'],
                    'capitalizado'      => ['label' => 'Capitalizado','sql' => 'm.capitalizado',      'type' => 'bool'],
                    'fecha'             => ['label' => 'Fecha',       'sql' => 'm.fecha',             'type' => 'date'],
                    'nota'              => ['label' => 'Nota',        'sql' => 'm.nota',              'type' => 'text'],
                    'registrado_por'    => ['label' => 'Registró',    'sql' => "COALESCE(NULLIF(reg.nombre,''), reg.usuario)", 'type' => 'text'],
                    'created_at'        => ['label' => 'Creado',      'sql' => 'm.created_at',        'type' => 'datetime'],
                ],
                'search'  => ['m.nota', 'i.nombre'],
                'filters' => [
                    ['param' => 'admin', 'label' => 'Administrador', 'type' => 'select',    'sql' => 'f.admin_id', 'options' => 'admins'],
                    ['param' => 'tipo',  'label' => 'Tipo',          'type' => 'select',    'sql' => 'm.tipo',     'options' => ['rendimiento', 'aporte', 'retiro', 'salida_inversor', 'transferencia_owner']],
                    ['param' => 'fecha', 'label' => 'Fecha',         'type' => 'daterange', 'sql' => 'm.fecha'],
                    ['param' => 'monto', 'label' => 'Monto',         'type' => 'range',     'sql' => 'm.monto'],
                ],
                'sums'         => ['monto', 'monto_reinversion', 'monto_retornado'],
                'default_sort' => ['id', 'desc'],
            ],

            'actividad' => [
                'label' => 'Actividad',
                'query' => fn () => DB::table('prestamo_actividad as act')
                    ->leftJoin('prestamos as p', 'p.id', '=', 'act.prestamo_id')
                    ->leftJoin('clientes as c', 'c.id', '=', 'p.cliente_id')
                    ->leftJoin('users as a', 'a.id', '=', 'p.admin_id')
                    ->leftJoin('users as u', 'u.id', '=', 'act.user_id'),
                'columns' => [
                    'id'          => ['label' => 'ID',          'sql' => 'act.id',          'type' => 'int'],
                    'admin'       => ['label' => 'Admin',       'sql' => $adminName,        'type' => 'text'],
                    'prestamo_id' => ['label' => 'Préstamo',    'sql' => 'act.prestamo_id', 'type' => 'int'],
                    'cliente'     => ['label' => 'Cliente',     'sql' => 'c.nombre',        'type' => 'text'],
                    'tipo'        => ['label' => 'Tipo',        'sql' => 'act.tipo',        'type' => 'badge'],
                    'descripcion' => ['label' => 'Descripción', 'sql' => 'act.descripcion', 'type' => 'text'],
                    'usuario'     => ['label' => 'Usuario',     'sql' => "COALESCE(NULLIF(u.nombre,''), u.usuario)", 'type' => 'text'],
                    'created_at'  => ['label' => 'Fecha',       'sql' => 'act.created_at',  'type' => 'datetime'],
                ],
                'search'  => ['act.descripcion', 'c.nombre', 'act.prestamo_id'],
                'filters' => [
                    ['param' => 'admin', 'label' => 'Administrador', 'type' => 'select',    'sql' => 'p.admin_id',    'options' => 'admins'],
                    ['param' => 'tipo',  'label' => 'Tipo',          'type' => 'select',    'sql' => 'act.tipo',      'options' => ['creado', 'pago', 'estatus', 'cobrador', 'mora', 'edicion', 'desembolso']],
                    ['param' => 'fecha', 'label' => 'Fecha',         'type' => 'daterange', 'sql' => 'act.created_at'],
                ],
                'sums'         => [],
                'default_sort' => ['id', 'desc'],
            ],

            'notas' => [
                'label' => 'Notas de admin',
                'query' => fn () => DB::table('admin_notas as n')
                    ->leftJoin('users as a', 'a.id', '=', 'n.admin_id'),
                'columns' => [
                    'id'         => ['label' => 'ID',        'sql' => 'n.id',         'type' => 'int'],
                    'admin'      => ['label' => 'Admin',     'sql' => $adminName,     'type' => 'text'],
                    'contenido'  => ['label' => 'Contenido', 'sql' => 'n.contenido',  'type' => 'text'],
                    'created_at' => ['label' => 'Fecha',     'sql' => 'n.created_at', 'type' => 'datetime'],
                ],
                'search'  => ['n.contenido'],
                'filters' => [
                    ['param' => 'admin', 'label' => 'Administrador', 'type' => 'select',    'sql' => 'n.admin_id',   'options' => 'admins'],
                    ['param' => 'fecha', 'label' => 'Fecha',         'type' => 'daterange', 'sql' => 'n.created_at'],
                ],
                'sums'         => [],
                'default_sort' => ['id', 'desc'],
            ],
        ];
    }
}
