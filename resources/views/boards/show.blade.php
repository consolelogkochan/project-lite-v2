<x-app-layout>
    {{-- (1) カンバンボード専用ヘッダー --}}
    <div class="bg-white dark:bg-gray-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    {{-- 編集可能なボードタイトル（仮） --}}
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $board->title }}</h1>

                    {{-- ビュー切替アイコン（仮） --}}
                    <div class="flex space-x-2">
                        <button class="p-2 rounded-md bg-indigo-100 text-indigo-600">
                            {{-- カンバンアイコン --}}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </button>
                        <button class="p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{-- カレンダーアイコン --}}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>
                        <button class="p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{-- タイムラインアイコン --}}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    {{-- 参加メンバーアバター（仮） --}}
                    <div class="flex -space-x-2">
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800" src="https://via.placeholder.com/150" alt="Member 1">
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800" src="https://via.placeholder.com/150" alt="Member 2">
                        <span class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 text-gray-700 items-center justify-center text-xs font-medium">+2</span>
                    </div>

                    {{-- フィルターアイコン（仮） --}}
                    <button class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    </button>
                    
                    {{-- 招待ボタン（仮） --}}
                    <x-primary-button>
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Invite
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    {{-- (2) カンバンボード本体 --}}
    <div class="p-4 sm:p-6 lg:p-8 h-full flex-grow overflow-x-auto">
        <div class="flex space-x-4 h-full">
            {{-- ▼▼▼ リスト一覧のループ ▼▼▼ --}}
            @foreach ($board->lists as $list)
                <div class="flex-shrink-0 w-72 bg-gray-100 dark:bg-gray-700 rounded-md shadow-md">
                    {{-- リストヘッダー --}}
                    <div class="p-3 flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $list->title }}</h3>
                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                        </button>
                    </div>

                    {{-- カード一覧 --}}
                    <div class="p-3 space-y-3 overflow-y-auto" style="max-height: calc(100vh - 250px);">
                        @foreach ($list->cards as $card)
                            <div class="bg-white dark:bg-gray-800 rounded-md shadow p-3">
                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ $card->title }}</p>
                            </div>
                        @endforeach

                        {{-- ▼▼▼ 「カードを追加」UI ▼▼▼ --}}
                        <div x-data="{ addingCard: false }" class="mt-2">
                            <button x-show="!addingCard" @click="addingCard = true; $nextTick(() => $refs.cardTitleInput.focus())"
                                    class="w-full p-2 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm font-medium text-left">
                                + Add a card
                            </button>
                            <form x-show="addingCard" @submit.prevent="alert('Card creation logic goes here!')" class="space-y-2">
                                <textarea x-ref="cardTitleInput" rows="3" placeholder="Enter a title for this card..."
                                        class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"></textarea>
                                <div class="flex items-center space-x-2">
                                    <x-primary-button type="submit">Add card</x-primary-button>
                                    <button @click="addingCard = false" type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                        {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}
                        {{-- ここに「+ Add a card」ボタンが入ります --}}
                    </div>
                </div>
            @endforeach
            {{-- ▲▲▲ ループここまで ▲▲▲ --}}
            {{-- 「リストを追加」UI --}}
            <div x-data="{ addingList: false }" class="flex-shrink-0 w-72">
                {{-- 「+ Add another list」ボタン --}}
                <button x-show="!addingList" @click="addingList = true; $nextTick(() => $refs.listTitleInput.focus())"
                        class="w-full p-3 rounded-md bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 font-medium">
                    + Add another list
                </button>

                {{-- リスト作成フォーム（最初は非表示） --}}
                <form x-show="addingList" @submit.prevent="alert('List creation logic goes here!')" class="bg-gray-100 dark:bg-gray-700 rounded-md shadow-md p-3">
                    <input x-ref="listTitleInput" type="text" placeholder="Enter list title..."
                        class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <div class="mt-2 flex items-center space-x-2">
                        <x-primary-button type="submit">Add list</x-primary-button>
                        <button @click="addingList = false" type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ここに「+ Add another list」ボタンが入ります --}}
        </div>
    </div>
</x-app-layout>