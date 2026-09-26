@props([
    'size' => 'md',        // sm | md | lg
    'fallback' => 'dark',  // color del recuadro con iniciales: dark | brand
])

@php
    // Se resuelve una vez por render y se cachea en el contenedor: el layout
    // lo pinta en cada página y no conviene tocar disco más de lo necesario.
    $logo = app(App\Services\Branding\LogoService::class)->url();

    $company = setting('company.name', config('app.name'));

    $box = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-12 w-12 text-base',
        default => 'h-9 w-9 text-sm',
    };

    $logoBox = match ($size) {
        'sm' => 'h-8 max-w-[7rem]',
        'lg' => 'h-12 max-w-[11rem]',
        default => 'h-9 max-w-[9rem]',
    };

    $fallbackColor = $fallback === 'brand' ? 'bg-indigo-600' : 'bg-stone-900';
@endphp

@if ($logo)
    <img src="{{ $logo }}" alt="{{ $company }}"
         {{ $attributes->class(['w-auto object-contain', $logoBox]) }}>
@else
    <div {{ $attributes->class([
        'flex shrink-0 items-center justify-center rounded-lg font-bold text-white',
        $box,
        $fallbackColor,
    ]) }}>
        {{ Str::upper(Str::substr($company, 0, 2)) }}
    </div>
@endif
