<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Product</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Product details form --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full rounded-lg border-gray-300" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full rounded-lg border-gray-300" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="w-full rounded-lg border-gray-300" required>
                        @error('sku') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" class="w-full rounded-lg border-gray-300" rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (KES)</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" class="w-full rounded-lg border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" class="w-full rounded-lg border-gray-300" required>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Update Details</button>
                        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-lg border">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- Stock adjustment — deliberately a SEPARATE form/section --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Adjust Stock</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Current stock: <span class="font-medium">{{ $product->stock_quantity }}</span>
                </p>
                <form action="{{ route('products.adjust-stock', $product) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Change</label>
                            <input type="number" name="quantity_change" placeholder="e.g. 50 or -10" class="w-full rounded-lg border-gray-300" required>
                            <p class="text-xs text-gray-400 mt-1">Positive to add stock, negative to remove.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <select name="reason" class="w-full rounded-lg border-gray-300" required>
                                <option value="restock">Restock</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="return">Return</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <input type="text" name="notes" class="w-full rounded-lg border-gray-300">
                    </div>
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Apply Adjustment</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
