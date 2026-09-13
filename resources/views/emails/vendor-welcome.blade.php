<x-mail::message>
# Welcome to SokoTrack, {{ $vendor->business_name }}!

Your vendor account is ready. You can now browse the catalog, place orders, and start earning loyalty points on every purchase.

**Getting started:**
- Browse products and add items to your cart
- Choose M-Pesa, bank transfer, or credit at checkout
- Earn 1 loyalty point for every KES 100 spent
- Track your orders and credit balance from your Dashboard

<x-mail::button :url="route('storefront.index')">
Start Shopping
</x-mail::button>

Welcome aboard,<br>
{{ config('app.name') }}
</x-mail::message>
