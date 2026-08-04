<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                <h1 class="font-display text-2xl text-[#1F2421]">Products</h1>
                <a href="{{ route('products.create') }}" class="bg-[#1B4332] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                    + New Product
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            <form method="GET" class="mb-4 bg-white border border-gray-100 rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Category</label>
                    <select name="category_id" class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock')) class="rounded text-[#1B4332] focus:ring-[#1B4332]">
                        Low stock only
                    </label>
                </div>
                <button type="submit" class="bg-[#1F2421] text-white px-4 py-2 rounded-lg text-sm">Filter</button>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 underline">Clear</a>
            </form>

            {{-- Desktop: table --}}
            <div class="hidden md:block bg-white rounded-lg border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F7F9F6] text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Stock</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($products as $product)
                            <tr class="hover:bg-[#F7F9F6]/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-[#1F2421]">{{ $product->name }}</div>
                                    <div class="text-gray-400 text-xs font-mono-price">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $product->category->name }}</td>
                                <td class="px-6 py-4 font-mono-price text-[#1B4332]">KES {{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span @class([
                                        'px-2 py-1 rounded-full text-xs font-medium font-mono-price',
                                        'bg-[#C2622D]/10 text-[#C2622D]' => $product->stock_quantity <= $product->low_stock_threshold,
                                        'bg-green-100 text-green-700' => $product->stock_quantity > $product->low_stock_threshold,
                                    ])>
                                        {{ $product->stock_quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-3">
                                    <a href="{{ route('products.edit', $product) }}" class="text-[#1B4332] hover:underline">Edit</a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Delete this product?');">
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
                @foreach ($products as $product)
                    <div class="bg-white rounded-lg border border-gray-100 p-4">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-[#1F2421] truncate">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400 font-mono-price">{{ $product->sku }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $product->category->name }}</p>
                            </div>
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-medium font-mono-price shrink-0',
                                'bg-[#C2622D]/10 text-[#C2622D]' => $product->stock_quantity <= $product->low_stock_threshold,
                                'bg-green-100 text-green-700' => $product->stock_quantity > $product->low_stock_threshold,
                            ])>
                                {{ $product->stock_quantity }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-50">
                            <p class="font-mono-price text-[#1B4332] font-medium">KES {{ number_format($product->price, 2) }}</p>
                            <div class="flex gap-3 text-sm">
                                <a href="{{ route('products.edit', $product) }}" class="text-[#1B4332]">Edit</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
