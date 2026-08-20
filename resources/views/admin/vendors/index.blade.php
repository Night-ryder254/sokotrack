<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
            Vendors
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Business name or phone"
                       class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tier</label>
                <select name="tier" class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    <option value="">All tiers</option>
                    @foreach ($tiers as $tier)
                        <option value="{{ $tier->id }}" @selected(request('tier') == $tier->id)>{{ $tier->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-[#1B4332] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/90">
                Filter
            </button>

            @if (request()->hasAny(['search', 'tier']))
                <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 hover:text-[#1B4332] pb-2">
                    Clear
                </a>
            @endif
        </form>

        <div class="space-y-3">
            @forelse ($vendors as $vendor)
                <a href="{{ route('admin.vendors.show', $vendor) }}"
                   class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-[#1B4332]/40 transition">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-[#1F2421]">{{ $vendor->business_name }}</h3>
                                @if ($vendor->tier)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-[#E8A93B]/20 text-[#8a5c14]">
                                        {{ $vendor->tier->name }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">{{ $vendor->phone }}</p>
                        </div>

                        <span @class([
                            'text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap',
                            'bg-[#1B4332]/10 text-[#1B4332]' => $vendor->healthScore['label'] === 'Low risk',
                            'bg-[#E8A93B]/20 text-[#8a5c14]' => $vendor->healthScore['label'] === 'Medium risk',
                            'bg-[#C2622D]/10 text-[#C2622D]' => $vendor->healthScore['label'] === 'High risk',
                        ])>
                            {{ $vendor->healthScore['label'] }} · {{ $vendor->healthScore['score'] }}/100
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Credit limit</p>
                            <p class="font-mono-price text-[#1F2421]">KES {{ number_format($vendor->credit_limit, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Outstanding</p>
                            <p class="font-mono-price text-[#1F2421]">KES {{ number_format($vendor->outstanding_balance, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Loyalty points</p>
                            <p class="font-mono-price text-[#1F2421]">{{ number_format($vendor->loyalty_points) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Orders</p>
                            <p class="font-mono-price text-[#1F2421]">{{ $vendor->total_orders_count }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center text-sm text-gray-500 py-8 bg-white rounded-xl border border-gray-200">
                    No vendors found.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $vendors->links() }}
        </div>
    </div>
</x-app-layout>
