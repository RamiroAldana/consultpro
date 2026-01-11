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

        <!-- Bootstrap (CDN) -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom admin styles -->
        <style>
            :root{--brand-blue:#0f4c81;--brand-yellow:#f6c84c;--brand-red:#e24b4b}
            .sidebar { width: 19rem; }
            .sidebar .nav-link { color: rgba(15,76,129,0.95); }
            .sidebar .nav-link:hover { background: rgba(15,76,129,0.06); }
            .brand { color: var(--brand-blue); font-weight:700 }
            .notif-badge { background: var(--brand-red); color: #fff }
            @media (max-width: 767.98px){ .sidebar{ position:fixed; z-index:1040; height:100vh; } }
            /* Dropdown adjustments to prevent overflow */
            .dropdown-menu { min-width: 10rem; max-width: 20rem; white-space: normal; word-break: break-word; }
            .navbar .dropdown { z-index: 2000; }
        </style>

        <!-- Scripts and app assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

    </head>
    <body class="font-sans antialiased bg-light">
        <div class="d-flex vh-100">

            <!-- Sidebar -->
            @include('layouts.sidebar')

            <div class="flex-grow-1 d-flex flex-column">
                <!-- Navbar -->
                @include('layouts.navbar')

                <!-- Optional header -->
                @if (isset($header))
                    <div class="bg-white shadow-sm py-3 px-4">
                        <div class="container-fluid">
                            {{ $header }}
                        </div>
                    </div>
                @endif

                <!-- Main content -->
                <main class="flex-grow-1 overflow-auto bg-white p-4">
                    <div class="container-fluid">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <!-- Bootstrap bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Keep sidebar open by default; allow toggle and persist preference
            (function(){
                var sidebar = document.getElementById('sidebarMenu');
                var stored = localStorage.getItem('admin_sidebar_open');
                if(stored === null && sidebar){
                    // default open
                    sidebar.classList.add('show');
                } else if(stored === 'false' && sidebar){
                    sidebar.classList.remove('show');
                }

                var toggles = document.querySelectorAll('[data-bs-toggle="sidebar"]');
                toggles.forEach(function(btn){
                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        if(!sidebar) return;
                        sidebar.classList.toggle('show');
                        localStorage.setItem('admin_sidebar_open', sidebar.classList.contains('show'));
                    });
                });
            })();
        </script>
        @livewireScripts

    </body>
</html>
