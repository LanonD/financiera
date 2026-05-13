@props(['status'])

@php
$styles = [
    'Activo'     => 'background:#dcfce7;color:#16a34a',
    'Atrasado'   => 'background:#fee2e2;color:#dc2626',
    'Pendiente'  => 'background:#fef9c3;color:#ca8a04',
    'Finalizado' => 'background:#f3f4f6;color:#6b7280',
    'Retirado'   => 'background:#f3f4f6;color:#9ca3af',
];
$style = $styles[$status] ?? 'background:#f3f4f6;color:#6b7280';
@endphp

<span style="{{ $style }};display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600">
    {{ $status }}
</span>
