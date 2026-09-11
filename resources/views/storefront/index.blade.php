<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6]">

        {{-- Sticky header: responsive - stacks on mobile, single row on desktop --}}
        <div class="sticky top-0 z-10 bg-[#1B4332] shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-center justify-between sm:contents">
                        <h1 class="font-display text-xl sm:text-2xl text-white shrink-0">SokoTrack</h1>

                        <a href="{{ route('cart.index') }}" class="sm:hidden relative flex items-center gap-2 bg-[#E8A93B] text-[#1F2421] font-medium px-3 py-2 rounded-lg text-sm">
                            <span>Cart</span>
                            <span id="cart-badge-mobile" class="font-mono-price text-xs bg-[#1F2421]/10 px-2 py-0.5 rounded-full">
                                @if ($cartCount > 0)
                                    {{ $cartCount }} · KES {{ number_format($cartTotal, 0) }}
                                @endif
                            </span>
                        </a>
                    </div>

                    <form method="GET" class="flex-1 min-w-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search products, SKUs, brands..."
                               class="w-full rounded-lg border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#E8A93B]">
                    </form>

                    <div class="flex items-center justify-between sm:justify-end gap-3">
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

                        <a href="{{ route('cart.index') }}" class="hidden sm:flex relative items-center gap-2 bg-[#E8A93B] text-[#1F2421] font-medium px-4 py-2 rounded-lg text-sm hover:brightness-95">
                            <span>Cart</span>
                            <span id="cart-badge" class="font-mono-price text-xs bg-[#1F2421]/10 px-2 py-0.5 rounded-full">
                                @if ($cartCount > 0)
                                    {{ $cartCount }} · KES {{ number_format($cartTotal, 0) }}
                                @endif
                            </span>
                        </a>
                    </div>
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
                {{-- Mobile category selector (sidebar is desktop-only) --}}
                <div class="lg:hidden mb-4">
                    <select onchange="window.location.href=this.value" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="{{ route('storefront.index') }}" @selected(!request('category_id'))>All Products</option>
                        @foreach ($categories as $category)
                            <option value="{{ route('storefront.index', ['category_id' => $category->id]) }}" @selected(request('category_id') == $category->id)>
                                {{ $category->name }} ({{ $category->products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (session('success'))
                    <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse ($products as $product)
                        <div class="bg-white rounded-lg overflow-hidden border border-gray-100 hover:border-[#1B4332]/30 transition-colors flex flex-col">
                            <a href="{{ route('storefront.show', $product) }}">
                                <div class="h-32 sm:aspect-[4/3]">
                                    @if ($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-3xl font-display" style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                                            {{ strtoupper(substr($product->name, 0, 2)) }}
                                        </div>
                                    @endif
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

                                    @if ($product->reviews_count > 0)
                                        <div class="flex items-center gap-1 mt-1.5">
                                            <div class="flex text-[#E8A93B]">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="w-3.5 h-3.5 {{ $i <= round($product->reviews_avg_rating) ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20">
                                                        <path d="M10 15l-5.878 3.09L5.5 11.545 1 7.41l6.061-.88L10 1l2.939 5.53 6.061.88-4.5 4.135 1.378 6.545z"/>
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-400">({{ $product->reviews_count }})</span>
                                        </div>
                                    @endif
                                </div>

                                <form onsubmit="addToCart(event, '{{ route('cart.add', $product) }}')" class="mt-4 flex items-center gap-2">
                                    <div class="flex items-center border border-gray-300 rounded-lg" x-data="{ qty: 1 }">
                                        <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-2 py-1.5 text-gray-500 hover:bg-gray-50">−</button>
                                        <input type="number" name="quantity" x-model="qty" min="1" max="{{ $product->stock_quantity }}" class="w-10 text-center text-sm border-0 focus:ring-0">
                                        <button type="button" @click="qty = Math.min({{ $product->stock_quantity }}, qty + 1)" class="px-2 py-1.5 text-gray-500 hover:bg-gray-50">+</button>
                                    </div>
                                    <button type="submit" class="flex-1 bg-[#1B4332] text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                                        Add to cart
                                    </button>
                                    <span class="add-feedback text-xs text-green-600 opacity-0 transition-opacity whitespace-nowrap">✓ Added</span>
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
