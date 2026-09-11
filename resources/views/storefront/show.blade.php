<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('storefront.index') }}" class="text-sm text-[#1B4332] hover:underline">← Back to Shop</a>

            @if (session('success'))
                <div class="mt-4 rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-4 rounded-lg bg-[#C2622D]/10 border border-[#C2622D]/30 text-[#C2622D] px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-4 bg-white rounded-lg p-8 grid grid-cols-1 md:grid-cols-2 gap-8">

                <div class="h-48 sm:h-auto sm:aspect-square rounded-lg overflow-hidden">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-white text-6xl font-display" style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                            {{ strtoupper(substr($product->name, 0, 2)) }}
                        </div>
                    @endif
                </div>

                <div>
                    <p class="text-xs text-[#1B4332] font-medium uppercase tracking-wide">{{ $product->category->name }}</p>
                    <h1 class="font-display text-3xl text-[#1F2421] mt-2">{{ $product->name }}</h1>
                    <p class="text-sm text-gray-400 font-mono-price mt-1">{{ $product->sku }}</p>

                    @if ($averageRating)
                        <div class="flex items-center gap-1.5 mt-2">
                            <div class="flex text-[#E8A93B]">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= round($averageRating) ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09L5.5 11.545 1 7.41l6.061-.88L10 1l2.939 5.53 6.061.88-4.5 4.135 1.378 6.545z"/>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-sm text-gray-500">{{ number_format($averageRating, 1) }} ({{ $reviews->count() }} review{{ $reviews->count() === 1 ? '' : 's' }})</span>
                        </div>
                    @endif

                    <p class="font-mono-price text-3xl text-[#1B4332] mt-4">KES {{ number_format($product->price, 2) }}</p>
                    <p class="text-sm text-green-600 mt-1">✓ In stock</p>

                    <p class="text-gray-600 mt-4 leading-relaxed">{{ $product->description }}</p>

                    <form onsubmit="addToCart(event, '{{ route('cart.add', $product) }}')" class="mt-6 flex items-center gap-3">
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

            {{-- Reviews --}}
            <div class="mt-10 bg-white rounded-lg p-8">
                <h2 class="font-display text-xl text-[#1F2421] mb-6">
                    Reviews @if ($reviews->isNotEmpty()) ({{ $reviews->count() }}) @endif
                </h2>

                @if ($hasPurchased)
                    <div class="bg-[#F7F9F6] rounded-lg p-5 mb-6">
                        <p class="text-sm font-medium text-[#1F2421] mb-3">
                            {{ $existingReview ? 'Update your review' : 'Leave a review' }}
                        </p>
                        <form method="POST" action="{{ route('reviews.store', $product) }}" x-data="{ rating: {{ $existingReview->rating ?? 0 }} }">
                            @csrf

                            <div class="flex gap-1 mb-3">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" @click="rating = {{ $i }}" class="focus:outline-none">
                                        <svg class="w-7 h-7 transition"
                                             :class="rating >= {{ $i }} ? 'fill-[#E8A93B] text-[#E8A93B]' : 'fill-gray-200 text-gray-200'"
                                             viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09L5.5 11.545 1 7.41l6.061-.88L10 1l2.939 5.53 6.061.88-4.5 4.135 1.378 6.545z"/>
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" x-model="rating">

                            <textarea name="comment" rows="3" placeholder="Share your experience with this product (optional)"
                                      class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">{{ $existingReview->comment ?? old('comment') }}</textarea>
                            @error('comment') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                            @error('rating') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror

                            <button type="submit" :disabled="rating === 0"
                                    :class="rating === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="mt-3 bg-[#1B4332] text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-[#1B4332]/90">
                                {{ $existingReview ? 'Update Review' : 'Submit Review' }}
                            </button>
                        </form>
                    </div>
                @endif

                @forelse ($reviews as $review)
                    <div class="border-t border-gray-100 py-4 first:border-t-0 first:pt-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-[#1F2421]">{{ $review->vendor->business_name }}</p>
                                <div class="flex text-[#E8A93B] mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09L5.5 11.545 1 7.41l6.061-.88L10 1l2.939 5.53 6.061.88-4.5 4.135 1.378 6.545z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                        @if ($review->comment)
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $review->comment }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No reviews yet — be the first to review this product.</p>
                @endforelse
            </div>

            @if ($relatedProducts->isNotEmpty())
                <h2 class="font-display text-xl text-[#1F2421] mt-10 mb-4">More in {{ $product->category->name }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($relatedProducts as $related)
                        <a href="{{ route('storefront.show', $related) }}" class="bg-white rounded-lg p-4 hover:border-[#1B4332]/30 border border-gray-100">
                            <div class="aspect-square rounded-lg mb-2 overflow-hidden">
                                @if ($related->image_path)
                                    <img src="{{ asset('storage/' . $related->image_path) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-white text-xl font-display" style="background: linear-gradient(135deg, #1B4332, #2D6A4F);">
                                        {{ strtoupper(substr($related->name, 0, 2)) }}
                                    </div>
                                @endif
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
