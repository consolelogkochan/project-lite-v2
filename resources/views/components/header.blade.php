<header class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            {{-- 左側：ロゴ --}}
            <div class="flex-shrink-0">
                <a href="{{ route('dashboard') }}">
                    <img class="h-16 w-auto" src="{{ asset('images/Logo-full-2.png') }}" alt="Project-Lite Logo">
                </a>
            </div>

            {{-- 右側：アイコン群 --}}
            <div class="flex items-center space-x-4">

                {{-- ▼▼▼ このトグルボタンを追加 ▼▼▼ --}}
                <button id="theme-toggle-button" class="text-gray-400 hover:text-indigo-600">
                    <svg id="theme-toggle-dark-icon" class="h-6 w-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="h-6 w-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-15.66l-.707.707M4.05 19.95l-.707.707M21 12h-1M4 12H3m15.66 8.66l-.707-.707M4.05 4.05l-.707-.707M12 18a6 6 0 100-12 6 6 0 000 12z"></path></svg>
                </button>
                {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}

                {{-- 通知アイコン --}}
                <div class="relative" id="notification-component">
                    <button id="notification-button" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>
                    @if($unreadNotificationsCount > 0)
                        <span id="notification-badge" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white">{{ $unreadNotificationsCount }}</span>
                    @endif
                    <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 hidden">
                        {{-- 中身はJavaScriptで生成 --}}
                    </div>
                </div>

                {{-- ユーザーメニュー --}}
                <div class="relative" id="user-menu-component">
                    <button id="user-menu-button" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                        {{-- ★★★ ここから修正 ★★★ --}}
                        {{-- 1. Str::startsWith() で厳密にチェック --}}
                        @if (\Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'avatars/'))
                            {{-- アバター画像 --}}
                            <img class="h-8 w-8 rounded-full object-cover" src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                        @else
                            {{-- フォールバック: イニシャル (マルチバイト対応) --}}
                            <span class="inline-flex h-8 w-8 rounded-full bg-gray-500 items-center justify-center" title="{{ Auth::user()->name }}">
                                <span class="text-sm font-medium leading-none text-white">
                                    @php
                                        $name = trim(Auth::user()->name ?? '');
                                        $initial = mb_substr($name, 0, 1, 'UTF-8'); 
                                        $initials = mb_strtoupper($initial, 'UTF-8');
                                    @endphp
                                    {{ $initials }}
                                </span>
                            </span>
                        @endif
                        {{-- ★★★ 修正ここまで ★★★ --}}
                    </button>
                    <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Profile') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Log Out') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- 要素の取得 ---
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');
    const notificationButton = document.getElementById('notification-button');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationBadge = document.getElementById('notification-badge');
    const settingsModal = document.getElementById('notification-settings-modal');
    const closeSettingsModalButton = document.getElementById('close-notification-settings-modal');
    {{-- ▼▼▼ テーマ切替用の要素取得を追加 ▼▼▼ --}}
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');


    // --- 変数定義 ---
    let notifications = [];
    let filter = 'all';
    let unreadCount = {{ $unreadNotificationsCount }};

    // --- 関数定義 ---
    function updateBadge() {
        if (!notificationBadge) return;
        if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }

    function renderNotifications() {
        let filtered = notifications;
        if (filter === 'unread') {
            filtered = notifications.filter(n => n.read_at === null);
        }
        let content = `<div class="p-4 font-bold border-b flex justify-between items-center"><span>Notifications</span><div class="flex items-center space-x-2"><button data-filter="all" class="notification-filter-btn px-2 py-1 text-xs font-semibold rounded-md ${filter === 'all' ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-gray-100'}">All</button><button data-filter="unread" class="notification-filter-btn px-2 py-1 text-xs font-semibold rounded-md ${filter === 'unread' ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-gray-100'}">Unread</button><button id="notification-settings-button" title="Notification Settings" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></button></div></div><ul class="divide-y max-h-96 overflow-y-auto">`;
        if (filtered.length === 0) { content += `<li class="p-4 text-center text-sm text-gray-500">${filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.'}</li>`; } else { filtered.forEach(n => { content += `<li class="p-4 flex items-center justify-between hover:bg-gray-50"><a href="${n.data.url}" class="flex-grow"><p class="text-sm text-gray-700 ${n.read_at === null ? 'font-bold' : 'font-normal'}">${n.data.message}</p><p class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</p></a>${n.read_at === null ? `<button data-id="${n.id}" title="Mark as read" class="mark-as-read-btn ms-4 flex-shrink-0 w-3 h-3 bg-indigo-500 rounded-full hover:bg-indigo-700"></button>` : ''}</li>`; }); }
        content += `</ul>`;
        notificationDropdown.innerHTML = content;
    }

    // --- ▼▼▼ テーマ切替機能のロジックを追加 ▼▼▼ ---
    function updateThemeIcon() {
        if (document.documentElement.classList.contains('dark')) {
            lightIcon.classList.remove('hidden');
            darkIcon.classList.add('hidden');
        } else {
            lightIcon.classList.add('hidden');
            darkIcon.classList.remove('hidden');
        }
    }

    // ページ読み込み時にアイコンを初期化
    updateThemeIcon();

    // トグルボタンのクリックイベント
    themeToggleButton.addEventListener('click', function() {
        // 1. <html>タグのクラスを「手動で」トグルする
        const isDark = document.documentElement.classList.toggle('dark');
        // 2. localStorageに保存する
        localStorage.theme = isDark ? 'dark' : 'light';
        // 3. アイコンを更新する
        updateThemeIcon();
    });
    // --- ▲▲▲ 追加ここまで ▲▲▲ ---

    // --- イベントリスナー ---

    // ユーザーメニューボタンのクリック
    if (userMenuButton) {
        userMenuButton.addEventListener('click', function (event) {
            event.stopPropagation();
            if(notificationDropdown) notificationDropdown.classList.add('hidden');
            if(userMenuDropdown) userMenuDropdown.classList.toggle('hidden');
        });
    }

    // 通知ボタンのクリック
    if (notificationButton) {
        notificationButton.addEventListener('click', function (event) {
            event.stopPropagation();
            if(userMenuDropdown) userMenuDropdown.classList.add('hidden');
            const isHidden = notificationDropdown.classList.toggle('hidden');
            if (!isHidden) {
                fetch('{{ route('notifications.index') }}')
                    .then(response => response.json())
                    .then(data => {
                        notifications = data;
                        renderNotifications();
                    });
            }
        });
    }

    // ドキュメント全体のクリック
    document.addEventListener('click', function (event) {
        const target = event.target;

        // ユーザーメニューの外側クリック
        if (userMenuDropdown && !userMenuDropdown.classList.contains('hidden') && !userMenuButton.contains(target)) {
            userMenuDropdown.classList.add('hidden');
        }

        // ▼▼▼ ここからが重要な修正点 ▼▼▼
        // 通知関連エリア（ボタン、ドロップダウン、設定モーダル）の内側かどうかを判定
        const isClickInsideNotificationArea = (notificationButton && notificationButton.contains(target)) ||
                                            (notificationDropdown && notificationDropdown.contains(target)) ||
                                            (settingsModal && settingsModal.contains(target));

        // 通知ドロップダウンが開いていて、かつ通知関連エリアの外側がクリックされたら閉じる
        if (notificationDropdown && !notificationDropdown.classList.contains('hidden') && !isClickInsideNotificationArea) {
            notificationDropdown.classList.add('hidden');
        }
        // ▲▲▲ 修正ここまで ▲▲▲
        
        // フィルターボタンのクリック
        if (target.closest('.notification-filter-btn')) {
            filter = target.closest('.notification-filter-btn').dataset.filter;
            renderNotifications();
        }

        // 既読ボタンのクリック
        if (target.closest('.mark-as-read-btn')) {
            const id = target.closest('.mark-as-read-btn').dataset.id;
            fetch(`/notifications/${id}`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }})
                .then(response => {
                    if (response.ok) {
                        const notification = notifications.find(n => n.id === id);
                        if (notification) notification.read_at = new Date().toISOString();
                        unreadCount = Math.max(0, unreadCount - 1);
                        updateBadge();
                        renderNotifications();
                    }
                });
        }
        
        // 設定ボタンのクリック
        if (target.closest('#notification-settings-button')) {
            if(settingsModal) {
                settingsModal.classList.remove('hidden');
                settingsModal.classList.add('flex');
            }
        }
    });
    
    // 通知設定モーダルを閉じる機能
    if (settingsModal) {
        const closeModal = () => {
            settingsModal.classList.add('hidden');
            settingsModal.classList.remove('flex');
        };
        closeSettingsModalButton.addEventListener('click', closeModal);
        settingsModal.addEventListener('click', event => {
            if (event.target === settingsModal) {
                closeModal();
            }
        });
    }
});
</script>