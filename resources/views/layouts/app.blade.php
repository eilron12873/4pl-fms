<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '4PL FMS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <!-- Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- Flowbite UI Components -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Barcode Scanner -->
        <script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>

        <!-- QR Code Scanner -->
        <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

        <!-- Alpine.js Cloak -->
        <style>
            [x-cloak] { display: none !important; }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="flex min-h-screen">
            @include('layouts.sidebar')

            <div id="main-layout" class="flex-1 flex flex-col min-h-screen ml-64">
                @include('layouts.navigation')

                @isset($header)
                    <header class="bg-white shadow">
                        <div class="px-4 sm:px-6 lg:px-8 py-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1">
                    <div class="px-4 sm:px-6 lg:px-8 py-6">
                        @if (session('success'))
                            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-800 rounded flex justify-between items-center" role="alert">
                                <span>{{ session('success') }}</span>
                                <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()" aria-label="Dismiss">×</button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded flex justify-between items-center" role="alert">
                                <span>{{ session('error') }}</span>
                                <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()" aria-label="Dismiss">×</button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="mt-2 text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                                    {{ __('Dismiss') }}
                                </button>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const mainLayout = document.getElementById('main-layout');
                const toggleBtn = document.getElementById('sidebar-toggle');

                if (!sidebar || !mainLayout || !toggleBtn) {
                    return;
                }

                let sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === '1';

                function applySidebarState() {
                    if (sidebarCollapsed) {
                        sidebar.classList.add('hidden');
                        mainLayout.classList.remove('ml-64');
                    } else {
                        sidebar.classList.remove('hidden');
                        if (!mainLayout.classList.contains('ml-64')) {
                            mainLayout.classList.add('ml-64');
                        }
                    }
                }

                applySidebarState();

                toggleBtn.addEventListener('click', function () {
                    sidebarCollapsed = !sidebarCollapsed;
                    localStorage.setItem('sidebar-collapsed', sidebarCollapsed ? '1' : '0');
                    applySidebarState();
                });
            });
        </script>
    </body>
</html>

