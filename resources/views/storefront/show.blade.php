<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('storefront.index') }}" class="text-sm text-[#1B4332] hover:underline">← Back to Shop</a>

            <div class="mt-4 bg-white rounded-lg p-8 grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- Placeholder product image: colored panel with initials, since no real photos uploaded yet --}}
                <div class="aspect-square rounded-lg flex items-center justify-center text-white text-6xl font-display"
                     style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                    {{ strtoupper(substr($product->name, 0, 2)) }}
                </div>

                <div>
                    <p class="text-xs text-[#1B4332] font-medium uppercase tracking-wide">{{ $product->category->name }}</p>
                    <h1 class="font-display text-3xl text-[#1F2421] mt-2">{{ $product->name }}</h1>
                    <p class="text-sm text-gray-400 font-mono-price mt-1">{{ $product->sku }}</p>

                    <p class="font-mono-price text-3xl text-[#1B4332] mt-4">KES {{ number_format($product->price, 2) }}</p>
                    <p class="text-sm text-green-600 mt-1">✓ In stock</p>

                    <p class="text-gray-600 mt-4 leading-relaxed">{{ $product->description }}</p>

                    <form onsubmit="addToCart(event, '{{ route('cart.add', $product) }}')" class="mt-6 flex items-center gap-3">
                        {{-- Quantity stepper: click +/- via Alpine state, no DOM sibling reaching --}}
                        <div class="flex items-center border border-gray-300 rounded-lg" x-data="{ qty: 1 }">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-3 py-2 text-gray-500 hover:bg-gray-50">−</button>
                            <input type="number" name="quantity" x-model="qty" min="1" max="{{ $product->stock_quantity }}" class="w-14 text-center border-0 focus:ring-0">
                            <button type="button" @click="qty = Math.min({{ $product->stock_quantity }}, qty + 1)" class="px-3 py-2 text-gray-500 hover:bg-gray-50">+</button>
                        </div>
                        <button type="submit" class="bg-[#1B4332] text-white px-6 py-2.5 rounded-lg font-medium hover:bg-[#163829]">
                            Add to Cart
                        </button>
                        <span class="add-feedback text-sm text-green-600 opacity-0 transition-opacity whitespace-nowrap">✓ Added</span>
                    </form>
                </div>
            </div>

            @if ($relatedProducts->isNotEmpty())
                <h2 class="font-display text-xl text-[#1F2421] mt-10 mb-4">More in {{ $product->category->name }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($relatedProducts as $related)
                        <a href="{{ route('storefront.show', $related) }}" class="bg-white rounded-lg p-4 hover:border-[#1B4332]/30 border border-gray-100">
                            <div class="aspect-square rounded-lg mb-2 flex items-center justify-center text-white text-xl font-display"
                                 style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                                {{ strtoupper(substr($related->name, 0, 2)) }}
                            </div>
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $related->name }}</p>
                            <p class="text-xs font-mono-price text-[#1B4332]">KES {{ number_format($related->price, 2) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>