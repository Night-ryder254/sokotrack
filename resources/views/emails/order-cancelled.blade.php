<x-mail::message>
# Order Cancelled

Hi {{ $order->vendor->business_name }},

Your order **{{ $order->order_number }}** (KES {{ number_format($order->total_amount, 2) }}) has been cancelled.

@if ($order->payment_method === 'credit')
The amount has been removed from your outstanding balance.
@endif

If this wasn't expected, please reach out via WhatsApp and we'll help sort it out.

{{ config('app.name') }}
</x-mail::message>
