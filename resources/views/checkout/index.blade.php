<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-2xl text-[#1F2421] mb-6">Checkout</h1>

            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            <div class="bg-white rounded-lg border border-gray-100 p-6 mb-6">
                <h2 class="font-display text-lg text-[#1F2421] mb-4">Order Summary</h2>
                @foreach ($items as $item)
                    <div class="flex justify-between py-2 text-sm">
                        <span>{{ $item['quantity'] }} × {{ $item['product']->name }}</span>
                        <span class="font-mono-price">KES {{ number_format($item['subtotal'], 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between pt-3 mt-3 border-t font-semibold">
                    <span>Total</span>
                    <span class="font-mono-price text-[#1B4332]">KES {{ number_format($total, 2) }}</span>
                </div>
            </div>

            <form action="{{ route('checkout.store') }}" method="POST" class="bg-white rounded-lg border border-gray-100 p-6">
                @csrf
                <h2 class="font-display text-lg text-[#1F2421] mb-4">Payment Method</h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 border rounded-lg p-4 cursor-pointer hover:border-[#1B4332]">
                        <input type="radio" name="payment_method" value="mpesa" checked class="text-[#1B4332]">
                        <div>
                            <p class="font-medium">M-Pesa</p>
                            <p class="text-xs text-gray-400">Pay instantly via STK push</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 border rounded-lg p-4 cursor-pointer hover:border-[#1B4332]">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-[#1B4332]">
                        <div>
                            <p class="font-medium">Bank Transfer</p>
                            <p class="text-xs text-gray-400">Pay via bank, confirmed manually</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 border rounded-lg p-4 cursor-pointer hover:border-[#1B4332]">
                        <input type="radio" name="payment_method" value="credit" class="text-[#1B4332]">
                        <div>
                            <p class="font-medium">Credit Terms</p>
                            <p class="text-xs text-gray-400">
                                Available: KES {{ number_format($vendor->availableCredit(), 2) }}
                            </p>
                        </div>
                    </label>
                </div>

                <button type="submit" class="w-full mt-6 bg-[#1B4332] text-white px-6 py-3 rounded-lg font-medium hover:bg-[#163829]">
                    Place Order
                </button>
            </form>
        </div>
    </div>
</x-app-layout>