<x-guest-layout>
    <h1 class="font-display text-2xl text-[#1F2421] mb-1">Terms & Conditions</h1>
    <p class="text-sm text-gray-500 mb-6">Version {{ $currentVersion->version_label }}</p>
    <div class="prose prose-sm max-w-none">
        {!! \Illuminate\Support\Str::markdown($currentVersion->content) !!}
    </div>
</x-guest-layout>
