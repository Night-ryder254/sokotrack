<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Product</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('products.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full rounded-lg border-gray-300" required>
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border-gray-300" required>
                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" class="w-full rounded-lg border-gray-300" required>
                        @error('sku') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" class="w-full rounded-lg border-gray-300" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (KES)</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="w-full rounded-lg border-gray-300" required>
                            @error('price') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" class="w-full rounded-lg border-gray-300" required>
                            @error('stock_quantity') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}" class="w-full rounded-lg border-gray-300" required>
                            @error('low_stock_threshold') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Save Product</button>
                        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-lg border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
