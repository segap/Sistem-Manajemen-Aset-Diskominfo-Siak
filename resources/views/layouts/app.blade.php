<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMIVA - Sistem Informasi Manajemen Aset')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="min-h-screen bg-[#f4f7fb]">
        <x-sidebar />

        <div class="min-h-screen lg:ml-[255px]">
            <x-navbar />

            @yield('content')
        </div>
    </div>
</body>
</html>
