<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex justify-end gap-3 mb-4 no-print">
                <button onclick="window.print()" class="bg-[#1B4332] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#163829]">
                    🖨️ Print Receipt
                </button>
                <a href="{{ route('storefront.index') }}" class="px-4 py-2 rounded-lg border text-sm">Continue Shopping</a>
            </div>

            <div class="bg-white rounded-lg border border-gray-100 p-10">

                {{-- Company header --}}
                <div class="flex justify-between items-start border-b pb-6">
                    <div>
                        <h1 class="font-display text-2xl text-[#1B4332]">SokoTrack Distributors Ltd</h1>
                        <p class="text-sm text-gray-500 mt-1">Enterprise Road, Industrial Area, Nairobi</p>
                        <p class="text-sm text-gray-500">P.O. Box 12345-00100, Nairobi, Kenya</p>
                        <p class="text-sm text-gray-500">orders@sokotrack.co.ke · +254 714 540 101</p>
                        <p class="text-xs text-gray-400 mt-1">KRA PIN: P051234567X</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl">✓</p>
                        <p class="font-display text-lg text-[#1F2421]">Receipt</p>
                        <p class="text-xs text-gray-400 font-mono-price mt-1">{{ $order->order_number }}</p>
                    </div>
                </div>

                {{-- Billed to / order meta --}}
                <div class="grid grid-cols-2 gap-6 py-6 border-b text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Billed To</p>
                        <p class="font-medium text-gray-900">{{ $order->vendor->business_name }}</p>
                        <p class="text-gray-500">{{ $order->vendor->phone }}</p>
                        <p class="text-gray-500">{{ $order->vendor->user->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Order Details</p>
                        <p class="text-gray-700">{{ $order->created_at->format('M j, Y — g:i A') }}</p>
                        <p class="text-gray-500 capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</p>
                        @if ($order->payment?->mpesa_receipt_number)
                            <p class="text-gray-400 font-mono-price text-xs">{{ $order->payment->mpesa_receipt_number }}</p>
                        @endif
                    </div>
                </div>

                {{-- Line items --}}
                <div class="py-6">
                    <div class="flex justify-between text-xs text-gray-400 uppercase tracking-wide pb-2 border-b">
                        <span>Item</span>
                        <span>Amount</span>
                    </div>
                    @foreach ($order->items as $item)
                        <div class="flex justify-between py-2 text-sm border-b border-gray-50">
                            <span>{{ $item->quantity }} × {{ $item->product->name }}</span>
                            <span class="font-mono-price">KES {{ number_format($item->quantity * $item->unit_price, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between pt-4 mt-2 font-semibold text-lg">
                        <span>Total</span>
                        <span class="font-mono-price text-[#1B4332]">KES {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                <p class="text-xs text-gray-400 text-center pt-4 border-t">
                    Thank you for ordering with SokoTrack. Goods sold are subject to our standard terms and conditions.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>