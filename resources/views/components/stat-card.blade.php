@props([
    'label',
    'value',
    'sub'      => null,
    'color'    => 'green',   // green | blue | red | amber
    'trend'    => null,      // e.g. '+12.5%'
    'trendUp'  => true,
])

@php
$palette = [
    'green' => ['bg:#f0fdf4', 'icon-bg:rgba(34,197,94,0.12)', 'icon-color:#22c55e'],
    'blue'  => ['bg:#eff6ff', 'icon-bg:rgba(59,130,246,0.12)', 'icon-color:#3b82f6'],
    'red'   => ['bg:#fff1f2', 'icon-bg:rgba(239,68,68,0.12)',  'icon-color:#ef4444'],
    'amber' => ['bg:#fffbeb', 'icon-bg:rgba(245,158,11,0.12)', 'icon-color:#f59e0b'],
];
$p = $palette[$color] ?? $palette['green'];
// parse simple k=v pairs
$cfg = [];
foreach ($p as $item) { [$k,$v] = explode(':', $item, 2); $cfg[$k] = $v; }
@endphp

<div class="bg-white rounded-xl p-5" style="border:1px solid rgba(0,0,0,0.07);box-shadow:0 1px 3px rgba(0,0,0,0.04)">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wider mb-1.5" style="color:#9ca3af;letter-spacing:.06em">{{ $label }}</p>
            <p class="text-2xl font-bold leading-none" style="color:#111827;letter-spacing:-0.03em">{{ $value }}</p>
            @if($sub)
            <p class="text-xs mt-1.5" style="color:#9ca3af">{{ $sub }}</p>
            @endif
            @if($trend)
            <div class="flex items-center gap-1 mt-2">
                <span class="text-xs font-semibold" style="color:{{ $trendUp ? '#22c55e' : '#ef4444' }}">
                    {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
                </span>
                <span class="text-xs" style="color:#9ca3af">vs mes anterior</span>
            </div>
            @endif
        </div>
        @if(isset($icon))
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:{{ $cfg['icon-bg'] }};color:{{ $cfg['icon-color'] }}">
            {{ $icon }}
        </div>
        @endif
    </div>
</div>
