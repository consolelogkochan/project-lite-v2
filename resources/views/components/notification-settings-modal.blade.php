<div id="notification-settings-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md m-4">
        {{-- モーダルヘッダー --}}
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold">Notification Settings</h3>
            <button id="close-notification-settings-modal" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        {{-- モーダルボディ --}}
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Select when you want to receive notifications.</p>
            {{-- 仮のチェックリスト --}}
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                    <span class="ms-2 text-sm">When a new comment is added</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                    <span class="ms-2 text-sm">When a due date is approaching</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ms-2 text-sm">When a card is moved</span>
                </label>
            </div>
        </div>

        {{-- モーダルフッター --}}
        <div class="p-4 bg-gray-50 border-t text-right">
            <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-500">Save Changes</button>
        </div>
    </div>
</div>