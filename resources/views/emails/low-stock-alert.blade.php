<x-mail::message>
# {{ $type === 'realtime' ? 'Low Stock Alert' : 'Daily Low Stock Digest' }}

@if ($type === 'realtime')
**{{ $products->first()->name }}** (SKU: {{ $products->first()->sku }}) has dropped to **{{ $products->first()->stock_quantity }}** units, at or below its alert threshold of {{ $products->first()->low_stock_threshold }}.
@else
The following {{ $products->count() }} product(s) are at or below their restock threshold:
@endif

<x-mail::table>
| Product | SKU | Stock | Threshold |
|:--------|:----|:------|:----------|
@foreach ($products as $product)
| {{ $product->name }} | {{ $product->sku }} | {{ $product->stock_quantity }} | {{ $product->low_stock_threshold }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('products.index', ['low_stock' => 1])">
Review Inventory
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
