<div id="notification-settings-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden items-center justify-center">
    {{-- モーダル内部のロジックを Alpine.js で管理 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md m-4"
         x-data="{
            notifyOnComment: {{ Auth::user()->notify_on_comment ? 'true' : 'false' }},
            notifyOnAttachment: {{ Auth::user()->notify_on_attachment ? 'true' : 'false' }},
            notifyOnDueDate: {{ Auth::user()->notify_on_due_date ? 'true' : 'false' }},
            notifyOnCardMove: {{ Auth::user()->notify_on_card_move ? 'true' : 'false' }},
            notifyOnCardCreated: {{ Auth::user()->notify_on_card_created ? 'true' : 'false' }},
            notifyOnCardDeleted: {{ Auth::user()->notify_on_card_deleted ? 'true' : 'false' }},
            isSaving: false,

            savePreferences() {
                this.isSaving = true;
                
                fetch('{{ route('notifications.updatePreferences') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        notify_on_comment: this.notifyOnComment,
                        notify_on_attachment: this.notifyOnAttachment,
                        notify_on_due_date: this.notifyOnDueDate,
                        notify_on_card_move: this.notifyOnCardMove,
                        notify_on_card_created: this.notifyOnCardCreated,
                        notify_on_card_deleted: this.notifyOnCardDeleted
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to update preferences');
                    return response.json();
                })
                .then(data => {
                    alert('Preferences saved successfully.');
                    // モーダルを閉じる (header.blade.php の script と連携)
                    document.getElementById('notification-settings-modal').click(); 
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving preferences.');
                })
                .finally(() => {
                    this.isSaving = false;
                });
            }
         }">
         
        {{-- モーダルヘッダー --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Notification Settings</h3>
            <button id="close-notification-settings-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none transition-colors">
                &times;
            </button>
        </div>

        {{-- モーダルボディ --}}
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Select when you want to receive notifications.</p>
            
            <div class="space-y-3">
                {{-- 1. コメント通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnComment" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a new comment is added</span>
                </label>

                {{-- ★ 追加: 添付ファイル通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnAttachment" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a file is attached</span>
                </label>

                {{-- 2. 期限通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnDueDate" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a due date is approaching</span>
                </label>

                {{-- 3. 移動通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnCardMove" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a card is moved</span>
                </label>

                {{-- 4. 作成通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnCardCreated" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a card is created</span>
                </label>

                {{-- 5. 削除通知 --}}
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="notifyOnCardDeleted" 
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">When a card is deleted</span>
                </label>
            </div>
        </div>

        {{-- モーダルフッター --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 text-right rounded-b-lg">
            <button @click="savePreferences" 
                    :disabled="isSaving"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-show="!isSaving">Save Changes</span>
                <span x-show="isSaving">Saving...</span>
            </button>
        </div>
    </div>
</div>