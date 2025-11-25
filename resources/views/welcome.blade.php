<x-layout>
    <div class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen selection:bg-indigo-500 selection:text-white">
        
        {{-- ヘッダー (ナビゲーション) --}}
        <nav class="flex items-center justify-between px-6 py-4 max-w-7xl mx-auto">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/Logo_icon.png') }}" alt="Logo" class="h-10 w-auto">
                <span class="text-xl font-bold tracking-tight hidden sm:block">Project-Lite</span>
            </div>
            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-500 transition">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-500 transition">Get Started</a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>

        {{-- メインコンテンツ --}}
        <main class="max-w-7xl mx-auto px-6 pt-16 pb-24 text-center lg:pt-32">
            
            {{-- 1. ヒーローセクション (テキストのみ) --}}
            <h1 class="mx-auto max-w-4xl font-display text-5xl font-medium tracking-tight text-slate-900 dark:text-white sm:text-7xl">
                Manage your projects 
                <span class="text-indigo-600 dark:text-indigo-400 relative whitespace-nowrap"><span class="relative">simply</span></span> 
                and 
                <span class="text-indigo-600 dark:text-indigo-400">fast.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg tracking-tight text-slate-700 dark:text-slate-300">
                Project-Lite brings the power of Kanban, Calendar, and Timeline views into one lightweight application.
            </p>
            <div class="mt-10 flex justify-center gap-x-6 mb-24">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all hover:scale-105">Go to Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all hover:scale-105">Start for free</a>
                    <a href="{{ route('login') }}" class="rounded-md bg-white dark:bg-gray-800 px-5 py-3 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">Log in →</a>
                @endauth
            </div>

            {{-- ★★★ 機能紹介セクション（交互配置） ★★★ --}}
            <div class="space-y-24 text-left">

                {{-- Section 1: Kanban Board (左テキスト・右画像) --}}
                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/2 order-2 md:order-1"> {{-- スマホはテキストが下 --}}
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" /></svg>
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Kanban Board</h2>
                        <p class="mt-4 text-lg leading-7 text-gray-600 dark:text-gray-400">
                            Visualize your workflow with intuitive drag-and-drop. Move tasks between lists, prioritize work, and track progress at a glance.
                        </p>
                    </div>
                    <div class="md:w-1/2 order-1 md:order-2"> {{-- スマホは画像が上 --}}
                        {{-- ダミー画像設定済み: 実際の画像名に変更してください (e.g., screenshot-kanban.png) --}}
                        <img src="{{ asset('images/screenshot-kanban.png') }}" 
                             onerror="this.src='https://placehold.co/800x500/e2e8f0/475569?text=Kanban+Screenshot'"
                             alt="Kanban Board Screenshot" class="rounded-xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-white/10 w-full h-auto transform transition-all hover:scale-[1.02]">
                    </div>
                </div>

                {{-- Section 2: Calendar View (左画像・右テキスト) --}}
                <div class="flex flex-col md:flex-row-reverse items-center gap-12"> {{-- row-reverse で左右反転 --}}
                    <div class="md:w-1/2 order-2 md:order-1">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Calendar View</h2>
                        <p class="mt-4 text-lg leading-7 text-gray-600 dark:text-gray-400">
                            Never miss a deadline. Switch to the monthly calendar view to see all your due dates clearly lined up.
                        </p>
                    </div>
                    <div class="md:w-1/2 order-1 md:order-2">
                        <img src="{{ asset('images/screenshot-calendar.png') }}" 
                             onerror="this.src='https://placehold.co/800x500/e2e8f0/475569?text=Calendar+Screenshot'"
                             alt="Calendar View Screenshot" class="rounded-xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-white/10 w-full h-auto transform transition-all hover:scale-[1.02]">
                    </div>
                </div>

                {{-- Section 3: Timeline View (左テキスト・右画像) --}}
                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/2 order-2 md:order-1">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Timeline View</h2>
                        <p class="mt-4 text-lg leading-7 text-gray-600 dark:text-gray-400">
                            Plan your week effectively. The weekly timeline view helps you manage task durations and identify bottlenecks in your schedule.
                        </p>
                    </div>
                    <div class="md:w-1/2 order-1 md:order-2">
                        <img src="{{ asset('images/screenshot-timeline.png') }}" 
                             onerror="this.src='https://placehold.co/800x500/e2e8f0/475569?text=Timeline+Screenshot'"
                             alt="Timeline View Screenshot" class="rounded-xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-white/10 w-full h-auto transform transition-all hover:scale-[1.02]">
                    </div>
                </div>

                {{-- Section 4: Powerful Task Details (左画像・右テキスト) --}}
                <div class="flex flex-col md:flex-row-reverse items-center gap-12">
                    <div class="md:w-1/2 order-2 md:order-1">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 011.875 1.875v12.75a1.875 1.875 0 01-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V6.375a1.875 1.875 0 011.875-1.875z" /></svg>
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Powerful Task Details</h2>
                        <p class="mt-4 text-lg leading-7 text-gray-600 dark:text-gray-400">
                            Dive into details. Add checklists, due dates, labels, attachments, and comments. Keep everything related to a task in one place.
                        </p>
                    </div>
                    <div class="md:w-1/2 order-1 md:order-2">
                        <img src="{{ asset('images/screenshot-modal.png') }}" 
                             onerror="this.src='https://placehold.co/800x500/e2e8f0/475569?text=Card+Modal+Screenshot'"
                             alt="Card Modal Screenshot" class="rounded-xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-white/10 w-full h-auto transform transition-all hover:scale-[1.02]">
                    </div>
                </div>

            </div>
            {{-- ★★★ 機能紹介セクションここまで ★★★ --}}

        </main>

        {{-- フッター --}}
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-12">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <p class="text-sm leading-5 text-gray-500 dark:text-gray-400">
                    Built with Laravel, Tailwind CSS, and Alpine.js.<br class="sm:hidden">
                    &copy; {{ date('Y') }} Project-Lite.
                </p>
            </div>
        </footer>
    </div>
</x-layout>