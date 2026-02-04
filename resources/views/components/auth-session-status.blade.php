@if (session('status'))
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600 dark:text-green-400']) }}>
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-red-600 dark:text-red-400']) }}>
        {{ session('error') }}
    </div>
@endif