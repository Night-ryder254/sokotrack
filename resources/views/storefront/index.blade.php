<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6]">

        {{-- Sticky header: search, tier stamp, cart total --}}
        <div class="sticky top-0 z-10 bg-[#1B4332] shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-wrap items-center gap-4">
                    <h1 class="font-display text-2xl text-white shrink-0">SokoTrack</h1>

                    <form method="GET" class="flex-1 min-w-[240px]">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search products, SKUs, brands..."
                               class="w-full rounded-lg border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#E8A93B]">
                    </form>

                    @if ($vendor)
                        <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-full">
                            <span @class([
                                'w-2.5 h-2.5 rounded-full',
                                'bg-amber-600' => $vendor->tier?->name === 'Bronze',
                                'bg-gray-300' => $vendor->tier?->name === 'Silver',
                                'bg-yellow-400' => $vendor->tier?->name === 'Gold',
                            ])></span>
                            <span class="text-white text-sm font-medium">{{ $vendor->tier?->name ?? 'Bronze' }}</span>
                            <span class="text-white/60 text-xs font-mono-price">· {{ $vendor->loyalty_points }} pts</span>
                        </div>
                    @endif

                    <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 bg-[#E8A93B] text-[#1F2421] font-medium px-4 py-2 rounded-lg text-sm hover:brightness-95">
                        <span>Cart</span>
                        @if ($cartCount > 0)
                            <span class="font-mono-price text-xs bg-[#1F2421]/10 px-2 py-0.5 rounded-full">
                                {{ $cartCount }} · KES {{ number_format($cartTotal, 0) }}
                            </span>
                        @else
                            <span class="text-xs opacity-70">Empty</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex gap-8">

            {{-- Category sidebar --}}
            <aside class="w-56 shrink-0 hidden lg:block">
                <h2 class="font-display text-lg text-[#1F2421] mb-3">Departments</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('storefront.index') }}" @class([
                            'block px-3 py-2 rounded-lg text-sm',
                            'bg-[#1B4332] text-white' => !request('category_id'),
                            'text-gray-700 hover:bg-gray-100' => request('category_id'),
                        ])>All Products</a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('storefront.index', ['category_id' => $category->id]) }}" @class([
                                'flex justify-between px-3 py-2 rounded-lg text-sm',
                                'bg-[#1B4332] text-white' => request('category_id') == $category->id,
                                'text-gray-700 hover:bg-gray-100' => request('category_id') != $category->id,
                            ])>
                                <span>{{ $category->name }}</span>
                                <span class="text-xs opacity-60">{{ $category->products_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a href="https://wa.me/254700000000" target="_blank" class="mt-6 flex items-center gap-2 bg-green-50 text-green-800 px-3 py-3 rounded-lg text-sm font-medium hover:bg-green-100">
                    💬 Chat on WhatsApp
                </a>
            </aside>

            {{-- Product grid --}}
            <div class="flex-1">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse ($products as $product)
                        <div class="bg-white rounded-lg overflow-hidden border border-gray-100 hover:border-[#1B4332]/30 transition-colors flex flex-col">
                            <a href="{{ route('storefront.show', $product) }}">
                                <div class="aspect-[4/3] flex items-center justify-center text-white text-3xl font-display"
                                     style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                                    {{ strtoupper(substr($product->name, 0, 2)) }}
                                </div>
                            </a>
                            <div class="p-5 flex flex-col justify-between flex-1">
                                <div>
                                    <p class="text-xs text-[#1B4332] font-medium uppercase tracking-wide">{{ $product->category->name }}</p>
                                    <a href="{{ route('storefront.show', $product) }}">
                                        <h3 class="font-display text-base text-[#1F2421] mt-2 hover:underline">{{ $product->name }}</h3>
                                    </a>
                                    <p class="text-xs text-gray-400 font-mono-price mt-1">{{ $product->sku }}</p>
                                    <p class="font-mono-price text-lg text-[#1B4332] mt-3">KES {{ number_format($product->price, 2) }}</p>
                                    <p class="text-xs text-green-600 mt-1">✓ In stock</p>
                                </div>

                                <form action="{{ route('cart.add', $product) }}" method="POST" class="mt-4 flex items-center gap-2">
                                    @csrf
                                    <div class="flex items-center border border-gray-300 rounded-lg">
                                        <button type="button" onclick="this.nextElementSibling.stepDown()" class="px-2 py-1.5 text-gray-500 hover:bg-gray-50">−</button>
                                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" class="w-10 text-center text-sm border-0 focus:ring-0">
                                        <button type="button" onclick="this.previousElementSibling.stepUp()" class="px-2 py-1.5 text-gray-500 hover:bg-gray-50">+</button>
                                    </div>
                                    <button type="submit" class="flex-1 bg-[#1B4332] text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                                        Add to cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-full text-center py-12">No products match your search.</p>
                    @endforelse
                </div>

                <div class="mt-8">{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
