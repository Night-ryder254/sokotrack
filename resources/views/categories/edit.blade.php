<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-2xl text-[#1F2421] mb-6">Edit Category</h1>

            <div class="bg-white rounded-lg border border-gray-100 p-6">
                <form action="{{ route('categories.update', $category) }}" method="POST">
                    @csrf @method('PUT')
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full rounded-lg border-gray-300 focus:ring-[#1B4332] focus:border-[#1B4332]" required>
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="bg-[#1B4332] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">Update</button>
                        <a href="{{ route('categories.index') }}" class="px-4 py-2 rounded-lg border text-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
