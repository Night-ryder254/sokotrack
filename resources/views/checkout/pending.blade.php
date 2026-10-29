<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white rounded-xl p-8 text-center"
                 x-data="mpesaStatusPoll({{ $order->id }}, @js($order->payment->checkout_request_id), @js($order->payment->status), @js($order->payment->result_desc))">

                <div x-show="status === 'pending'">
                    <div class="mx-auto h-16 w-16 rounded-full bg-[#E8A93B]/20 flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-[#E8A93B] animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl text-[#1F2421] mb-2">Check your phone</h1>
                    <p class="text-gray-600">
                        We've sent an M-Pesa prompt to <strong>{{ $order->vendor->phone }}</strong>. Enter your PIN to complete payment.
                    </p>
                    <p class="text-sm text-gray-400 mt-4" x-text="'Waiting... (' + elapsed + 's)'"></p>
                </div>

                <div x-show="status === 'confirmed'" x-cloak>
                    <div class="mx-auto h-16 w-16 rounded-full bg-[#1B4332]/10 flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-[#1B4332]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl text-[#1F2421] mb-2">Payment Confirmed!</h1>
                    <p class="text-gray-600 mb-6">Your order <strong>{{ $order->order_number }}</strong> has been paid successfully.</p>
                    <a :href="'/checkout/' + orderId + '/success'" class="inline-block bg-[#1B4332] text-white px-6 py-2.5 rounded-lg font-medium hover:bg-[#163829]">
                        View Receipt
                    </a>
                </div>

                <div x-show="status === 'failed'" x-cloak>
                    <div class="mx-auto h-16 w-16 rounded-full bg-[#C2622D]/10 flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-[#C2622D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl text-[#1F2421] mb-2">Payment Failed</h1>
                    <p class="text-gray-600 mb-1" x-text="resultDesc || 'The payment could not be completed.'"></p>
                    <p class="text-sm text-gray-400 mb-6">This order was cancelled and nothing was charged. You can place it again from the shop.</p>
                    <a href="{{ route('storefront.index') }}" class="inline-block bg-[#1B4332] text-white px-6 py-2.5 rounded-lg font-medium hover:bg-[#163829]">
                        Back to Shop
                    </a>
                </div>

                <div x-show="status === 'timeout'" x-cloak>
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl text-[#1F2421] mb-2">Still waiting...</h1>
                    <p class="text-gray-600 mb-2">This is taking longer than expected. If you completed the payment, it may take a moment to reflect, and this page will update by itself.</p>
                    <p class="text-sm text-gray-400 mb-6">If no payment arrives within 10 minutes, the order is cancelled automatically and nothing is charged.</p>
                    <a href="{{ route('dashboard') }}" class="inline-block bg-[#1B4332] text-white px-6 py-2.5 rounded-lg font-medium hover:bg-[#163829]">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mpesaStatusPoll(orderId, checkoutRequestId, initialStatus, initialDescription) {
            return {
                status: initialStatus || 'pending',
                resultDesc: initialDescription || null,
                orderId: orderId,
                elapsed: 0,
                pollTimer: null,
                elapsedTimer: null,

                init() {
                    // Opened again after the payment was already settled: nothing to wait for.
                    if (this.status !== 'pending') {
                        return;
                    }

                    this.elapsedTimer = setInterval(() => this.elapsed++, 1000);
                    this.poll();
                    this.pollTimer = setInterval(() => this.poll(), 3000);

                    // After 90s, just relabel the UI as "taking longer" but NEVER stop polling.
                    // A real person can take a while to find their phone and enter a PIN; we
                    // should keep catching the callback whenever it actually arrives.
                    setTimeout(() => {
                        if (this.status === 'pending') {
                            this.status = 'timeout';
                        }
                    }, 90000);
                },

                async poll() {
                    try {
                        const response = await fetch(`/api/mpesa/status?checkout_request_id=${checkoutRequestId}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await response.json();

                        if (data.status === 'confirmed') {
                            this.status = 'confirmed';
                            this.stop();
                        } else if (data.status === 'failed') {
                            this.status = 'failed';
                            this.resultDesc = data.result_desc;
                            this.stop();
                        }
                        // status === 'pending' -> keep polling, even past the 90s mark
                    } catch (e) {
                        console.error('Status poll failed', e);
                    }
                },

                stop() {
                    clearInterval(this.pollTimer);
                    clearInterval(this.elapsedTimer);
                },
            };
        }
    </script>
</x-app-layout>
