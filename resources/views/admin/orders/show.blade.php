<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
                Order {{ $order->order_number }}
            </h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-[#1B4332] hover:underline">
                &larr; Back to orders
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-[#C2622D]/10 border border-[#C2622D]/30 text-[#C2622D] px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span @class([
                    'text-sm font-medium px-3 py-1 rounded-full',
                    'bg-[#E8A93B]/20 text-[#8a5c14]' => $order->status === 'pending',
                    'bg-[#1B4332]/10 text-[#1B4332]' => $order->status === 'confirmed',
                    'bg-[#C2622D]/10 text-[#C2622D]' => $order->status === 'cancelled',
                ])>
                    {{ ucfirst($order->status) }}
                </span>
                <span class="text-sm text-gray-500">Placed {{ $order->created_at->format('d M Y, g:i A') }}</span>
            </div>

            <div class="flex gap-2">
                @if ($order->payment_method === 'bank_transfer' && $order->payment?->status === 'pending' && $order->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.orders.confirm-payment', $order) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="bg-[#1B4332] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/90">
                            Confirm Bank Transfer
                        </button>
                    </form>
                @endif

                @if ($order->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}"
                          onsubmit="return confirm('Cancel this order? Stock and loyalty points will be reversed.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="bg-white border border-[#C2622D] text-[#C2622D] text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#C2622D]/10">
                            Cancel Order
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Billed to</h3>
                <p class="font-medium text-[#1F2421]">{{ $order->vendor->business_name }}</p>
                <p class="text-sm text-gray-600">{{ $order->vendor->phone }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Payment</h3>
                <p class="font-medium text-[#1F2421]">
                    {{ str($order->payment_method)->replace('_', ' ')->title() }}
                </p>
                @if ($order->payment)
                    <p class="text-sm text-gray-600">Status: {{ ucfirst($order->payment->status) }}</p>
                    @if ($order->payment->mpesa_receipt_number)
                        <p class="text-sm text-gray-600 font-mono-price">{{ $order->payment->mpesa_receipt_number }}</p>
                    @endif
                    @if ($order->payment->bank_reference)
                        <p class="text-sm text-gray-600 font-mono-price">Ref: {{ $order->payment->bank_reference }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F7F9F6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-[#1F2421]">{{ $item->product->name ?? 'Product removed' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono-price text-gray-700">
                                KES {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono-price text-[#1F2421]">
                                KES {{ number_format($item->unit_price * $item->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-[#F7F9F6]">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-sm font-semibold text-right text-[#1F2421]">Total</td>
                        <td class="px-4 py-3 text-right font-mono-price font-semibold text-[#1F2421]">
                            KES {{ number_format($order->total_amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
