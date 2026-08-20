<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
            Orders
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

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

        <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end bg-white rounded-xl border border-gray-200 p-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Payment method</label>
                <select name="payment_method" onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    <option value="">All methods</option>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(request('payment_method') === $method)>
                            {{ str($method)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if (request()->hasAny(['status', 'payment_method']))
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-[#1B4332] pb-2">
                    Clear filters
                </a>
            @endif
        </form>

        <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F7F9F6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3"></th>
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
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $order->vendor->business_name }}</td>
                            <td class="px-4 py-3 text-sm font-mono-price text-[#1F2421]">
                                KES {{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ str($order->payment_method)->replace('_', ' ')->title() }}
                                @if ($order->payment)
                                    <span @class([
                                        'ml-1 text-xs px-2 py-0.5 rounded-full',
                                        'bg-[#1B4332]/10 text-[#1B4332]' => $order->payment->status === 'confirmed',
                                        'bg-[#E8A93B]/20 text-[#8a5c14]' => $order->payment->status === 'pending',
                                        'bg-[#C2622D]/10 text-[#C2622D]' => $order->payment->status === 'failed',
                                    ])>
                                        {{ ucfirst($order->payment->status) }}
                                    </span>
                                @endif
                            </td>
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
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-[#1B4332] hover:underline font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('admin.orders.show', $order) }}"
                   class="block bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-medium text-[#1F2421]">{{ $order->order_number }}</span>
                        <span @class([
                            'text-xs font-medium px-2.5 py-1 rounded-full',
                            'bg-[#E8A93B]/20 text-[#8a5c14]' => $order->status === 'pending',
                            'bg-[#1B4332]/10 text-[#1B4332]' => $order->status === 'confirmed',
                            'bg-[#C2622D]/10 text-[#C2622D]' => $order->status === 'cancelled',
                        ])>
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">{{ $order->vendor->business_name }}</p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="font-mono-price text-sm text-[#1F2421]">
                            KES {{ number_format($order->total_amount, 2) }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</span>
                    </div>
                </a>
            @empty
                <div class="text-center text-sm text-gray-500 py-8">No orders found.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
