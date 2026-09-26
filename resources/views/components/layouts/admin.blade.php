<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }} · {{ setting('company.name', config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50 font-sans antialiased dark:bg-slate-900">
<div class="flex min-h-full" x-data="{ sidebar: false }">

    {{-- Barra lateral --}}
    <aside class="fixed inset-y-0 left-0 z-30 w-64 -translate-x-full border-r border-slate-200 bg-white transition-transform lg:translate-x-0 dark:border-slate-700 dark:bg-slate-800"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 items-center gap-2 border-b border-slate-100 px-5 dark:border-slate-700">
            <x-company-logo size="sm" fallback="brand" />
            <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ setting('company.name', config('app.name')) }}
            </span>
        </div>

        <nav class="flex flex-col gap-0.5 p-3">
            @php
                $nav = [
                    ['admin.dashboard', 'Panel', 'home', null],
                    ['admin.clients.index', 'Clientes', 'users', 'clients.view'],
                    ['admin.providers.index', 'Proveedores', 'wrench', 'providers.view'],
                    ['admin.quotes.index', 'Presupuestos', 'document', 'quotes.view'],
                    ['admin.projects.index', 'Obras', 'building', 'projects.view'],
                    ['admin.calendar.index', 'Agenda', 'calendar', 'calendar.view'],
                    ['admin.conversations.index', 'Mensajes', 'chat', 'conversations.view'],
                    ['admin.catalog.index', 'Catálogo', 'chart', 'catalog.view'],
                    ['admin.documents.index', 'Documentos', 'document', 'documents.view'],
                ];
            @endphp

            @foreach ($nav as [$route, $label, $icon, $permission])
                @continue($permission && ! auth()->user()->can($permission))
                @php $active = request()->routeIs($route) || request()->routeIs(Str::beforeLast($route, '.').'.*'); @endphp
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                          {{ $active
                             ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300'
                             : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
                    {{ $label }}
                </a>
            @endforeach

            @role('admin')
                <div class="my-2 border-t border-slate-100 dark:border-slate-700"></div>
                <a href="{{ route('admin.settings.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">
                    <x-icon name="cog" class="h-5 w-5 shrink-0" />
                    Configuración
                </a>
            @endrole
        </nav>

        <div class="absolute inset-x-0 bottom-0 border-t border-slate-100 p-3 dark:border-slate-700">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    {{ auth()->user()->initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-medium text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ auth()->user()->roles->first()?->name ?? 'Usuario' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 transition hover:text-red-500" title="Salir">
                        <x-icon name="logout" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Contenido --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur lg:px-8 dark:border-slate-700 dark:bg-slate-800/80">
            <button @click="sidebar = !sidebar" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="flex-1"></div>

            @livewire('admin.shared.notification-bell')
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <div x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden" style="display:none"></div>
</div>

<x-toasts />
@livewireScripts
</body>
</html>