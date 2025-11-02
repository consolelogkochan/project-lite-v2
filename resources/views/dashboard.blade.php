<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ▼▼▼ このブロックを丸ごと追加 ▼▼▼ --}}
            <div class="mb-8">
                <div class="flex items-center space-x-2">
                    {{-- ユーザーアイコン（仮） --}}
                    <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        Your Boards
                    </h2>
                </div>
            </div>

            {{-- ▼▼▼ ボード一覧グリッド（次のステップで中身を実装） ▼▼▼ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                {{-- ▼▼▼ ループ処理を追加 ▼▼▼ --}}
                @foreach ($boards as $board)
                    <a href="{{ route('boards.show', $board) }}" class="block h-32 rounded-md p-4 bg-cover bg-center shadow-md transition-all duration-200 ease-in-out hover:shadow-lg hover:-translate-y-1" style="background-color: {{ $board->background_color ?? '#6366F1' }};">
                        <h3 class="font-bold text-lg text-white">{{ $board->title }}</h3>
                    </a>
                @endforeach
                {{-- ▲▲▲ ループ処理ここまで ▲▲▲ --}}

                {{-- ▼▼▼ @clickの処理を変更 ▼▼▼ --}}
                <div @click="$dispatch('open-create-board-modal')" class="bg-gray-200 hover:bg-gray-300 rounded-md p-4 h-32 flex items-center justify-center cursor-pointer transition-all duration-200 ease-in-out hover:shadow-lg hover:-translate-y-1">
                    <span class="text-gray-500 font-medium">Create new board...</span>
                </div>
            </div>
            {{-- ▲▲▲ 追加ブロックここまで ▲▲▲ --}}
        </div>
        {{-- ▼▼▼ モーダルから :showプロパティを削除 ▼▼▼ --}}
        <x-board-create-modal />
    </div>
</x-app-layout>
