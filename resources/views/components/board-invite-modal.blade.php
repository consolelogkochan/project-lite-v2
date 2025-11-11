

{{-- 
    モーダルの表示/非表示は、親コンポーネント（show.blade.php）の
    x-data="{ showInviteModal: false }" によって制御されます。
--}}
<div
    x-show="showInviteModal" {{-- 1. showInviteModal が true の時だけ表示 --}}
    @keydown.escape.window="showInviteModal = false" {{-- 2. Escapeキーで閉じる --}}
    x-cloak {{-- 3. ちらつき防止 --}}
    class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
>
    {{-- オーバーレイ（背景） --}}
    <div 
        x-show="showInviteModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showInviteModal = false" {{-- 4. 背景クリックで閉じる --}}
        class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"
        aria-hidden="true"
    ></div>

    {{-- モーダル本体 --}}
    <div 
        x-show="showInviteModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop {{-- 5. モーダル本体クリックで閉じないように --}}
        class="relative z-10 w-full max-w-2xl overflow-y-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl"
        style="max-height: 90vh;"
    >
        {{-- モーダルヘッダー --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Invite to board "{{ $board->title }}"
                </h2>
                <button 
                    @click="showInviteModal = false"
                    class="p-2 text-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
        </div>

        {{-- モーダルメインコンテンツ --}}
        {{-- ★ 1. 招待機能用の x-data を追加 --}}
        <div 
            x-data='{
                searchQuery: "",
                searchResults: [],
                isLoading: false,
                boardMembers: [], // ★ 1. メンバー一覧を保持する配列を追加
                myId: {{ Auth::id() }}, // ★ 1. ログイン中ユーザーのID
                myRole: "guest",      // ★ 2. 自分の役割 (初期値)

                // ★ 2. モーダル初期化時にメンバーを読み込む
                initModal() {
                    this.loadMembers();
                },

                // ★ 3. メンバー一覧を読み込むメソッド
                loadMembers() {
                    fetch("{{ route('boards.getMembers', $board) }}")
                        .then(response => response.json())
                        .then(data => {
                            this.boardMembers = data;
                            // ★ 3. 読み込んだメンバーから自分の役割をセット
                            const me = this.boardMembers.find(m => m.id === this.myId);
                            if (me) {
                                this.myRole = me.pivot.role;
                            }
                        })
                        .catch(error => console.error("Error loading members:", error));
                },
                
                searchUsers() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    this.isLoading = true;
                    
                    fetch("{{ route('boards.searchUsers', $board) }}?q=" + encodeURIComponent(this.searchQuery))
                        .then(response => response.json())
                        .then(data => {
                            this.searchResults = data;
                        })
                        .catch(error => console.error("Error searching users:", error))
                        .finally(() => this.isLoading = false);
                },

                inviteUser(user) {
                    // 1. APIを呼び出す
                    fetch("{{ route('boards.inviteUser', $board) }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            // ★ 修正: シングルクォートをエスケープされたダブルクォートに変更
                            "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            user_id: user.id,
                            role: "member" // ★ "member" (ダブルクォート)
                        })
                    })
                    .then(response => {
                        if (response.status === 422) { // バリデーションエラー
                            // ★ 修正: シングルクォートをダブルクォートに変更
                            alert("This user is already a member of the board.");
                            throw new Error("Validation failed");
                        }
                        if (!response.ok) {
                            // ★ 修正: シングルクォートをダブルクォートに変更
                            throw new Error("Failed to invite user.");
                        }
                        return response.json();
                    })
                    .then(invitedUser => {
                        // ★ 2. 成功時の処理 ★
                        
                        // a. 検索結果から招待したユーザーを削除
                        this.searchResults = this.searchResults.filter(u => u.id !== invitedUser.id);
                        this.searchQuery = "";
                        
                        // b. ★ 修正: APIが "pivot" 付きで返すため、応急処置を削除
                        // invitedUser.pivot = { role: "member" }; // ← 削除
                        this.boardMembers.push(invitedUser); // ★
                    })
                    .catch(error => {
                        console.error("Error inviting user:", error);
                        // ★ 修正: シングルクォートをダブルクォートに変更
                        if (error.message !== "Validation failed") {
                            alert("An error occurred while inviting the user.");
                        }
                    });
                },

                updateRole(member, newRole) {
                    // (自分自身の役割を変更しようとした場合は何もしない)
                    if (member.id === this.myId) {
                        alert("You cannot change your own role.");
                        // (UIを元の値に戻す)
                        event.target.value = member.pivot.role; 
                        return;
                    }

                    fetch(`/boards/{{ $board->id }}/members/${member.id}`, {
                        method: "PATCH",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            role: newRole
                        })
                    })
                    .then(response => {
                        if (response.status === 403) { // 403 Forbidden (非管理者)
                            throw new Error("Only admins can change roles.");
                        }
                        if (!response.ok) {
                            throw new Error("Failed to update role.");
                        }
                        return response.json();
                    })
                    .then(updatedMember => {
                        // ★ 成功時の処理 ★
                        // boardMembers 配列内の該当メンバーの pivot.role を更新
                        const index = this.boardMembers.findIndex(m => m.id === updatedMember.id);
                        if (index > -1) {
                            this.boardMembers[index].pivot.role = updatedMember.pivot.role;
                        }
                        alert("Role for " + updatedMember.name + " updated to " + updatedMember.pivot.role);
                    })
                    .catch(error => {
                        console.error("Error updating role:", error);
                        alert(error.message || "An error occurred.");
                        // エラー時はUIを元の値に戻す
                        event.target.value = member.pivot.role; 
                    });
                },

                removeMember(member) {
                    // (自分自身は退出させられない)
                    if (member.id === this.myId) {
                        // ★ 修正: シングルクォートをダブルクォートに変更
                        alert("You cannot remove yourself from the board. Use the \"Leave board\" option.");
                        return;
                    }
                    
                    if (!confirm("Are you sure you want to remove " + member.name + " from this board?")) {
                        return;
                    }

                    fetch(`/boards/{{ $board->id }}/members/${member.id}`, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                            "Accept": "application/json"
                        }
                    })
                    .then(response => {
                        if (response.status === 403) { 
                            return response.json().then(error => { throw new Error(error.message); });
                        }
                        if (!response.ok) { 
                            throw new Error("Failed to remove member.");
                        }
                        
                        // ★ 成功時の処理 (イミュータブルな更新)
                        this.boardMembers = this.boardMembers.filter(m => m.id !== member.id);
                        alert(member.name + " has been removed from the board.");
                    })
                    .catch(error => {
                        console.error("Error removing member:", error);
                        alert(error.message || "An error occurred.");
                    });
                },

                leaveBoard() {
                    // (API側でオーナーは拒否されるが、念のためUIでも確認)
                    if (this.myRole === "admin") {
                        alert("The board owner cannot leave the board.");
                        return;
                    }

                    if (!confirm("Are you sure you want to leave this board?")) {
                        return;
                    }

                    fetch("{{ route('boards.leave', $board) }}", {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                            "Accept": "application/json"
                        }
                    })
                    .then(response => {
                        if (response.status === 403) { // 403 Forbidden (オーナーの場合)
                            return response.json().then(error => { throw new Error(error.message); });
                        }
                        if (!response.ok) { // 204 No Content も .ok は true
                            throw new Error("Failed to leave the board.");
                        }
                        
                        // ★ 成功時の処理
                        alert("You have left the board.");
                        // ダッシュボードにリダイレクト
                        window.location.href = "{{ route('dashboard') }}";
                    })
                    .catch(error => {
                        console.error("Error leaving board:", error);
                        alert(error.message || "An error occurred.");
                    });
                }
            }'
            x-init="initModal()" {{-- ★ 5. x-init を追加 --}}
            class="p-6 space-y-6"
        >
            {{-- ★ 2. メールアドレスでの招待 (検索) --}}
            <div>
                <label for="invite-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invite by email or name</label>
                <div class="mt-1 flex space-x-2">
                    <input 
                        type="text" 
                        id="invite-search" 
                        x-model="searchQuery"
                        {{-- ★ 3. 300ms 待ってから searchUsers() を呼び出す --}}
                        @keyup.debounce.300ms="searchUsers()"
                        class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., user@example.com or Taro Yamada">
                </div>
                
                {{-- 検索結果の表示 --}}
                <div class="mt-3 space-y-2 max-h-40 overflow-y-auto">
                    {{-- ローディング --}}
                    <div x-show="isLoading" class="text-sm text-gray-500 dark:text-gray-400">Searching...</div>
                    
                    {{-- 結果一覧 --}}
                    <template x-for="user in searchResults" :key="user.id">
                        <div class="flex items-center justify-between p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center space-x-3">
                                {{-- アバター (Userモデルの 'avatar_url' アクセサを利用) --}}
                                <template x-if="user.avatar_url">
                                    <img class="h-8 w-8 rounded-full object-cover" :src="user.avatar_url" :alt="user.name">
                                </template>
                                <template x-if="!user.avatar_url">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 dark:bg-gray-600">
                                        <span x-text="user.name.split(' ').map(name => name[0]).join('').substring(0, 2).toUpperCase()"
                                              class="text-sm font-medium leading-none text-white"></span>
                                    </span>
                                </template>
                                {{-- 名前とEmail --}}
                                <div>
                                    <span classs="text-sm font-semibold text-gray-900 dark:text-white" x-text="user.name"></span>
                                    <span classs="block text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></span>
                                </div>
                            </div>
                            
                            {{-- 招待ボタン --}}
                            <button 
                                @click.prevent="inviteUser(user)"
                                class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Invite
                            </button>
                        </div>
                    </template>
                    
                    {{-- 結果なし --}}
                    <div x-show="!isLoading && searchQuery.length >= 2 && searchResults.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                        No users found.
                    </div>
                </div>
            </div>

            <p class="text-gray-700 dark:text-gray-300 mt-4">(TODO: リンクでの招待)</p>
            {{-- ★★★ ここからメンバー一覧UI ★★★ --}}
            <div class="mt-6">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Board Members</h4>
                <div class="mt-3 space-y-3 max-h-48 overflow-y-auto">
                    
                    <template x-for="member in boardMembers" :key="member.id">
                        <div class="flex items-center justify-between p-2 rounded-md">
                            {{-- ユーザー情報 --}}
                            <div class="flex items-center space-x-3">
                                {{-- アバター --}}
                                <template x-if="member.avatar_url">
                                    <img class="h-8 w-8 rounded-full object-cover" :src="member.avatar_url" :alt="member.name">
                                </template>
                                <template x-if="!member.avatar_url">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 dark:bg-gray-600">
                                        <span x-text="member.name.split(' ').map(name => name[0]).join('').substring(0, 2).toUpperCase()"
                                              class="text-sm font-medium leading-none text-white"></span>
                                    </span>
                                </template>
                                {{-- 名前とEmail --}}
                                <div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="member.name"></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="member.email"></span>
                                </div>
                            </div>
                            
                            {{-- 役割 (Role) と操作ボタン --}}
                            <div class="flex items-center space-x-2">
                                
                                {{-- ★★★ 役割変更ドロップダウン ★★★ --}}
                                <select 
                                    {{-- 1. 変更時に updateRole を呼び出す --}}
                                    @change="updateRole(member, $event.target.value)"
                                    {{-- 2. 管理者(admin)以外、または自分自身の場合は操作不可 --}}
                                    :disabled="myRole !== 'admin' || member.id === myId"
                                    class="text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500
                                           disabled:opacity-70 disabled:border-gray-300 dark:disabled:border-gray-600 disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed">
                                    
                                    {{-- 3. member.pivot.role の値に応じて選択状態を動的に設定 --}}
                                    <option value="admin" :selected="member.pivot.role === 'admin'">Admin</option>
                                    <option value="member" :selected="member.pivot.role === 'member'">Member</option>
                                    <option value="guest" :selected="member.pivot.role === 'guest'">Guest</option>
                                </select>
                                
                                {{-- 退出ボタン --}}
                                <button 
                                    {{-- 1. 管理者(admin)のみ表示 --}}
                                    x-show="myRole === 'admin'"
                                    {{-- 2. ボードオーナーには非表示 (オーナーは削除不可のため) --}}
                                    x-show="member.pivot.role !== 'admin'"
                                    {{-- 3. 自分自身にも非表示 (退出は別ボタン) --}}
                                    x-show="member.id !== myId"
                                    @click.prevent="removeMember(member)"
                                    class="p-1 text-gray-400 rounded-md hover:text-red-600 dark:hover:text-red-400 focus:outline-none"
                                    title="Remove member">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                </div>
                {{-- ★★★ ここから退出ボタンを追加 ★★★ --}}
                {{-- 管理者(オーナー)以外に表示 --}}
                <div x-show="myRole !== 'admin'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        @click.prevent="leaveBoard()"
                        class="w-full text-left text-sm p-2 rounded-md font-medium text-red-800 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50">
                        Leave board...
                    </button>
                </div>
                {{-- ★★★ 退出ボタンここまで ★★★ --}}
            </div>
            {{-- ★★★ メンバー一覧UIここまで ★★★ --}}

        </div>

    </div>
</div>