<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-semibold text-[#1F2421]">Publish New Terms Version</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-[#E8A93B]/10 border border-[#E8A93B]/30 text-[#8a5c14] rounded-lg px-4 py-3 text-sm mb-6">
            Publishing a new version will require every vendor to re-accept before continuing to use the app.
        </div>

        <form method="POST" action="{{ route('admin.terms.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Version label</label>
                <input type="text" name="version_label" value="{{ old('version_label') }}" placeholder="e.g. v1.1" required
                       class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                @error('version_label') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Content (Markdown supported)</label>
                <textarea name="content" rows="16" required
                          class="w-full rounded-lg border-gray-300 text-sm font-mono focus:ring-[#1B4332] focus:border-[#1B4332]">{{ old('content') }}</textarea>
                @error('content') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium py-2.5 rounded-lg hover:bg-[#1B4332]/90">
                Publish
            </button>
        </form>
    </div>
</x-app-layout>
