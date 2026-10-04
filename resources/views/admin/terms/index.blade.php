<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]">Terms & Conditions</h2>
            <a href="{{ route('admin.terms.create') }}" class="bg-[#1B4332] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/90">
                Publish New Version
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F7F9F6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Version</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acceptances</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Published</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($versions as $version)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-[#1F2421]">{{ $version->version_label }}</td>
                            <td class="px-4 py-3">
                                @if ($version->is_active)
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-[#1B4332]/10 text-[#1B4332]">Active</span>
                                @else
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">Archived</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $version->acceptances_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
