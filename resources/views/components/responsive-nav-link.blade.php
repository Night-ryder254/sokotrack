@props(['active' => false, 'danger' => false])

@php
$classes = match(true) {
    $danger => 'block w-full ps-3 pe-4 py-3 border-l-4 border-transparent text-start text-base font-medium text-[#C2622D] hover:bg-[#C2622D]/5 focus:outline-none focus:bg-[#C2622D]/5 transition duration-150 ease-in-out',
    $active => 'block w-full ps-3 pe-4 py-3 border-l-4 border-[#1B4332] text-start text-base font-medium text-[#1B4332] bg-[#1B4332]/5 focus:outline-none focus:bg-[#1B4332]/10 transition duration-150 ease-in-out',
    default => 'block w-full ps-3 pe-4 py-3 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-[#1F2421] hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-[#1F2421] focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out',
};
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
