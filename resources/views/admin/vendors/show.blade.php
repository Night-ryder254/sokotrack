<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
                {{ $vendor->business_name }}
            </h2>
            <a href="{{ route('admin.vendors.index') }}" class="text-sm text-[#1B4332] hover:underline">
                &larr; Back to vendors
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('success'))
            <div class="rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-[#C2622D]/10 border border-[#C2622D]/30 text-[#C2622D] px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Credit Health Score --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Credit Health Score</h3>
                <span @class([
                    'text-sm font-medium px-3 py-1 rounded-full',
                    'bg-[#1B4332]/10 text-[#1B4332]' => $healthScore['label'] === 'Low risk',
                    'bg-[#E8A93B]/20 text-[#8a5c14]' => $healthScore['label'] === 'Medium risk',
                    'bg-[#C2622D]/10 text-[#C2622D]' => $healthScore['label'] === 'High risk',
                ])>
                    {{ $healthScore['label'] }} · {{ $healthScore['score'] }}/100
                </span>
            </div>
            <p class="text-sm text-gray-600">{{ $healthScore['reason'] }}</p>

            @if ($healthScore['suggestion'])
                <div @class([
                    'mt-3 rounded-lg px-4 py-3 text-sm border',
                    'bg-[#1B4332]/5 border-[#1B4332]/20 text-[#1B4332]' => $healthScore['suggestion']['type'] === 'increase',
                    'bg-[#C2622D]/5 border-[#C2622D]/20 text-[#C2622D]' => $healthScore['suggestion']['type'] === 'decrease',
                ])>
                    <strong>Suggestion:</strong> {{ $healthScore['suggestion']['message'] }}
                    <span class="text-xs opacity-70">(Not automatic — review and apply below if you agree.)</span>
                </div>
            @endif
        </div>

        {{-- Profile + Edit --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Profile</h3>
                <p class="font-medium text-[#1F2421]">{{ $vendor->business_name }}</p>
                <p class="text-sm text-gray-600">{{ $vendor->phone }}</p>
                <p class="text-sm text-gray-600">{{ $vendor->user->email ?? '' }}</p>
                <p class="text-sm text-gray-600 mt-2">Loyalty points: <span class="font-mono-price">{{ number_format($vendor->loyalty_points) }}</span></p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">Edit Credit &amp; Tier</h3>
                <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Credit limit (KES)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $vendor->credit_limit) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                        @error('credit_limit') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tier</label>
                        <select name="vendor_tier_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                            <option value="">No tier</option>
                            @foreach (\App\Models\VendorTier::orderBy('min_points')->get() as $tier)
                                <option value="{{ $tier->id }}" @selected($vendor->vendor_tier_id === $tier->id)>
                                    {{ $tier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/90">
                        Save
                    </button>
                </form>
            </div>
        </div>

        {{-- Loyalty adjustment --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">Manual Loyalty Adjustment</h3>
            <form method="POST" action="{{ route('admin.vendors.loyalty-adjustment', $vendor) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Points change</label>
                    <input type="number" name="points_change" placeholder="e.g. 50 or -20"
                           class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                </div>
                <button type="submit" class="bg-white border border-[#1B4332] text-[#1B4332] text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/10">
                    Apply Adjustment
                </button>
            </form>
            @error('points_change') <p class="text-xs text-[#C2622D] mt-2">{{ $message }}</p> @enderror

            @if ($loyaltyTransactions->isNotEmpty())
                <div class="mt-4 border-t border-gray-100 pt-3 space-y-1">
                    @foreach ($loyaltyTransactions as $txn)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ str($txn->reason)->replace('_', ' ')->title() }} · {{ $txn->created_at->format('d M Y') }}</span>
                            <span @class(['font-mono-price', 'text-[#1B4332]' => $txn->points_change > 0, 'text-[#C2622D]' => $txn->points_change < 0])>
                                {{ $txn->points_change > 0 ? '+' : '' }}{{ $txn->points_change }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Order history --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <h3 class="text-xs font-semibold text-gray-500 uppercase px-5 pt-5 pb-3">Order History</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F7F9F6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-[#F7F9F6]">
                            <td class="px-4 py-3 text-sm font-medium text-[#1F2421]">
                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-[#1B4332]">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono-price text-[#1F2421]">KES {{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ str($order->payment_method)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'text-xs font-medium px-2.5 py-1 rounded-full',
                                    'bg-[#E8A93B]/20 text-[#8a5c14]' => $order->status === 'pending',
                                    'bg-[#1B4332]/10 text-[#1B4332]' => $order->status === 'confirmed',
                                    'bg-[#C2622D]/10 text-[#C2622D]' => $order->status === 'cancelled',
                                ])>
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
