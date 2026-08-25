@props(['header' => null])

<x-app-layout :module-id="config('pidgey.portal_app_id', 12)" :module-menu="view('Pidgey::components.menu-main')" :header="$header">
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</x-app-layout>
