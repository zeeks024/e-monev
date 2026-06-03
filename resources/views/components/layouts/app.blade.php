<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logobna.png') }}">

        <title>{{ $title ?? 'E-Monev KIP Banjarnegara' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-gray-100">
        <div class="min-h-screen">
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="max-w-screen-xl mx-auto px-6 md:px-20">
                    <div class="flex justify-between items-center py-3">
                        <a href="/" class="flex items-center space-x-2">
                            <img src="/images/logobna.png" alt="Logo E-Monev" class="h-10 w-auto">
                            <span class="text-xl font-bold text-gray-800">E-Monev KIP</span>
                        </a>
                        <nav class="hidden md:flex items-center space-x-2 lg:space-x-4">
                            <a href="{{ route('user.dashboard') }}" class="px-4 py-2 rounded-md {{ request()->routeIs('user.dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-blue-600' }}">Beranda</a>
                            <a href="{{ route('kuesioner') }}" class="px-4 py-2 rounded-md {{ request()->routeIs('kuesioner') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-blue-600' }}">Kuesioner</a>
                            <a href="{{ route('notifikasi') }}" class="px-4 py-2 rounded-md {{ request()->routeIs('notifikasi') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-blue-600' }} relative">
                                Notifikasi
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                            </a>
                            <a href="{{ route('logout.confirm') }}" wire:navigate class="px-4 py-2 text-gray-600 hover:text-blue-600 rounded-md">Keluar</a>
                        </nav>
                    </div>
                </div>
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
