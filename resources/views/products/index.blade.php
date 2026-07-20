<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Products</h2>
            <a href="{{ route('products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
                + New Product
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" class="mb-4 bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Category</label>
                    <select name="category_id" class="rounded-lg border-gray-300 text-sm">
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
                        <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))>
                        Low stock only
                    </label>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 underline">Clear</a>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Stock</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($products as $product)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                    <div class="text-gray-400 text-xs">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $product->category->name }}</td>
                                <td class="px-6 py-4 text-gray-700">KES {{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span @class([
                                        'px-2 py-1 rounded text-xs font-medium',
                                        'bg-red-100 text-red-700' => $product->stock_quantity <= $product->low_stock_threshold,
                                        'bg-green-100 text-green-700' => $product->stock_quantity > $product->low_stock_threshold,
                                    ])>
                                        {{ $product->stock_quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-3">
                                    <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">Edit</a>
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

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
