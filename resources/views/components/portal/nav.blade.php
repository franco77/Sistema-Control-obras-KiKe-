{{--
    Navegación del portal. Se resuelve desde el token de la sesión, de modo
    que el layout no necesita que cada pantalla le pase la obra.
--}}
@php
    $token = request()->attributes->get('portalToken');
    $target = $token?->tokenable;

    $project = $target instanceof \App\Models\Project
        ? $target
        : (\App\Models\Project::find(data_get($target, 'project_id')));

    $pendingExtras = $project?->extras()->where('status', 'sent')->count() ?? 0;
    $unreadMessages = (int) ($project?->conversations()->sum('unread_for_client') ?? 0);
@endphp

@if ($project && $token?->can('project.view'))
    <x-portal.tab route="portal.project">Resumen</x-portal.tab>
    <x-portal.tab route="portal.project.tasks">Trabajos</x-portal.tab>
    <x-portal.tab route="portal.project.gallery">Fotos</x-portal.tab>
    <x-portal.tab route="portal.project.incidents">Incidencias</x-portal.tab>
    <x-portal.tab route="portal.project.extras" :badge="$pendingExtras ?: null">Extras</x-portal.tab>
    <x-portal.tab route="portal.project.documents">Documentos</x-portal.tab>
    <x-portal.tab route="portal.project.messages" :badge="$unreadMessages ?: null">Mensajes</x-portal.tab>
@endif