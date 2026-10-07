@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line focus:border-navy focus:ring-navy/15 rounded-md shadow-sm']) }}>
