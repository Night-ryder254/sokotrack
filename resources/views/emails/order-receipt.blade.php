<x-mail::message>
# Order Confirmed

Hi {{ $order->vendor->business_name }},

Thanks for your order! Here's your receipt.

**Order:** {{ $order->order_number }}
**Date:** {{ $order->created_at->format('d M Y, g:i A') }}
**Payment method:** {{ str($order->payment_method)->replace('_', ' ')->title() }}

<x-mail::table>
| Product | Qty | Unit Price | Subtotal |
|:--------|:---:|:----------:|:--------:|
@foreach ($order->items as $item)
| {{ $item->product->name ?? 'Product' }} | {{ $item->quantity }} | KES {{ number_format($item->unit_price, 2) }} | KES {{ number_format($item->unit_price * $item->quantity, 2) }} |
@endforeach
</x-mail::table>

**Total: KES {{ number_format($order->total_amount, 2) }}**

@if ($order->payment_method === 'credit')
This order was placed on credit and added to your outstanding balance.
@elseif ($order->payment)
Payment status: {{ ucfirst($order->payment->status) }}
@endif

<x-mail::button :url="route('checkout.success', $order)">
View Order
</x-mail::button>

Thanks for choosing SokoTrack,<br>
{{ config('app.name') }}
</x-mail::message>
