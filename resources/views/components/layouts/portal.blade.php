<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Tu obra' }} · {{ setting('company.name', config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-stone-50 font-sans antialiased">

<header class="border-b border-stone-200 bg-white">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <x-company-logo size="md" />
            <div class="leading-tight">
                <p class="text-sm font-semibold text-stone-900">{{ setting('company.name', config('app.name')) }}</p>
                <p class="text-xs text-stone-500">Área privada de cliente</p>
            </div>
        </div>

        <form method="POST" action="{{ route('portal.leave') }}">
            @csrf
            <button type="submit" class="text-xs font-medium text-stone-500 transition hover:text-stone-900">
                Cerrar sesión
            </button>
        </form>
    </div>

    <nav class="mx-auto max-w-5xl overflow-x-auto px-4 sm:px-6">
        <div class="flex gap-1 border-t border-stone-100 pt-1">
            <x-portal.nav />
        </div>
    </nav>
</header>

<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    {{ $slot }}
</main>

<footer class="mx-auto max-w-5xl px-4 pb-10 text-center text-xs text-stone-400 sm:px-6">
    <p>{{ setting('company.name', config('app.name')) }}
        @if (setting('company.phone')) · {{ setting('company.phone') }} @endif
        @if (setting('company.email')) · {{ setting('company.email') }} @endif
    </p>
    <p class="mt-1">Este enlace es personal. No lo compartas con terceros.</p>
</footer>

<x-toasts />
@livewireScripts
</body>
</html>