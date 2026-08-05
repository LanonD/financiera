@extends('layouts.app')

@section('title', 'Explorador de datos')

@push('styles')
<style>
/* ── Header ─────────────────────────────────────────────── */
.exp-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px}
.exp-title{font-size:20px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.exp-subtitle{font-size:12px;color:var(--text3);margin-top:3px}

/* ── Tabs de entidad ────────────────────────────────────── */
.exp-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.exp-tab{padding:7px 14px;border-radius:99px;font-size:12px;font-weight:700;color:var(--text2);background:var(--card);border:1.5px solid var(--border);text-decoration:none;transition:all .12s;white-space:nowrap}
.exp-tab:hover{border-color:var(--accent);color:var(--accent)}
.exp-tab.active{background:var(--accent);border-color:var(--accent);color:#fff}

/* ── Barra de filtros ───────────────────────────────────── */
.exp-filters{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:14px}
.exp-frow{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.exp-frow + .exp-frow{margin-top:10px}
.exp-fgroup{display:flex;flex-direction:column;gap:4px}
.exp-flabel{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.exp-input,.exp-select{padding:7px 10px;background:#f9fafb;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s}
.exp-input:focus,.exp-select:focus{border-color:var(--accent)}
.exp-select{cursor:pointer}
.exp-search{min-width:230px;flex:1}
.exp-filter-text{width:170px}
.exp-num{width:95px}
.exp-date{width:130px}
.exp-range{display:flex;gap:4px;align-items:center}
.exp-range span{font-size:11px;color:var(--text3)}
.exp-btn{padding:8px 16px;border:none;border-radius:8px;font-size:12px;font-weight:700;font-family:var(--font);cursor:pointer;transition:opacity .12s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.exp-btn:hover{opacity:.85}
.exp-btn-primary{background:var(--accent);color:#fff}
.exp-btn-ghost{background:#f3f4f6;color:var(--text2)}
.exp-btn-csv{background:#f0fdf4;color:#059669;border:1.5px solid #a7f3d0}

/* ── Chips de totales ───────────────────────────────────── */
.exp-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
.exp-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:var(--card);border:1px solid var(--border);border-radius:99px;font-size:12px;color:var(--text2)}
.exp-chip b{color:var(--text);font-weight:800;font-variant-numeric:tabular-nums}
.exp-chip-n{background:var(--accent);border-color:var(--accent);color:#fff}
.exp-chip-n b{color:#fff}

/* ── Selector de columnas ───────────────────────────────── */
.exp-cols-wrap{position:relative;margin-left:auto}
.exp-cols-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:50;background:var(--card);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.12);padding:10px 12px;max-height:320px;overflow-y:auto;min-width:200px}
.exp-cols-menu.open{display:block}
.exp-cols-menu label{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text2);padding:4px 2px;cursor:pointer;white-space:nowrap}
.exp-cols-menu input{accent-color:var(--accent)}

/* ── Tabla ──────────────────────────────────────────────── */
.exp-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:auto;max-height:72vh}
.exp-table{width:100%;border-collapse:collapse;font-size:12px;white-space:nowrap}
.exp-table thead th{position:sticky;top:0;z-index:10;background:#f9fafb;border-bottom:1.5px solid var(--border);padding:0;text-align:left}
.exp-table thead th a{display:flex;align-items:center;gap:4px;padding:10px 12px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-decoration:none;transition:color .1s}
.exp-table thead th a:hover{color:var(--accent)}
.exp-table thead th.sorted a{color:var(--accent)}
.exp-table tbody td{padding:8px 12px;border-bottom:1px solid #f3f4f6;color:var(--text);font-variant-numeric:tabular-nums}
.exp-table tbody tr:hover{background:#f9fafb}
.exp-td-money{text-align:right;font-weight:600}
.exp-td-int{text-align:right}
.exp-td-null{color:#d1d5db}
.exp-td-text{max-width:260px;overflow:hidden;text-overflow:ellipsis}
.exp-pill{display:inline-flex;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap}
.exp-pill-green{background:#f0fdf4;color:#10b981}
.exp-pill-red{background:#fef2f2;color:#dc2626}
.exp-pill-blue{background:#eff6ff;color:#1d4ed8}
.exp-pill-yellow{background:#fefce8;color:#ca8a04}
.exp-pill-purple{background:#f5f3ff;color:#7c3aed}
.exp-pill-gray{background:#f3f4f6;color:#6b7280}
.exp-score{display:inline-flex;align-items:center;justify-content:center;min-width:42px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:800;font-variant-numeric:tabular-nums}
.exp-score-good{background:#dcfce7;color:#166534}
.exp-score-warn{background:#fef3c7;color:#92400e}
.exp-score-risk{background:#fee2e2;color:#991b1b}
.exp-empty{padding:50px 20px;text-align:center;color:var(--text3);font-size:13px}

/* ── Paginación ─────────────────────────────────────────── */
.exp-pager{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;flex-wrap:wrap}
.exp-pager-info{font-size:12px;color:var(--text3)}
.exp-pager-links{display:flex;gap:4px;flex-wrap:wrap}
.exp-page{padding:6px 11px;border-radius:7px;font-size:12px;font-weight:600;color:var(--text2);background:var(--card);border:1px solid var(--border);text-decoration:none;transition:all .12s}
.exp-page:hover{border-color:var(--accent);color:var(--accent)}
.exp-page.current{background:var(--accent);border-color:var(--accent);color:#fff}
.exp-page.disabled{opacity:.4;pointer-events:none}

@media(max-width:768px){
    .exp-search{min-width:150px}
    .exp-table-wrap{max-height:none}
}
</style>
@endpush

@section('content')
@php
    // ── Formateadores de celda ────────────────────────────────
    $pillColor = function ($v) {
        return match ($v) {
            'Activo', 'Pagado', 'pago', 'rendimiento', 'creado' => 'green',
            'Atrasado', 'retiro', 'mora', 'supervisor'          => 'red',
            'Finalizado', 'admin', 'aporte', 'desembolso', 'extra' => 'blue',
            'Pendiente', 'Parcial', 'agendado', 'salida_inversor', 'collector', 'cobrador' => 'yellow',
            'Refinanciado', 'Transferido', 'transferencia_owner', 'owner', 'extraordinario', 'edicion', 'estatus' => 'purple',
            default => 'gray',
        };
    };
    $sortLink = function ($key) use ($sort, $dir) {
        $newDir = ($sort === $key && $dir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $newDir, 'page' => null]);
    };
@endphp

<div class="exp-header">
    <div>
        <div class="exp-title">Explorador de datos</div>
        <div class="exp-subtitle">Toda la información del sistema, filtrable y exportable · sólo lectura</div>
    </div>
</div>

{{-- ── Tabs de entidad ─────────────────────────────────────── --}}
<div class="exp-tabs">
    @foreach($entities as $key => $ent)
        <a href="{{ route('owner.explorador', ['t' => $key]) }}" class="exp-tab {{ $tabla === $key ? 'active' : '' }}">{{ $ent['label'] }}</a>
    @endforeach
</div>

{{-- ── Filtros ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('owner.explorador') }}" class="exp-filters" id="expForm">
    <input type="hidden" name="t" value="{{ $tabla }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">

    <div class="exp-frow">
        <div class="exp-fgroup exp-search">
            <span class="exp-flabel">Buscar</span>
            <input type="text" name="q" value="{{ $q }}" class="exp-input" placeholder="Texto libre (nombre, CURP, celular, ID…)">
        </div>

        @foreach($filtros as $f)
            @if(in_array($f['type'], ['text', 'exact']))
                <div class="exp-fgroup">
                    <span class="exp-flabel">{{ $f['label'] }}</span>
                    <input
                        type="{{ $f['input'] ?? 'text' }}"
                        name="f_{{ $f['param'] }}"
                        value="{{ $f['value'] }}"
                        class="exp-input exp-filter-text"
                        placeholder="{{ $f['placeholder'] ?? 'Cualquier valor' }}"
                    >
                </div>
            @elseif($f['type'] === 'select')
                <div class="exp-fgroup">
                    <span class="exp-flabel">{{ $f['label'] }}</span>
                    <select name="f_{{ $f['param'] }}" class="exp-select">
                        <option value="">— Todos —</option>
                        @if($f['options'] === 'admins')
                            @foreach($admins as $ad)
                                <option value="{{ $ad['id'] }}" {{ (string)$f['value'] === (string)$ad['id'] ? 'selected' : '' }}>{{ $ad['label'] }}</option>
                            @endforeach
                        @else
                            @foreach($f['options'] as $optKey => $optLabel)
                                @php $optVal = is_int($optKey) ? $optLabel : $optKey; @endphp
                                <option value="{{ $optVal }}" {{ (string)$f['value'] === (string)$optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif
        @endforeach
    </div>

    <div class="exp-frow">
        @foreach($filtros as $f)
            @if($f['type'] === 'range')
                <div class="exp-fgroup">
                    <span class="exp-flabel">{{ $f['label'] }}{{ $f['suffix'] ?? ' ($)' }}</span>
                    <div class="exp-range">
                        <input type="number" step="any" name="f_{{ $f['param'] }}_min" value="{{ $f['min'] }}" class="exp-input exp-num" placeholder="Mín" @if(isset($f['min_attr'])) min="{{ $f['min_attr'] }}" @endif @if(isset($f['max_attr'])) max="{{ $f['max_attr'] }}" @endif>
                        <span>—</span>
                        <input type="number" step="any" name="f_{{ $f['param'] }}_max" value="{{ $f['max'] }}" class="exp-input exp-num" placeholder="Máx" @if(isset($f['min_attr'])) min="{{ $f['min_attr'] }}" @endif @if(isset($f['max_attr'])) max="{{ $f['max_attr'] }}" @endif>
                    </div>
                </div>
            @elseif($f['type'] === 'daterange')
                <div class="exp-fgroup">
                    <span class="exp-flabel">{{ $f['label'] }}</span>
                    <div class="exp-range">
                        <input type="date" name="f_{{ $f['param'] }}_de" value="{{ $f['de'] }}" class="exp-input exp-date">
                        <span>—</span>
                        <input type="date" name="f_{{ $f['param'] }}_a" value="{{ $f['a'] }}" class="exp-input exp-date">
                    </div>
                </div>
            @endif
        @endforeach

        <div class="exp-fgroup">
            <span class="exp-flabel">Por página</span>
            <select name="pp" class="exp-select">
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" {{ $pp === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="exp-btn exp-btn-primary">Filtrar</button>
        <a href="{{ route('owner.explorador', ['t' => $tabla]) }}" class="exp-btn exp-btn-ghost">Limpiar</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}" class="exp-btn exp-btn-csv">⬇ CSV</a>
    </div>
</form>

{{-- ── Totales del conjunto filtrado ───────────────────────── --}}
<div class="exp-chips">
    <span class="exp-chip exp-chip-n"><b>{{ number_format($tot->_n ?? 0) }}</b> registros</span>
    @foreach($cfg['sums'] as $key)
        <span class="exp-chip">Σ {{ $cfg['columns'][$key]['label'] }}: <b>${{ number_format((float)($tot->$key ?? 0), 2) }}</b></span>
    @endforeach

    <div class="exp-cols-wrap">
        <button type="button" class="exp-btn exp-btn-ghost" onclick="document.getElementById('colsMenu').classList.toggle('open')">☰ Columnas</button>
        <div class="exp-cols-menu" id="colsMenu">
            @foreach($cfg['columns'] as $key => $col)
                <label><input type="checkbox" data-col="{{ $key }}" checked> {{ $col['label'] }}</label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Tabla ───────────────────────────────────────────────── --}}
<div class="exp-table-wrap">
    <table class="exp-table" id="expTable">
        <thead>
            <tr>
                @foreach($cfg['columns'] as $key => $col)
                    <th class="col-{{ $key }} {{ $sort === $key ? 'sorted' : '' }}">
                        <a href="{{ $sortLink($key) }}">
                            {{ $col['label'] }}
                            @if($sort === $key)
                                <span>{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </a>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($cfg['columns'] as $key => $col)
                        @php $v = $row->$key; @endphp
                        <td class="col-{{ $key }} @if($col['type']==='money') exp-td-money @elseif(in_array($col['type'],['int','pct'])) exp-td-int @elseif($col['type']==='text') exp-td-text @endif">
                            @if($v === null || $v === '')
                                <span class="exp-td-null">—</span>
                            @elseif($col['type'] === 'money')
                                ${{ number_format((float)$v, 2) }}
                            @elseif($col['type'] === 'pct')
                                {{ rtrim(rtrim(number_format((float)$v, 2), '0'), '.') }}%
                            @elseif($col['type'] === 'date')
                                {{ \Carbon\Carbon::parse($v)->format('d/m/Y') }}
                            @elseif($col['type'] === 'datetime')
                                {{ \Carbon\Carbon::parse($v)->format('d/m/Y H:i') }}
                            @elseif($col['type'] === 'bool')
                                <span class="exp-pill {{ $v ? 'exp-pill-green' : 'exp-pill-gray' }}">{{ $v ? 'Sí' : 'No' }}</span>
                            @elseif($col['type'] === 'credit_score')
                                <span class="exp-score {{ $v >= 750 ? 'exp-score-good' : ($v >= 650 ? 'exp-score-warn' : 'exp-score-risk') }}">{{ $v }}</span>
                            @elseif($col['type'] === 'badge')
                                <span class="exp-pill exp-pill-{{ $pillColor($v) }}">{{ $v }}</span>
                            @elseif($col['type'] === 'text')
                                <span title="{{ $v }}">{{ \Illuminate\Support\Str::limit($v, 60) }}</span>
                            @else
                                {{ $v }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($cfg['columns']) }}"><div class="exp-empty">No hay registros que coincidan con los filtros.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Paginación ──────────────────────────────────────────── --}}
@if($rows->lastPage() > 1 || $rows->total() > 0)
<div class="exp-pager">
    <div class="exp-pager-info">
        Mostrando {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }} de {{ number_format($rows->total()) }}
    </div>
    <div class="exp-pager-links">
        <a href="{{ $rows->previousPageUrl() ?? '#' }}" class="exp-page {{ $rows->onFirstPage() ? 'disabled' : '' }}">‹ Anterior</a>
        @php
            $cur = $rows->currentPage(); $last = $rows->lastPage();
            $window = collect(range(max(1, $cur - 2), min($last, $cur + 2)));
            if (!$window->contains(1)) $window->prepend(1);
            if (!$window->contains($last)) $window->push($last);
        @endphp
        @php $prev = 0; @endphp
        @foreach($window as $pnum)
            @if($pnum - $prev > 1)<span class="exp-page disabled">…</span>@endif
            <a href="{{ $rows->url($pnum) }}" class="exp-page {{ $pnum === $cur ? 'current' : '' }}">{{ $pnum }}</a>
            @php $prev = $pnum; @endphp
        @endforeach
        <a href="{{ $rows->nextPageUrl() ?? '#' }}" class="exp-page {{ $rows->hasMorePages() ? '' : 'disabled' }}">Siguiente ›</a>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // Auto-submit al cambiar cualquier select de filtro
    document.querySelectorAll('#expForm select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            document.getElementById('expForm').submit();
        });
    });

    // Cerrar el menú de columnas al hacer clic fuera
    document.addEventListener('click', function (e) {
        var wrap = document.querySelector('.exp-cols-wrap');
        var menu = document.getElementById('colsMenu');
        if (wrap && menu && !wrap.contains(e.target)) menu.classList.remove('open');
    });

    // Mostrar/ocultar columnas (persistido por tabla en localStorage)
    var tabla = @json($tabla);
    var storageKey = 'exp_cols_' + tabla;
    var styleEl = document.createElement('style');
    document.head.appendChild(styleEl);

    var hidden = [];
    try { hidden = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) { hidden = []; }

    function applyHidden() {
        styleEl.textContent = hidden.map(function (c) {
            return '#expTable .col-' + c + '{display:none}';
        }).join('');
        document.querySelectorAll('#colsMenu input[data-col]').forEach(function (cb) {
            cb.checked = hidden.indexOf(cb.dataset.col) === -1;
        });
    }

    document.querySelectorAll('#colsMenu input[data-col]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var col = cb.dataset.col;
            if (cb.checked) {
                hidden = hidden.filter(function (c) { return c !== col; });
            } else if (hidden.indexOf(col) === -1) {
                hidden.push(col);
            }
            try { localStorage.setItem(storageKey, JSON.stringify(hidden)); } catch (e) {}
            applyHidden();
        });
    });

    applyHidden();
})();
</script>
@endpush
