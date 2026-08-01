<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Welcome back, {{ $vendor->business_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- About SokoTrack --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-display text-lg text-[#1B4332] mb-2">About SokoTrack</h3>
                <p class="text-gray-600 leading-relaxed">
                    SokoTrack is your wholesale ordering platform for everyday retail stock - beverages,
                    snacks, cleaning supplies, stationery, cosmetics, and dairy - sourced and delivered
                    reliably across Kenya. Order online, pay by M-Pesa, bank transfer, or approved credit
                    terms, and earn loyalty points on every purchase toward better pricing and priority stock.
                </p>
            </div>

            {{-- Account snapshot --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Your Tier</p>
                    <p class="font-display text-2xl text-[#1B4332] mt-1">{{ $vendor->tier?->name ?? 'Bronze' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $vendor->loyalty_points }} loyalty points</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Available Credit</p>
                    <p class="font-display text-2xl text-[#1B4332] mt-1">KES {{ number_format($vendor->availableCredit(), 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">of KES {{ number_format($vendor->credit_limit, 0) }} limit</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Outstanding Balance</p>
                    <p class="font-display text-2xl text-[#1B4332] mt-1">KES {{ number_format($vendor->outstanding_balance, 0) }}</p>
                </div>
            </div>

            {{-- CTA to shop --}}
            <a href="{{ route('storefront.index') }}" class="block bg-[#1B4332] hover:bg-[#163829] text-white rounded-lg p-8 text-center transition-colors">
                <p class="font-display text-2xl">Browse Products →</p>
                <p class="text-white/70 text-sm mt-1">Restock your shop from our full catalog</p>
            </a>

            {{-- Recent orders --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="font-display text-lg text-[#1F2421] p-6 pb-0">Recent Orders</h3>
                @forelse ($recentOrders as $order)
                    <div class="px-6 py-4 border-t flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900">{{ $order->order_number }}</p>
                            <p class="text-xs text-gray-400">{{ $order->created_at->format('M j, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono-price text-[#1B4332]">KES {{ number_format($order->total_amount, 2) }}</p>
                            <span class="text-xs text-gray-500 capitalize">{{ $order->status }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 px-6 py-8 text-center">No orders yet — start shopping above.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>