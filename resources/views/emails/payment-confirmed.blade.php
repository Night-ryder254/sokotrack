<x-mail::message>
# Payment Confirmed

Hi {{ $order->vendor->business_name }},

Your payment for order **{{ $order->order_number }}** has been confirmed.

**Amount:** KES {{ number_format($order->total_amount, 2) }}
**Method:** {{ str($order->payment_method)->replace('_', ' ')->title() }}
@if ($order->payment?->bank_reference)
**Reference:** {{ $order->payment->bank_reference }}
@endif

<x-mail::button :url="route('checkout.success', $order)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
