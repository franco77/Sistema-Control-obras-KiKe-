@props(['route', 'badge' => null])

@php $active = request()->routeIs($route); @endphp

<a href="{{ route($route) }}"
   class="flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium transition
          {{ $active
             ? 'border-stone-900 text-stone-900'
             : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-800' }}">
    {{ $slot }}
    @if ($badge)
        <span class="rounded-full bg-amber-100 px-1.5 text-[11px] font-semibold text-amber-700">{{ $badge }}</span>
    @endif
</a>