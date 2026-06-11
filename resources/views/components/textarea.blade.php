@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface-container-low border-outline-variant focus:border-lime focus:ring-lime text-on-surface rounded-md shadow-sm']) }}>{{ $slot }}</textarea>
