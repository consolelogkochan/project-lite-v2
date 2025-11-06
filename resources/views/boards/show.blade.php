<x-app-layout>
    {{-- (1) カンバンボード専用ヘッダー (変更なし) --}}
    <div class="bg-white dark:bg-gray-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $board->title }}</h1>
                    {{-- (ビュー切替アイコンは変更なし) --}}
                    <div class="flex space-x-2">
                        <button class="p-2 rounded-md bg-indigo-100 text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </button>
                        <button class="p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </button>
                        <button class="p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex -space-x-2">
                        <img class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800" src="https://via.placeholder.com/150" alt="Member 1">
                        <img class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800" src="https://via.placeholder.com/150" alt="Member 2">
                        <span class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 text-gray-700 items-center justify-center text-xs font-medium">+2</span>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    </button>
                    <x-primary-button>
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Invite
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    {{-- (2) カンバンボード本体 --}}
    <div class="p-4 sm:p-6 lg:p-8 h-full flex-grow overflow-x-auto" >

        <div class="flex space-x-4 h-full" x-sortable
        @sortable-end.self="handleSortEnd($event.detail)"
        @card-sort-end.window="handleCardSortEnd($event.detail)" 
        @submit-card-title-update.window="updateCardTitleFromModal()"
        @submit-card-description-update.window="updateCardDescriptionFromModal()"
        @submit-new-comment.window="submitNewCommentFromModal($event.detail)"
        @submit-delete-comment.window="deleteCommentFromModal($event.detail)"
        @submit-edit-comment.window="updateCommentFromModal($event.detail)"
        @submit-card-dates.window="updateCardDatesFromModal($event.detail)"
        x-data='{
            lists: [],
            newCardForm: { // ★ 追加: カード追加フォームの状態
             listId: null, // どのリストに追加するか
             title: ""     // カードのタイトル
            },
            editingCardId: null, // ★ 追加: 編集中のカードID
            editedCardTitle: "", // ★ 追加: 編集中のカードタイトル
            selectedCardId: null, // 現在開いているカードのID
            selectedCardData: null, // APIから取得した詳細データ
            editingCardTitleModal: false, // モーダル内のタイトルを編集中か
            editedCardTitleModal: "", // モーダル内の編集用タイトル
            editingCardDescription: false, // 説明文を編集中か
            editedCardDescription: "", // 編集用の説明文
            editingCommentId: null, // 編集中のコメントID
            editedCommentContent: "", // 編集用のコメント本文
            addingList: false,
            newListTitle: "",
            editingListId: null,
            editedListTitle: "",

            init() {
                this.lists = @json($lists);
            },

            // ★ ここから追加: selectedCardId を監視
            watchSelectedCard() {
                this.$watch("selectedCardId", (newId) => {
                    // モーダルが開いた時 (IDがセットされた時)
                    if (newId !== null) {
                        // 1. まずデータをリセット (ローディング表示のため)
                        this.selectedCardData = null; 
                        
                        // 2. APIを叩いて詳細データを取得
                        fetch(`/cards/${newId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error("Failed to fetch card details.");
                                }
                                return response.json();
                            })
                            .then(data => {
                                // 取得したデータをセット
                                this.selectedCardData = data; 
                            })
                            .catch(error => {
                                console.error("Error fetching card details:", error);
                                alert("Failed to load card details. Please close the modal and try again.");
                                // エラー時はモーダルを閉じる
                                this.selectedCardId = null; 
                            });
                    } 
                    // モーダルが閉じた時 (newId が null になった時)
                    else {
                        // データをクリア
                        this.selectedCardData = null; 
                        // ★ ここから追加: 編集状態もリセット
                        this.editingCardTitleModal = false;
                        this.editedCardTitleModal = "";
                        this.editingCardDescription = false;
                        this.editedCardDescription = "";
                        this.editingCommentId = null;
                        this.editedCommentContent = "";
                    }
                });
            },
            // ★ 追加ここまで

            submitNewList() {
                fetch("{{ route('lists.store', $board) }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: this.newListTitle })
                })
                .then(response => {
                    if (!response.ok) throw new Error("Validation failed");
                    return response.json();
                })
                .then(newList => {
                    this.lists.push(newList);
                    this.newListTitle = "";
                    this.addingList = false;
                })
                .catch(error => console.error("Error:", error));
            },

            startEditingList(list) {
                this.editingListId = list.id;
                this.editedListTitle = list.title;
                this.$nextTick(() => {
                    document.getElementById(`list-title-input-${list.id}`).focus();
                });
            },

            updateListTitle(list) {
                if (this.editedListTitle.trim() === "" || this.editedListTitle === list.title) {
                    this.editingListId = null;
                    return;
                }
                fetch(`/lists/${list.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: this.editedListTitle })
                })
                .then(response => {
                    if (!response.ok) throw new Error("Validation failed");
                    return response.json();
                })
                .then(updatedList => {
                    const index = this.lists.findIndex(l => l.id === updatedList.id);
                    if (index !== -1) this.lists[index].title = updatedList.title;
                    this.editingListId = null;
                })
                .catch(error => {
                    console.error("Error:", error);
                    this.editingListId = null;
                });
            },

            deleteList(list) {
                if (!confirm(`Are you sure you want to delete the list "${list.title}"? This action cannot be undone.`)) return;

                fetch(`/lists/${list.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error("Failed to delete list.");
                    const index = this.lists.findIndex(l => l.id === list.id);
                    if (index !== -1) this.lists.splice(index, 1);
                })
                .catch(error => console.error("Error:", error));
            },

            handleSortEnd(event) {
                let items = Array.from(this.lists);
                let [movedItem] = items.splice(event.oldIndex, 1);
                items.splice(event.newIndex, 0, movedItem);

                this.lists = [];
                this.$nextTick(() => {
                    this.lists = items;
                });

                const newOrder = items.map((list, index) => {
                    list.order = index;
                    return list.id;
                });

                fetch("{{ route('lists.updateOrder') }}", {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({
                        orderedListIds: newOrder
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error("Failed to update list order.");
                })
                .catch(error => console.error("Error:", error));
            },

            // ★ ここからカードD&D用メソッドを追加
            initCardSortable(el) {
                // $el (this div) をSortableコンテナとして初期化
                // (window.Sortable を bootstrap.js で設定済み)
                new Sortable(el, {
                    group: "cards", // "cards" グループ内のリスト間でD&Dを許可
                    draggable: ".card-item", // .card-item クラスをドラッグ対象に
                    animation: 150,
                    onEnd: (event) => {
                        // ドラッグ終了時にカスタムイベントを発火
                        this.$dispatch("card-sort-end", {
                            cardId: event.item.dataset.cardId,
                            fromListId: event.from.dataset.listId,
                            toListId: event.to.dataset.listId,
                            newIndex: event.newIndex,
                            oldIndex: event.oldIndex
                        });
                    }
                });
            },

            handleCardSortEnd(detail) {
                // detail = { cardId: "1", fromListId: "1", toListId: "2", newIndex: 0, oldIndex: 0 }

                // 1. ローカルの Alpine.js データを即時更新 (UIの即時反映)

                const fromList = this.lists.find(list => list.id == detail.fromListId);
                const toList = this.lists.find(list => list.id == detail.toListId);
                
                if (!fromList || !toList) {
                    console.error("Could not find source or destination list");
                    return;
                }

                // 移動するカードのインデックスを oldIndex から特定
                // (SortableJSが渡す cardId から探すほうが確実)
                const cardIndex = fromList.cards.findIndex(card => card.id == detail.cardId);

                if (cardIndex === -1) {
                    console.error("Could not find card in source list");
                    // D&Dライブラリとデータの不整合が起きた場合は、
                    // サーバーからのデータで同期するためリロードを促す
                    alert("An error occurred. Please reload the page.");
                    return;
                }

                // fromList からカードを抜き取る
                const [movedCard] = fromList.cards.splice(cardIndex, 1);

                // toList の指定された位置 (newIndex) にカードを挿入
                toList.cards.splice(detail.newIndex, 0, movedCard);

                // ★★★ ここから挿入 (強制再描画ロジック) ★★★
                // SortableJSによるDOM変更とAlpineのリアクティビティを同期させる
                
                // 1. 現在の (splice後の) this.lists の完全なコピーを作成
                let updatedLists = Array.from(this.lists);
                
                // 2. this.lists を一度リセットして、Alpineに「変更」を強制通知
                this.lists = []; 
                
                // 3. $nextTick (DOM更新が完了した後) で、コピーした配列を再セット
                this.$nextTick(() => {
                    this.lists = updatedLists;
                });
                // ★★★ 挿入ここまで ★★★

                // 2. サーバーに新しい順序を送信 (API呼び出し)

                // APIが要求する形式 [{id: 1, cards: [1, 2]}, {id: 2, cards: [3]}] にデータを整形
                const listsPayload = updatedLists.map(list => {
                    return {
                        id: list.id,
                        // 各リストの cards 配列から card の id だけを抽出
                        cards: list.cards.map(card => card.id) 
                    };
                });

                fetch("{{ route('cards.updateOrder') }}", { 
                    method: "PATCH", 
                    headers: {
                        "Content-Type": "application/json", 
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({
                        lists: listsPayload 
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card order on server."); // "..."
                    }
                    return response.json();
                })
                .then(data => {
                    // 成功 (特にUIでやることはないが、ログは残す)
                    console.log("Card order updated successfully:", data.message);
                })
                .catch(error => {
                    console.error("Error updating card order:", error); // "..."
                    // ★ ユーザーにエラーを通知し、リロードを促す
                    // （ローカルのUIとDBのデータが不整合になっている可能性が高いため）
                    alert("An error occurred while saving the new order. Please reload the page to ensure data consistency.");
                });
            },
            // ★ カードD&D用メソッドここまで

            // ★ ここから追加 (文字列をダブルクォートに変更)
            submitNewCard(list) {
                if (this.newCardForm.title.trim() === "") {
                    // タイトルが空ならフォームを閉じるだけ
                    this.newCardForm.listId = null;
                    this.newCardForm.title = "";
                    return;
                }

                fetch(`/lists/${list.id}/cards`, {
                    method: "POST", // "..." に変更
                    headers: {
                        "Content-Type": "application/json", // "..." に変更
                        // "..." と \"...\" に変更
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content") 
                    },
                    body: JSON.stringify({
                        title: this.newCardForm.title
                    })
                })
                .then(response => {
                    if (response.status === 422) { // バリデーションエラー
                        alert("Card title is required or too long."); // "..." に変更
                        throw new Error("Validation failed"); // "..." に変更
                    }
                    if (!response.ok) {
                        throw new Error("Failed to create card."); // "..." に変更
                    }
                    return response.json();
                })
                .then(newCard => {
                    // ★ 成功時の処理
                    // 該当するリストの cards 配列に新しいカードを追加
                    list.cards.push(newCard); 

                    // フォームをリセット
                    this.newCardForm.listId = null;
                    this.newCardForm.title = ""; // "..." に変更
                })
                .catch(error => {
                    console.error("Error creating card:", error); // "..." に変更
                    // バリデーション失敗以外のエラー
                    if (error.message !== "Validation failed") { // "..." に変更
                        alert("An error occurred while adding the card."); // "..." に変更
                    }
                });
            },

            updateCardTitle(card) {
                const newTitle = this.editedCardTitle.trim();

                // タイトルが空、または変更がない場合は、編集をキャンセルするだけ
                if (newTitle === "" || newTitle === card.title) {
                    this.editingCardId = null;
                    this.editedCardTitle = "";
                    return;
                }

                fetch(`/cards/${card.id}`, {
                    method: "PATCH", // "..."
                    headers: {
                        "Content-Type": "application/json", // "..."
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: newTitle })
                })
                .then(response => {
                    if (response.status === 422) {
                        alert("Card title is required or too long."); // "..."
                        throw new Error("Validation failed"); // "..."
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update card."); // "..."
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理
                    // Alpine.js のリアクティブなデータを更新
                    card.title = updatedCard.title; 
                    
                    // 編集状態をリセット
                    this.editingCardId = null;
                    this.editedCardTitle = "";
                })
                .catch(error => {
                    console.error("Error updating card:", error);
                    if (error.message !== "Validation failed") {
                        alert("An error occurred while updating the card."); // "..."
                    }
                    // エラーが起きても編集フォームを閉じる
                    this.editingCardId = null;
                    this.editedCardTitle = "";
                });
            },

            updateCardDescriptionFromModal() {
                // 編集モードでないなら何もしない
                if (!this.editingCardDescription) return;

                const newDescription = this.editedCardDescription.trim();
                const card = this.selectedCardData;

                // 変更がない場合は、編集をキャンセルするだけ
                // (DBの description は null の可能性があるため、|| "" で空文字に統一して比較)
                if (newDescription === (card.description || "")) {
                    this.editingCardDescription = false;
                    return;
                }

                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ description: newDescription }) // description を送信
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card description.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    
                    // 1. モーダル内のデータを更新
                    this.selectedCardData.description = updatedCard.description; 
                    
                    // 2. メインボード（背景）のデータも更新 (同期のため)
                    // (※説明文はカンバンボード一覧には表示されないが、
                    // 将来的に使う可能性を考慮し同期させておく)
                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].description = updatedCard.description;
                        }
                    }
                    
                    // 3. 編集状態をリセット
                    this.editingCardDescription = false;
                })
                .catch(error => {
                    console.error("Error updating card description:", error);
                    alert("An error occurred while updating the card description.");
                    this.editingCardDescription = false;
                });
            },

            submitNewCommentFromModal(detail) {
                // detail = { content: "...", card: {...}, callback: () => {} }
                if (detail.content.trim() === "") return;

                fetch(`/cards/${detail.card.id}/comments`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ content: detail.content })
                })
                .then(response => {
                    if (response.status === 422) {
                        throw new Error("Comment content is required.");
                    }
                    if (!response.ok) {
                        throw new Error("Failed to post comment.");
                    }
                    return response.json();
                })
                .then(newComment => {
                    // ★ 成功時の処理 ★
                    
                    // 1. モーダル内のコメント配列の「先頭」に新しいコメントを追加
                    // (CardController@show が latest() で取得するため、UIも先頭に追加)
                    this.selectedCardData.comments.unshift(newComment);
                    
                    // 2. フォームをクリアする (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error posting comment:", error);
                    alert("An error occurred while posting the comment.");
                });
            },

            deleteCommentFromModal(detail) {
                // detail = { comment: {...}, card: {...} }
                if (!confirm("Are you sure you want to delete this comment?")) {
                    return;
                }

                fetch(`/comments/${detail.comment.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (response.status === 403) { // 403 Forbidden
                        throw new Error("You do not have permission to delete this comment.");
                    }
                    if (!response.ok) { // 204 No Content も .ok は true
                        throw new Error("Failed to delete comment.");
                    }
                    
                    // ★ 成功時の処理 ★
                    // モーダル内の comments 配列から該当コメントを削除
                    const index = this.selectedCardData.comments.findIndex(c => c.id === detail.comment.id);
                    if (index > -1) {
                        this.selectedCardData.comments.splice(index, 1);
                    }
                })
                .catch(error => {
                    console.error("Error deleting comment:", error);
                    alert(error.message || "An error occurred while deleting the comment.");
                });
            },

            updateCommentFromModal(detail) {
                // detail = { comment: {...}, content: "...", callback: () => {} }
                const newContent = detail.content.trim();
                
                // (ボタン側で disabled にしているが、念のため)
                if (newContent === "" || newContent === detail.comment.content) {
                    detail.callback(); // フォームを閉じる
                    return;
                }

                fetch(`/comments/${detail.comment.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ content: newContent })
                })
                .then(response => {
                    if (response.status === 403) {
                        throw new Error("You do not have permission to edit this comment.");
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update comment.");
                    }
                    return response.json();
                })
                .then(updatedComment => {
                    // ★ 成功時の処理 ★
                    // モーダル内の comments 配列の該当コメントを更新
                    const index = this.selectedCardData.comments.findIndex(c => c.id === updatedComment.id);
                    if (index > -1) {
                        this.selectedCardData.comments[index].content = updatedComment.content;
                    }
                    
                    // フォームを閉じる (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating comment:", error);
                    alert(error.message || "An error occurred while updating the comment.");
                    // エラー時もフォームを閉じる
                    detail.callback();
                });
            },

            updateCardDatesFromModal(detail) {
                // detail = { card: {...}, startDate: "...", endDate: "...", reminder: "...", callback: () => {} }
                const card = this.selectedCardData;
                
                // ★★★ ここからリマインダー日時 (reminder_at) の計算 ★★★
                let reminder_at = null;
                const endDate = detail.endDate ? new Date(detail.endDate) : null;
                
                if (endDate && detail.reminder !== "none") {
                    let reminderDate = new Date(endDate.getTime()); // 期限日のコピーを作成

                    if (detail.reminder === "10_minutes_before") {
                        reminderDate.setMinutes(reminderDate.getMinutes() - 10);
                    } else if (detail.reminder === "1_hour_before") {
                        reminderDate.setHours(reminderDate.getHours() - 1);
                    } else if (detail.reminder === "1_day_before") {
                        reminderDate.setDate(reminderDate.getDate() - 1);
                    }
                    
                    // ★ 修正後: ローカル時刻を Y-m-d H:i:s 形式でフォーマット
                    // (flatpickr のフォーマッタを流用)
                    reminder_at = window.flatpickr.formatDate(reminderDate, "Y-m-d H:i:S");
                }
                // ★★★ 計算ここまで ★★★

                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ 
                        start_date: detail.startDate,
                        end_date: detail.endDate,
                        reminder_at: reminder_at // ★ 計算した `reminder_at` (日時 or null) を送信
                    })
                })
                .then(response => {
                    if (response.status === 422) {
                        alert("Invalid dates. Please ensure the due date is after the start date.");
                        throw new Error("Validation failed");
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update card dates.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    
                    // 1. モーダル内のデータを更新
                    this.selectedCardData.start_date = updatedCard.start_date;
                    this.selectedCardData.end_date = updatedCard.end_date;
                    this.selectedCardData.reminder_at = updatedCard.reminder_at; // ★ reminder_at も更新
                    
                    // 2. メインボード（背景）のデータも更新
                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].start_date = updatedCard.start_date;
                            this.lists[listIndex].cards[cardIndex].end_date = updatedCard.end_date;
                            this.lists[listIndex].cards[cardIndex].reminder_at = updatedCard.reminder_at; // ★ reminder_at も更新
                        }
                    }
                    
                    // 3. ポップオーバーを閉じる (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating card dates:", error);
                    if (error.message !== "Validation failed") {
                        alert("An error occurred while updating the dates.");
                    }
                });
            },

            deleteCard(card, list) {
                // ユーザーに確認
                if (!confirm("Are you sure you want to delete this card: \"" + card.title + "\"?")) {
                    return;
                }

                fetch(`/cards/${card.id}`, {
                    method: "DELETE", // "..."
                    headers: {
                        "Content-Type": "application/json", // "..."
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (!response.ok) { // 204 No Content も .ok は true になる
                        throw new Error("Failed to delete card."); // "..."
                    }
                    
                    // ★ 成功時の処理
                    // UIからカードを削除 (list.cards 配列から該当 card を除去)
                    const index = list.cards.findIndex(c => c.id === card.id);
                    if (index > -1) {
                        list.cards.splice(index, 1);
                    }
                })
                .catch(error => {
                    console.error("Error deleting card:", error); // "..."
                    alert("An error occurred while deleting the card."); // "..."
                });
            },

            updateCardTitleFromModal() {
                // 編集モードでないなら何もしない
                if (!this.editingCardTitleModal) return;

                const newTitle = this.editedCardTitleModal.trim();
                const card = this.selectedCardData; // APIから取得済みのデータ

                // タイトルが空、または変更がない場合は、編集をキャンセルするだけ
                if (newTitle === "" || newTitle === card.title) {
                    this.editingCardTitleModal = false;
                    return;
                }

                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: newTitle })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    
                    // 1. モーダル内のデータを更新
                    this.selectedCardData.title = updatedCard.title; 
                    
                    // 2. メインボード（背景）のデータも更新 (重要)
                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].title = updatedCard.title;
                        }
                    }
                    
                    // 3. 編集状態をリセット
                    this.editingCardTitleModal = false;
                })
                .catch(error => {
                    console.error("Error updating card:", error);
                    alert("An error occurred while updating the card.");
                    // エラーが起きても編集フォームを閉じる
                    this.editingCardTitleModal = false;
                });
            }
        }'
        x-init="init(); watchSelectedCard();"
        >
            {{-- ▼▼▼ PHPの@foreachをAlpine.jsの<template x-for>に変更 ▼▼▼ --}}
            <template x-for="list in lists" :key="list.id">
                <div class="flex-shrink-0 w-72 bg-gray-100 dark:bg-gray-700 rounded-md shadow-md" :data-id="list.id">
                    {{-- リストヘッダー --}}
                    <div class="p-3 flex justify-between items-center">
                        {{-- ▼▼▼ タイトルを編集可能に変更 ▼▼▼ --}}
                        <template x-if="editingListId === list.id">
                            <input type="text" 
                                   :id="`list-title-input-${list.id}`"
                                   x-model="editedListTitle"
                                   @blur="updateListTitle(list)"
                                   @keydown.enter.prevent="updateListTitle(list)"
                                   @keydown.escape.prevent="editingListId = null"
                                   class="text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border-indigo-500 rounded focus:ring-indigo-500 w-full p-1">
                        </template>
                        <template x-if="editingListId !== list.id">
                            <h3 @click="startEditingList(list)" 
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200 cursor-pointer w-full" 
                                x-text="list.title">
                            </h3>
                        </template>
                        {{-- ▲▲▲ 編集ロジックここまで ▲▲▲ --}}

                        {{-- ▼▼▼ 3点リーダーボタンをドロップダウンに変更 ▼▼▼ --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10">
                                <a @click.prevent="open = false; deleteList(list)" href="#" 
                                class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Delete this list...
                                </a>
                                {{-- (ここに将来「リストをコピー」などを追加できる) --}}
                            </div>
                        </div>
                        {{-- ▲▲▲ 変更ここまで ▲▲▲ --}}
                    </div>

                    {{-- カード一覧 --}}
                    {{-- ★ 変更点: SortableJS連携のため data-list-id と list-cards クラスを追加 --}}
                    <div class="list-cards p-3 space-y-3 overflow-y-auto" 
                        style="max-height: calc(100vh - 250px);"
                        :data-list-id="list.id"
                        x-init="initCardSortable($el)">
                        
                        <template x-for="card in list.cards" :key="card.id">
                            {{-- ★ 変更点: div全体をモーダルを開くトリガーにする --}}
                            <div @click="selectedCardId = card.id" {{-- 1. @click で selectedCardId をセット --}}
                                 class="card-item bg-white dark:bg-gray-800 rounded-md shadow hover:bg-gray-100 dark:hover:bg-gray-700 relative group cursor-pointer" {{-- 2. cursor-pointer をここに移動 --}}
                                 :data-card-id="card.id">

                                <div class="p-3">
                                    {{-- 3. @click を削除 (親divに移動したため) --}}
                                    <p class="text-sm text-gray-800 dark:text-gray-100" 
                                       x-text="card.title">
                                    </p>
                                    
                                    <button @click.prevent.stop="deleteCard(card, list)" {{-- 4. @click.prevent.stop を追加 --}}
                                            class="absolute top-1 right-1 p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                {{-- 5. インライン編集フォーム (editingCardId) はすべて削除 --}}
                                
                            </div>
                        </template>
                    </div>

                        {{-- 「カードを追加」UI (トップレベルの newCardForm を参照) --}}
                        {{-- リストフッター --}}
                        <div class="list-footer p-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="mt-2">

                                <div x-show="newCardForm.listId !== list.id">
                                    <button @click="newCardForm.listId = list.id; $nextTick(() => $refs['newCardTitleInput_' + list.id].focus())"
                                            class="w-full p-2 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm font-medium text-left">
                                        + Add a card
                                    </button>
                                </div>

                                <div x-show="newCardForm.listId === list.id" x-cloak>
                                    <form @submit.prevent="submitNewCard(list)" 
                                        class="space-y-2">
                                        
                                        {{-- ★ 変更点: x-model と :x-ref を動的に設定 --}}
                                        <textarea x-model="newCardForm.title"
                                                :x-ref="'newCardTitleInput_' + list.id"
                                                @keydown.escape.prevent="newCardForm.listId = null; newCardForm.title = ''"
                                                @keydown.enter.prevent="$event.target.form.requestSubmit()"
                                                class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                                rows="3" 
                                                placeholder="Enter a title for this card..."></textarea>
                                        
                                        <div class="flex items-center space-x-2">
                                            <x-primary-button type="submit">Add card</x-primary-button>
                                            <button @click="newCardForm.listId = null; newCardForm.title = ''"
                                                    type="button"
                                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    
                </div>
            </template>
            {{-- ▲▲▲ Alpine.jsのループここまで ▲▲▲ --}}

            {{-- 「リストを追加」UI --}}
            <div class="flex-shrink-0 w-72">
                <button x-show="!addingList" @click="addingList = true; $nextTick(() => $refs.listTitleInput.focus())"
                        class="w-full p-3 rounded-md bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 font-medium">
                    + Add another list
                </button>
                
                {{-- ▼▼▼ フォームの@submitと<input>を更新 ▼▼▼ --}}
                <form x-show="addingList" @submit.prevent="submitNewList" class="bg-gray-100 dark:bg-gray-700 rounded-md shadow-md p-3">
                    <input x-model="newListTitle" x-ref="listTitleInput" type="text" placeholder="Enter list title..."
                           class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <div class="mt-2 flex items-center space-x-2">
                        <x-primary-button type="submit">Add list</x-primary-button>
                        <button @click="addingList = false" type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
            {{-- ★ ここから追加: カード詳細モーダル --}}
            <x-card-detail-modal />
            {{-- ★ 追加ここまで --}}
        </div>
    </div>

</x-app-layout>