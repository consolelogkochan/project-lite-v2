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
                {{-- ▼▼▼ ループ処理を修正 ▼▼▼ --}}
                @foreach ($boards as $board)
                    {{-- ★ 1. リンク(a) を div (relative, group) に変更 --}}
                    <div class="relative h-32 rounded-md p-4 bg-cover bg-center shadow-md transition-all duration-200 ease-in-out hover:shadow-lg hover:-translate-y-1 group" 
                         style="background-color: {{ $board->background_color ?? '#6366F1' }};">
                        
                        {{-- メインのリンクエリア --}}
                        <a href="{{ route('boards.show', $board) }}" class="absolute inset-0 z-0">
                            <span class="sr-only">Open board: {{ $board->title }}</span>
                        </a>
                        
                        <h3 class="font-bold text-lg text-white">{{ $board->title }}</h3>
                        
                        {{-- ★ 2. 削除用の非表示フォームを追加 --}}
                        {{-- (親の x-data を利用) --}}
                        <form x-ref="deleteForm{{ $board->id }}" 
                              action="{{ route('boards.destroy', $board) }}" 
                              method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>

                        {{-- ★ 3. 削除ボタンを追加 --}}
                        <button 
                            {{-- .prevent.stop で親(a)へのクリックを防ぐ --}}
                            {{-- Alpine.js の $refs を使ってフォームを submit する --}}
                            @click.prevent.stop="if (confirm('Are you sure you want to delete the board \'{{ addslashes($board->title) }}\'? This action cannot be undone and will delete all associated lists, cards, and labels.')) { $refs.deleteForm{{ $board->id }}.submit(); }"
                            class="absolute top-2 right-2 z-10 p-1 text-white opacity-0 group-hover:opacity-100 hover:bg-black/20 rounded-md transition-opacity"
                            title="Delete board">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                @endforeach
                {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}

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
