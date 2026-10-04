<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl p-8">
                <h1 class="font-display text-2xl text-[#1F2421] mb-1">
                    {{ $currentVersion->version_label === 'v1.0' ? 'Terms & Conditions' : 'Updated Terms & Conditions' }}
                </h1>
                <p class="text-sm text-gray-500 mb-6">Version {{ $currentVersion->version_label }} — please review and accept to continue.</p>

                <div class="prose prose-sm max-w-none border border-gray-100 rounded-lg p-5 max-h-96 overflow-y-auto bg-[#F7F9F6] mb-6">
                    {!! \Illuminate\Support\Str::markdown($currentVersion->content) !!}
                </div>

                <form method="POST" action="{{ route('terms.accept') }}">
                    @csrf

                    <label class="flex items-start gap-3 mb-4">
                        <input type="checkbox" name="agree" value="1" required
                               class="mt-1 rounded border-gray-300 text-[#1B4332] focus:ring-[#1B4332]">
                        <span class="text-sm text-gray-700">
                            I have read and agree to the SokoTrack Terms & Conditions.
                        </span>
                    </label>
                    @error('agree') <p class="text-xs text-[#C2622D] mb-3">{{ $message }}</p> @enderror

                    <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium py-2.5 rounded-lg hover:bg-[#1B4332]/90">
                        Accept & Continue
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
