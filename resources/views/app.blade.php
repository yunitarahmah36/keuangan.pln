<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Keuangan PLN</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

    <!-- Top Loading Indicator -->
    @include('layouts.loading-bar')

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" class="sidebar-mobile-backdrop"></div>

    <div class="app-layout-wrapper">
        <!-- Komponen Sidebar di sebelah kiri -->
        @include('layouts.sidebar')

        <!-- Kontainer Konten Utama & Topbar -->
        <div class="app-main-wrapper">
            <!-- Komponen Sticky Topbar -->
            @include('layouts.topbar')

            <!-- Viewport Konten SPA -->
            <main class="spa-content-viewport">
                @hasSection('content')
                    @yield('content')
                @else
                    <!-- Fitur 1: Dashboard -->
                    @include('pages.dashboard.index')

                    <!-- Fitur 2: Laporan Keuangan Bulanan -->
                    @include('pages.laporan-keuangan-bulanan.index')
                @endif
            </main>
        </div>
    </div>
</body>
</html>
