@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white/5 border-spigo-violet/30 focus:border-spigo-lime focus:ring-spigo-lime text-gray-300 rounded-md shadow-sm']) }}>