<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Key metrics --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Today's Revenue</p>
                    <p class="font-display text-2xl text-[#1B4332] mt-1">KES {{ number_format($todayRevenue, 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $todayOrderCount }} orders today</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Outstanding Credit</p>
                    <p class="font-display text-2xl text-[#C2622D] mt-1">KES {{ number_format($totalOutstandingCredit, 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Across all vendors on credit terms</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Low Stock Items</p>
                    <p class="font-display text-2xl text-[#C2622D] mt-1">{{ $lowStockProducts->count() }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="underline">Review inventory →</a>
                    </p>
                </div>
            </div>

            {{-- Low stock alert list --}}
            @if ($lowStockProducts->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <h3 class="font-semibold text-gray-800 p-6 pb-0">⚠️ Needs Restocking</h3>
                    @foreach ($lowStockProducts as $product)
                        <div class="px-6 py-3 border-t flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400">{{ $product->sku }}</p>
                            </div>
                            <span class="text-sm font-medium text-[#C2622D]">
                                {{ $product->stock_quantity }} left (alert at {{ $product->low_stock_threshold }})
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Recent orders across all vendors --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="font-semibold text-gray-800 p-6 pb-0">Recent Orders</h3>
                @forelse ($recentOrders as $order)
                    <div class="px-6 py-4 border-t flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900">{{ $order->vendor->business_name }}</p>
                            <p class="text-xs text-gray-400">{{ $order->order_number }} · {{ $order->created_at->format('M j, g:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono-price text-[#1B4332]">KES {{ number_format($order->total_amount, 2) }}</p>
                            <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 px-6 py-8 text-center">No orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
