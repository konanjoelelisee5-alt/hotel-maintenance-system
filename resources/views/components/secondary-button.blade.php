@props(['href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </button>
@endif