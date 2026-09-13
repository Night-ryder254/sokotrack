<x-mail::message>
# New Order Received

**{{ $order->vendor->business_name }}** just placed an order.

**Order:** {{ $order->order_number }}
**Total:** KES {{ number_format($order->total_amount, 2) }}
**Payment method:** {{ str($order->payment_method)->replace('_', ' ')->title() }}

<x-mail::table>
| Product | Qty |
|:--------|:---:|
@foreach ($order->items as $item)
| {{ $item->product->name ?? 'Product' }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('admin.orders.show', $order)">
Review Order
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
