<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                <h1 class="font-display text-2xl text-[#1F2421]">Categories</h1>
                <a href="{{ route('categories.create') }}" class="bg-[#1B4332] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                    + New Category
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            {{-- Desktop: table --}}
            <div class="hidden md:block bg-white rounded-lg border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F7F9F6] text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Products</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($categories as $category)
                            <tr class="hover:bg-[#F7F9F6]/50">
                                <td class="px-6 py-4 font-medium text-[#1F2421]">{{ $category->name }}</td>
                                <td class="px-6 py-4 font-mono-price text-gray-500">{{ $category->products_count }}</td>
                                <td class="px-6 py-4 space-x-3">
                                    <a href="{{ route('categories.edit', $category) }}" class="text-[#1B4332] hover:underline">Edit</a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: stacked cards --}}
            <div class="md:hidden space-y-3">
                @foreach ($categories as $category)
                    <div class="bg-white rounded-lg border border-gray-100 p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-[#1F2421]">{{ $category->name }}</p>
                                <p class="text-xs text-gray-400 font-mono-price mt-1">{{ $category->products_count }} products</p>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <a href="{{ route('categories.edit', $category) }}" class="text-[#1B4332]">Edit</a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
