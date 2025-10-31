<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project-Lite</title>
    <script src="https://cdn.tailwindcss.com"></script> {{-- ← @viteを消して、この行を追加 --}}
</head>
<body class="bg-slate-100 text-slate-800">
    {{-- ここに将来ヘッダーが入る --}}
    <header class="bg-white shadow-md p-4">
        <h1 class="text-xl font-bold">Project-Lite</h1>
    </header>

    <main class="p-8">
        {{ $slot }}
    </main>

    {{-- ここに将来フッターが入る --}}
    <footer class="text-center p-4 text-sm text-slate-500">
        <p>&copy; 2025 Project-Lite</p>
    </footer>
</body>
</html>