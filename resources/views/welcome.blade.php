<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- FOUC（チカチカ）防止スクリプト --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <title>Laravel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    {{-- ▼▼▼ ここからがテスト用のUI ▼▼▼ --}}
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center">

        {{-- トグルボタン --}}
        <button id="theme-toggle" type="button" class="absolute top-4 right-4 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm p-2.5">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm-7.071 0l-.707.707a1 1 0 001.414 1.414l.707-.707a1 1 0 10-1.414-1.414zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3 10a1 1 0 011-1h1a1 1 0 110 2H4a1 1 0 01-1-1zM16 10a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zM4.95 6.464l.707-.707a1 1 0 00-1.414-1.414l-.707.707a1 1 0 101.414 1.414zM15.05 6.464l-.707-.707a1 1 0 00-1.414 1.414l.707.707a1 1 0 001.414-1.414z"></path></svg>
        </button>

        <h1 class="text-4xl font-bold text-gray-800 dark:text-white">
            It works!
        </h1>
    </div>

    {{-- ▼▼▼ トグルボタン用のJavaScript ▼▼▼ --}}
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');

            function updateIcon() {
                if (document.documentElement.classList.contains('dark')) {
                    lightIcon.classList.remove('hidden');
                    darkIcon.classList.add('hidden');
                } else {
                    lightIcon.classList.add('hidden');
                    darkIcon.classList.remove('hidden');
                }
            }

            // 初期アイコン表示
            updateIcon();

            // ボタンクリックイベント
            themeToggleBtn.addEventListener('click', () => {
                // <html>のクラスをトグル
                const isDark = document.documentElement.classList.toggle('dark');
                // localStorageに保存
                localStorage.theme = isDark ? 'dark' : 'light';
                // アイコン更新
                updateIcon();
            });
        });
    </script>
</body>
</html>