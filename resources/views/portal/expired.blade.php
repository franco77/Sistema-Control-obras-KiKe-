<x-layouts.portal title="Enlace no disponible">
    <div class="mx-auto max-w-md rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
            <x-icon name="warning" class="h-6 w-6 text-amber-500" />
        </div>
        <h1 class="text-lg font-semibold text-stone-900">Este enlace ya no está disponible</h1>
        <p class="mt-2 text-sm text-stone-600">
            {{ session('portal_error') ?? 'El enlace ha caducado, ha sido revocado o no es correcto.' }}
        </p>
        <p class="mt-4 text-sm text-stone-500">
            Escríbenos y te enviamos uno nuevo
            @if (setting('company.email'))
                a <a href="mailto:{{ setting('company.email') }}" class="font-medium text-stone-900 underline">{{ setting('company.email') }}</a>
            @endif
            @if (setting('company.phone'))
                o llámanos al <span class="font-medium text-stone-900">{{ setting('company.phone') }}</span>
            @endif.
        </p>
    </div>
</x-layouts.portal>