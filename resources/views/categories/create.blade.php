<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Category</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded-lg" required>
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Save</button>
                        <a href="{{ route('categories.index') }}" class="px-4 py-2 rounded-lg border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
