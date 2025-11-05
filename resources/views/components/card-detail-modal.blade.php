<div
    x-show="selectedCardId !== null"
    @keydown.escape.window="selectedCardId = null; selectedCardData = null" {{-- 修正: 閉じる時にデータもクリア --}}
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
>
    {{-- オーバーレイ（背景） --}}
    <div 
        x-show="selectedCardId !== null"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="selectedCardId = null; selectedCardData = null" {{-- 修正: 閉じる時にデータもクリア --}}
        class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"
        aria-hidden="true"
    ></div>

    {{-- モーダル本体 --}}
    <div 
        x-show="selectedCardId !== null"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative z-10 w-full max-w-3xl overflow-y-auto bg-gray-100 dark:bg-gray-800 rounded-lg shadow-xl"
        style="max-height: 90vh;"
    >
        {{-- ★★★ ここから中身を変更 ★★★ --}}

        {{-- 1. ローディングスピナー (データ取得中) --}}
        <div x-show="!selectedCardData" class="flex justify-center items-center h-64">
            <svg class="animate-spin h-8 w-8 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- 2. モーダルコンテンツ (データ取得完了後) --}}
        <div x-show="selectedCardData" x-cloak>
            
            {{-- モーダルヘッダー --}}
            <div class="px-6 py-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    {{-- ヘッダー左側 (リスト名) --}}
                    <div>
                        {{-- (アイコンは後で追加) --}}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            In list 
                            {{-- selectedCardData がロードされてから .list.title を参照する --}}
                            <span x-text="selectedCardData ? selectedCardData.list.title : '...'" class="font-medium underline"></span>
                        </p>
                    </div>

                    {{-- ヘッダー右側 (閉じるボタン) --}}
                    <button 
                        @click="selectedCardId = null; selectedCardData = null" {{-- 修正: 閉じる時にデータもクリア --}}
                        class="p-2 text-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
            </div>

            {{-- モーダルメインコンテンツ (2カラムレイアウト) --}}
            <div class="p-6 grid grid-cols-3 gap-6">

                {{-- ★★★ メインコンテンツ (左側 / 2カラム分) ★★★ --}}
                <div class="col-span-3 lg:col-span-2 space-y-6">

                    {{-- ★★★ カードタイトルセクション ★★★ --}}
                    <div class="flex items-start space-x-3">
                        {{-- アイコン --}}
                        <div class="flex-shrink-0 pt-1">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"></path></svg>
                        </div>
                        <div class="flex-grow min-w-0">
                            {{-- タイトル表示 (非編集時) --}}
                            <div x-show="!editingCardTitleModal">
                                <h2 @click="editingCardTitleModal = true; editedCardTitleModal = selectedCardData.title; $nextTick(() => $refs.modalCardTitleInput.focus())"
                                    x-text="selectedCardData ? selectedCardData.title : ''"
                                    class="text-2xl font-bold text-gray-900 dark:text-white cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md p-2 -m-2">
                                </h2>
                            </div>
                            {{-- タイトル編集 (編集時) --}}
                            <div x-show="editingCardTitleModal" x-cloak>
                                <form @submit.prevent="$dispatch('submit-card-title-update')">
                                    <textarea x-ref="modalCardTitleInput"
                                            x-model="editedCardTitleModal"
                                            @keydown.enter.prevent="$event.target.form.requestSubmit()"
                                            @keydown.escape.prevent="editingCardTitleModal = false"
                                            @blur="$dispatch('submit-card-title-update')"
                                            class="block w-full text-2xl font-bold rounded-md border-blue-500 shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            rows="2"></textarea>
                                </form>
                            </div>
                            {{-- (完了チェックボックス (UIのみ)) --}}
                            <div class="mt-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" disabled>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">(TODO: Mark as complete)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    {{-- ★★★ カードタイトルセクションここまで ★★★ --}}


                    {{-- ★★★ ここから説明セクション ★★★ --}}
                    <div class="flex items-start space-x-3">
                        {{-- アイコン --}}
                        <div class="flex-shrink-0 pt-1">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                        </div>

                        <div class="flex-grow min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Description</h3>

                            {{-- 説明文表示 (非編集時) --}}
                            <div x-show="!editingCardDescription">
                                {{-- 説明文が null または空の場合 --}}
                                <div x-show="!selectedCardData.description" x-cloak>
                                    <button 
                                        @click="editingCardDescription = true; editedCardDescription = ''; $nextTick(() => $refs.modalCardDescriptionInput.focus())"
                                        class="w-full text-left bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-md p-3 text-sm"
                                    >
                                        Add a more detailed description...
                                    </button>
                                </div>
                                {{-- 説明文が存在する場合 --}}
                                <div x-show="selectedCardData.description"
                                     @click="editingCardDescription = true; editedCardDescription = selectedCardData.description; $nextTick(() => $refs.modalCardDescriptionInput.focus())"
                                     x-html="selectedCardData.description.replace(/\n/g, '<br>')" {{-- 1. 改行を <br> に変換 --}}
                                     class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 cursor-pointer p-3 -m-3 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md">
                                    {{-- x-html を使うため、中身は空 --}}
                                </div>
                            </div>

                            {{-- 説明文編集 (編集時) --}}
                            <div x-show="editingCardDescription" x-cloak>
                                <form @submit.prevent="$dispatch('submit-card-description-update')">
                                    <textarea x-ref="modalCardDescriptionInput"
                                              x-model="editedCardDescription"
                                              class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                              rows="6"
                                              placeholder="Add a more detailed description..."></textarea>
                                    
                                    {{-- 保存・キャンセルボタン (次のステップで実装) --}}
                                    <div class="mt-2 space-x-2">
                                        <button type="submit"
                                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Save
                                        </button>
                                        <button @click="editingCardDescription = false"
                                                type="button"
                                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    {{-- ★★★ 説明セクションここまで ★★★ --}}
                    {{-- ★★★ ここからコメントセクション ★★★ --}}
                    <div class="flex items-start space-x-3">
                        {{-- アイコン --}}
                        <div class="flex-shrink-0 pt-1">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>

                        <div class="flex-grow min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activity</h3>
                            
                            {{-- コメント投稿フォーム --}}
                            {{-- (x-data でフォーム専用のスコープを作成) --}}
                            <div x-data="{ newComment: '' }" class="mt-4">
                                <form @submit.prevent="$dispatch('submit-new-comment', { content: newComment, card: selectedCardData, callback: () => newComment = '' })">
                                    <textarea x-model="newComment"
                                              rows="3"
                                              class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                              placeholder="Write a comment..."></textarea>
                                    <div class="mt-2">
                                        <button type="submit"
                                                x-bind:disabled="newComment.trim() === ''"
                                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                                       disabled:opacity-50 disabled:cursor-not-allowed">
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {{-- コメント一覧 --}}
                            <div class="mt-6 space-y-6">
                                {{-- コメントループ --}}
                                <template x-for="comment in selectedCardData.comments" :key="comment.id">
                                    <div class="flex items-start space-x-3">
                                        {{-- アバター (プロフィール画像 or イニシャル) --}}
                                        <div class="flex-shrink-0">
                                            
                                            {{-- 1. avatar_url が存在する場合 (<img> を表示) --}}
                                            <template x-if="comment.user.avatar_url">
                                                <img class="h-8 w-8 rounded-full object-cover" 
                                                     :src="comment.user.avatar_url" {{-- ★ Storage::url() が生成したパスをそのまま使用 --}}
                                                     :alt="comment.user.name">
                                            </template>

                                            {{-- 2. avatar_url が存在しない場合 (イニシャルを表示) --}}
                                            <template x-if="!comment.user.avatar_url">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 dark:bg-gray-600">
                                                    <span 
                                                        x-text="comment.user.name.split(' ').map(name => name[0]).join('').substring(0, 2).toUpperCase()"
                                                        class="text-sm font-medium leading-none text-white">
                                                    </span>
                                                </span>
                                            </template>

                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <div>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="comment.user.name"></span>
                                                <span 
                                                    x-text="new Date(comment.created_at).toLocaleString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })"
                                                    class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                </span>
                                            </div>
                                            {{-- コメント本文 (非編集時) --}}
                                            <div x-show="editingCommentId !== comment.id"
                                                 class="mt-1 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 rounded-md p-3 border border-gray-200 dark:border-gray-700">
                                                <p x-text="comment.content"></p>
                                            </div>

                                            {{-- コメント編集フォーム (編集時) --}}
                                            <div x-show="editingCommentId === comment.id" x-cloak>
                                                <form @submit.prevent="$dispatch('submit-edit-comment', { comment: comment, content: editedCommentContent, callback: () => { editingCommentId = null; editedCommentContent = ''; } })"
                                                      class="mt-1">
                                                    <textarea x-model="editedCommentContent"
                                                              rows="3"
                                                              class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                                              placeholder="Edit comment..."></textarea>
                                                    <div class="mt-2 space-x-2">
                                                        <button type="submit"
                                                                x-bind:disabled="editedCommentContent.trim() === '' || editedCommentContent.trim() === comment.content"
                                                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                                                       disabled:opacity-50 disabled:cursor-not-allowed">
                                                            Save
                                                        </button>
                                                        <button @click="editingCommentId = null; editedCommentContent = ''"
                                                                type="button"
                                                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            
                                            {{-- 編集・削除ボタン (非編集時のみ表示) --}}
                                            <div x-show="editingCommentId !== comment.id" 
                                                 class="mt-1 flex items-center space-x-2">
                                                
                                                {{-- Edit ボタン --}}
                                                <button 
                                                    x-show="comment.user_id === {{ Auth::id() }}"
                                                    @click.prevent="editingCommentId = comment.id; editedCommentContent = comment.content"
                                                    class="text-xs text-gray-500 dark:text-gray-400 hover:underline focus:outline-none">
                                                    Edit
                                                </button>

                                                {{-- Delete ボタン --}}
                                                <button 
                                                    x-show="comment.user_id === {{ Auth::id() }}"
                                                    @click.prevent="$dispatch('submit-delete-comment', { comment: comment, card: selectedCardData })"
                                                    class="text-xs text-gray-500 dark:text-gray-400 hover:underline focus:outline-none">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- コメントがない場合の表示 --}}
                                <div x-show="selectedCardData && selectedCardData.comments.length === 0" x-cloak
                                     class="text-gray-500 dark:text-gray-400 text-sm">
                                    No comments yet.
                                </div>
                            </div>

                        </div>
                    </div>
                    {{-- ★★★ コメントセクションここまで ★★★ --}}
                
                </div>
                {{-- ★★★ メインコンテンツ (左側) ここまで ★★★ --}}


                {{-- ★★★ サイドバー (右側 / 1カラム分) ★★★ --}}
                <div class="col-span-3 lg:col-span-1 space-y-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Add to card</h3>
                    
                    {{-- アビリティボタン群 --}}
                    <div class="space-y-2">
                        {{-- Members --}}
                        <button type="button" class="w-full flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md px-3 py-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Members
                        </button>
                        {{-- Labels --}}
                        <button type="button" class="w-full flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md px-3 py-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h.01M17 3h.01M17 7h.01M17 11h.01M17 15h.01M7 11h.01M7 15h.01M3 21h18M3 17h18M3 13h18M3 9h18M3 5h18"></path></svg>
                            Labels
                        </button>
                        {{-- Checklist --}}
                        <button type="button" class="w-full flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md px-3 py-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Checklist
                        </button>
                        {{-- Dates (Due Date) --}}
                        <button type="button" class="w-full flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md px-3 py-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Dates
                        </button>
                        {{-- Attachment --}}
                        <button type="button" class="w-full flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md px-3 py-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.415a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            Attachment
                        </button>
                    </div>

                    {{-- (ここに将来「Power-Ups」や「Actions」ボタンが入る) --}}

                </div>
                {{-- ★★★ サイドバー (右側) ここまで ★★★ --}}

            </div>

        </div>
        {{-- ★★★ 変更ここまで ★★★ --}}

    </div>
</div>