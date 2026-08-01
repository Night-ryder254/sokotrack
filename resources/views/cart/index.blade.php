<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Your Cart</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse ($items as $item)
                    <div class="flex justify-between items-center px-6 py-4 border-b">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item['product']->name }}</p>
                            <p class="text-sm text-gray-500">{{ $item['quantity'] }} × KES {{ number_format($item['product']->price, 2) }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <p class="font-semibold text-gray-900">KES {{ number_format($item['subtotal'], 2) }}</p>
                            <form action="{{ route('cart.remove', $item['product']) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 text-sm hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 px-6 py-8 text-center">Your cart is empty.</p>
                @endforelse
            </div>

            @if ($items->isNotEmpty())
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-6 flex justify-between items-center">
                    <p class="text-lg font-bold">Total: KES {{ number_format($total, 2) }}</p>
                    <a href="{{ route('storefront.index') }}" class="px-4 py-2 rounded-lg border text-sm">Continue Shopping</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
