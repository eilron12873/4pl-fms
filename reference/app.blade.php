<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>
        
        @stack('styles')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] { display: none !important; }
            .custom-scrollbar::-webkit-scrollbar { height: 8px; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #9ca3af; border-radius: 4px; }
            .custom-scrollbar::-webkit-scrollbar-track { background-color: #f3f4f6; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex">
            @include('layouts.sidebar')

            <div class="flex-1 ml-64">
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
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </main>
                
                @stack('scripts')
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var sidebar = document.querySelector('aside.custom-scrollbar');
                if (!sidebar) return;

                var saved = window.localStorage.getItem('sidebarScrollTop');
                if (saved !== null) {
                    sidebar.scrollTop = parseInt(saved, 10) || 0;
                }

                sidebar.addEventListener('scroll', function () {
                    window.localStorage.setItem('sidebarScrollTop', sidebar.scrollTop);
                }, { passive: true });
            });
        </script>
    </body>
</html>
