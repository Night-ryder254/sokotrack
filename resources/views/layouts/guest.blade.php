<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SokoTrack') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:600,700|inter:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-12 bg-[#F7F9F6]">

            <a href="/" class="mb-8">
                <x-application-logo class="h-12 w-auto" />
            </a>

            <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-8 sm:px-8">
                {{ $slot }}
            </div>

            <p class="text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} SokoTrack. Nairobi, Kenya.</p>
        </div>
    </body>
</html>
