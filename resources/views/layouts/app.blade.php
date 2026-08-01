<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
        <style>
            .font-display { font-family: 'Fraunces', serif; }
            .font-mono-price { font-family: 'IBM Plex Mono', monospace; }
             /* Hide native number input spinners across browsers */
            input[type="number"]::-webkit-inner-spin-button,
            input[type="number"]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            input[type="number"] {
                -moz-appearance: textfield;
            }
            
             @media print {
                nav, .no-print { display: none !important; }
                body { background: white !important; }
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            async function addToCart(event, url) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                // Update the cart button in the header without reloading
                const cartBadge = document.getElementById('cart-badge');
                if (cartBadge) {
                    cartBadge.innerHTML = data.cartCount > 0
                        ? `${data.cartCount} · KES ${Number(data.cartTotal).toLocaleString(undefined, {minimumFractionDigits: 0})}`
                        : '';
                }

                // Show a brief inline confirmation next to the button that was clicked
                const feedback = form.querySelector('.add-feedback');
                if (feedback) {
                    feedback.textContent = '✓ Added';
                    feedback.classList.remove('opacity-0');
                    setTimeout(() => feedback.classList.add('opacity-0'), 1500);
                }
            }
        </script>
    </body>
</html>
