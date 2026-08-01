<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-2xl text-[#1F2421] mb-6">Your Cart</h1>

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            <div class="bg-white rounded-lg overflow-hidden border border-gray-100">
                @forelse ($items as $item)
                    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item['product']->name }}</p>
                            <p class="text-sm text-gray-500 font-mono-price">{{ $item['quantity'] }} × KES {{ number_format($item['product']->price, 2) }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <p class="font-mono-price font-semibold text-[#1B4332]">KES {{ number_format($item['subtotal'], 2) }}</p>
                            <form action="{{ route('cart.remove', $item['product']) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 text-sm hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 px-6 py-12 text-center">Your cart is empty.</p>
                @endforelse
            </div>

            @if ($items->isNotEmpty())
                <div class="mt-6 bg-white rounded-lg border border-gray-100 p-6 flex justify-between items-center">
                    <p class="font-display text-xl text-[#1F2421]">Total: <span class="font-mono-price text-[#1B4332]">KES {{ number_format($total, 2) }}</span></p>
                    <div class="flex gap-3">
                        <a href="{{ route('storefront.index') }}" class="px-4 py-2 rounded-lg border text-sm">Continue Shopping</a>
                        <a href="{{ route('checkout.index') }}" class="bg-[#1B4332] text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>