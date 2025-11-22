<x-app-layout>
    <div
    @card-sort-end.window="handleCardSortEnd($event.detail)" 
        @submit-card-title-update.window="updateCardTitleFromModal()"
        @submit-card-description-update.window="updateCardDescriptionFromModal()"
        @submit-new-comment.window="submitNewCommentFromModal($event.detail)"
        @submit-delete-comment.window="deleteCommentFromModal($event.detail)"
        @submit-edit-comment.window="updateCommentFromModal($event.detail)"
        @submit-card-dates.window="updateCardDatesFromModal($event.detail)"
        @submit-new-label.window="submitNewLabel($event.detail)"
        @submit-edit-label.window="updateLabel($event.detail)" 
        @submit-delete-label.window="deleteLabel($event.detail)"
        @toggle-label.window="toggleLabel($event.detail)"
        @submit-new-checklist.window="submitNewChecklist($event.detail)"
        @submit-new-checklist-item.window="submitNewChecklistItem($event.detail)"
        @toggle-checklist-item.window="toggleChecklistItem($event.detail)" 
        @delete-checklist-item.window="deleteChecklistItem($event.detail)"
        @submit-edit-checklist-item.window="updateChecklistItem($event.detail)"
        @submit-checklist-item-sort.window="handleChecklistItemSort($event.detail)"
        @submit-edit-checklist.window="updateChecklist($event.detail)"
        @submit-delete-checklist.window="deleteChecklist($event.detail)"
        @submit-new-attachment.window="submitNewAttachment($event.detail)"
        @submit-delete-attachment.window="deleteAttachment($event.detail)"
        @submit-review-status-update.window="updateReviewStatus($event.detail)"
        @submit-make-cover.window="updateCardCoverImage($event.detail)"
        @toggle-assigned-user.window="toggleAssignedUser($event.detail)"
        @toggle-card-completed.window="toggleCardCompleted($event.detail)"
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
            editingChecklistItemId: null, // 編集中のアイテムID
            editedChecklistItemContent: "", // 編集用のアイテム本文
            editingChecklistId: null, // 編集中のチェックリストID
            editedChecklistTitle: "", // 編集用のチェックリストタイトル
            addingList: false,
            showInviteModal: false, // 招待モーダルの表示状態
            filterKeyword: "", // ★ 1. フィルターのキーワードを保持する変数を追加
            filterLabels: "", 
            filterPeriod: "", 
            filterMember: "", 
            filterChecklist: "", 
            filterCompleted: "",
            viewMode: "board",
            calendarInstance: null,
            timelineInstance: null, // ★ 1. [NEW] タイムラインのインスタンスを保持
            boardLabels: [],
            boardMembers: [], // ボードの全メンバーを保持する配列
            newListTitle: "",
            editingListId: null,
            editedListTitle: "",

            init() {
                this.lists = @json($lists);

                // ★ 2. ボードの全ラベルを fetch する処理を追加
                fetch("{{ route('labels.index', $board) }}")
                    .then(response => response.json())
                    .then(data => {
                        this.boardLabels = data;
                    })
                    .catch(error => console.error("Error fetching labels:", error));
                
                // ★ ここから追加: ボードの全メンバーを fetch
                fetch("{{ route('boards.getMembers', $board) }}")
                    .then(response => response.json())
                    .then(data => {
                        this.boardMembers = data;
                    })
                    .catch(error => console.error("Error fetching members:", error));
                // ★ 追加ここまで
                // ★★★ [NEW] デバッグ用のグローバル変数を設定 ★★★
                window.boardData = this;
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
                                // ★★★ ここから修正 ★★★
                                // 1. coverImage オブジェクトを手動で構築する
                                // (data.attachments の中から data.cover_image_id と一致するものを探す)
                                data.coverImage = data.attachments.find(a => a.id === data.cover_image_id) || null;

                                // 2. 取得したデータ（coverImage が追加された状態）をセット
                                this.selectedCardData = data; 
                                // ★★★ 修正ここまで ★★★ 
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
                        this.editingChecklistItemId = null;
                        this.editedChecklistItemContent = "";
                        this.editingChecklistId = null;
                        this.editedChecklistTitle = "";
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
                    // ★ 修正: pushする前に cards 配列を初期化する
                    newList.cards = [];
                    // ★ 修正: .push() の代わりに、新しい配列で置き換える
                    this.lists = [...this.lists, newList];
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
                    // ★ 修正: .splice() の代わりに、.filter() で新しい配列を作成
                    this.lists = this.lists.filter(l => l.id !== list.id);
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
                
                // ★ 修正: toList.cards を丸ごと置き換える
                let toItems = Array.from(toList.cards);
                toItems.splice(detail.newIndex, 0, movedCard);
                toList.cards = toItems;

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
                // エラーをリセット
                this.newCardForm.error = null;
                // 1. クライアントサイドでの空チェック
                if (this.newCardForm.title.trim() === "") {
                    // ★ 修正: フォームを閉じずに、エラーメッセージを表示する
                    this.newCardForm.error = "The title field is required.";
                    
                    // フォームの入力欄にフォーカスを戻す（UX向上）
                    this.$nextTick(() => {
                        if (this.$refs["newCardTitleInput_" + list.id]) {
                            this.$refs["newCardTitleInput_" + list.id].focus();
                        }
                    });
                    return; // 送信せずに終了
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
                .then(async response => {
                    // ★ 422 (バリデーションエラー) のハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        // Laravelのエラーレスポンス { message: "...", errors: { title: ["..."] } } からメッセージを抽出
                        const errorMessage = data.errors?.title?.[0] || data.message || "Invalid input.";
                        
                        // エラーをセットして処理を中断 (catchブロックには飛ばさない)
                        this.newCardForm.error = errorMessage;
                        return null; // nullを返して次のthenをスキップさせる判定に使う
                    }

                    if (!response.ok) {
                        throw new Error("Failed to create card.");
                    }
                    return response.json();
                })
                .then(newCard => {
                    // バリデーションエラーだった場合はここで終了
                    if (!newCard) return;

                    // 成功時の処理
                    list.cards = [...list.cards, newCard]; // スプレッド構文で再代入してリアクティブ更新
                    
                    // フォームをリセット
                    this.newCardForm.listId = null;
                    this.newCardForm.title = ""; 
                    this.newCardForm.error = null;
                })
                .catch(error => {
                    console.error("Error creating card:", error);
                    alert("An error occurred while adding the card.");
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
                .then(async response => {
                    // ★ 422 エラーハンドリング (修正)
                    if (response.status === 422) {
                        const data = await response.json();
                        // タイトルのエラーメッセージを取得 (例: "The title must not be greater than 255 characters.")
                        const message = data.errors?.title?.[0] || data.message || "Invalid input.";
                        
                        alert(message); // ★ 具体的な理由をアラート表示
                        throw new Error("Validation failed");
                    }

                    if (!response.ok) {
                        throw new Error("Failed to update card.");
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
                .then(async response => {
                    // ★ 422 エラーハンドリング (追加)
                    if (response.status === 422) {
                        const data = await response.json();
                        // 説明文のエラーメッセージを取得
                        const message = data.errors?.description?.[0] || data.message || "Invalid input.";
                        
                        alert(message); // ★ 具体的な理由をアラート表示
                        throw new Error("Validation failed");
                    }

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
                    this.syncBoardData();
                    
                    // 3. 編集状態をリセット
                    this.editingCardDescription = false;
                })
                .catch(error => {
                    console.error("Error updating card description:", error);
                    if (error.message !== "Validation failed") {
                        alert("An error occurred while updating the card description.");
                    }
                    // エラー時は編集モードを維持して修正させるか、閉じるか。今回は閉じる挙動を維持。
                    // (UX的には維持したほうが親切ですが、元の挙動に合わせます)
                    // this.editingCardDescription = false;
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
                .then(async response => {
                    // ★ 422 エラーハンドリング (修正)
                    if (response.status === 422) {
                        const data = await response.json();
                        // コメントのエラーメッセージを取得
                        const message = data.errors?.content?.[0] || data.message || "Invalid input.";
                        
                        // throwしてcatchブロックでアラートする
                        throw new Error(message);
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

                    this.syncBoardData();
                    
                    // 2. フォームをクリアする (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error posting comment:", error);
                    // catchしたエラーメッセージ(サーバーからのメッセージ)を表示
                    alert(error.message);
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
                        this.syncBoardData();
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
                .then(async response => {
                    // ★ 422 エラーハンドリング (追加)
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.content?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
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
                    alert(error.message);
                    // detail.callback(); // エラー時は閉じずに修正させるならコメントアウト
                });
            },

            updateCardDatesFromModal(detail) {
                // 1. データ取得と安全性チェック
                // dispatchで渡された detail.card があれば優先し、なければ this.selectedCardData を使う
                const card = detail.card || this.selectedCardData;

                // カードIDがない場合は処理を中断（これが405エラーの根本原因を防ぎます）
                if (!card || !card.id) {
                    console.error("Error: Card ID is missing.");
                    return;
                }

                // リマインダー日時の計算
                let reminder_at = null;
                const endDate = detail.endDate ? new Date(detail.endDate) : null;
                
                if (endDate && detail.reminder !== "none") {
                    let reminderDate = new Date(endDate.getTime()); 
                    if (detail.reminder === "10_minutes_before") {
                        reminderDate.setMinutes(reminderDate.getMinutes() - 10);
                    } else if (detail.reminder === "1_hour_before") {
                        reminderDate.setHours(reminderDate.getHours() - 1);
                    } else if (detail.reminder === "1_day_before") {
                        reminderDate.setDate(reminderDate.getDate() - 1);
                    }
                    reminder_at = window.flatpickr.formatDate(reminderDate, "Y-m-d H:i:S");
                }

                const startISO = detail.startDate ? new Date(detail.startDate).toISOString() : null;
                const endISO = detail.endDate ? new Date(detail.endDate).toISOString() : null;

                // URLを明示的に変数化（デバッグしやすくする）
                const url = `/cards/${card.id}`;

                fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                        "Accept": "application/json" // 明示的にJSONを要求
                    },
                    body: JSON.stringify({ 
                        start_date: startISO,
                        end_date: endISO,
                        reminder_at: reminder_at
                    })
                })
                .then(async response => {
                    // ★ 修正点: 422エラーの中身（サーバーからのメッセージ）を取得する
                    if (response.status === 422) {
                        const data = await response.json();
                        // エラー配列から最初のメッセージを取り出す
                        // (PHP側で $fail(The due date must be...) と返したメッセージ)
                        const errorMsg = data.errors?.end_date?.[0] 
                                    || data.errors?.reminder_at?.[0] 
                                    || data.message 
                                    || "Invalid dates.";
                        
                        // エラーとして投げる（catchブロックでalertする）
                        throw new Error(errorMsg); 
                    }

                    if (!response.ok) {
                        throw new Error("Failed to update card dates.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // 成功時の処理
                    if (this.selectedCardData) {
                        this.selectedCardData.start_date = updatedCard.start_date;
                        this.selectedCardData.end_date = updatedCard.end_date;
                        this.selectedCardData.reminder_at = updatedCard.reminder_at;
                    }

                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].start_date = updatedCard.start_date;
                            this.lists[listIndex].cards[cardIndex].end_date = updatedCard.end_date;
                            this.lists[listIndex].cards[cardIndex].reminder_at = updatedCard.reminder_at;
                        }
                    }

                    if (this.calendarInstance) this.calendarInstance.refetchEvents();
                    if (this.timelineInstance) this.timelineInstance.refetchEvents();
                    
                    if (detail.callback) detail.callback();
                })
                .catch(error => {
                    console.error("Error updating card dates:", error);
                    // サーバーからの具体的なエラーメッセージを表示
                    alert(error.message);
                });
            },

            submitNewLabel(detail) {
                // detail = { board: {...}, name: "...", color: "...", callback: () => {} }
                if (detail.name.trim() === "") {
                    alert("Label name is required.");
                    return;
                }

                fetch(`/boards/${detail.board.id}/labels`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ 
                        name: detail.name,
                        color: detail.color
                    })
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        // name または color のエラーを取得
                        const message = data.errors?.name?.[0] || data.errors?.color?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to create label.");
                    }
                    return response.json();
                })
                .then(newLabel => {
                    // ★ 成功時の処理 ★
                    
                    // 1. 親コンポーネントのラベル一覧 (boardLabels) に追加
                    this.boardLabels.push(newLabel);
                    
                    // 2. ポップオーバーのフォームをリセット (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error creating label:", error);
                    // サーバーからのメッセージを表示
                    alert(error.message);
                });
            },

            updateLabel(detail) {
                // detail = { label: {...}, name: "...", color: "...", callback: () => {} }
                if (detail.name.trim() === "") {
                    alert("Label name is required.");
                    return;
                }

                fetch(`/labels/${detail.label.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ 
                        name: detail.name,
                        color: detail.color
                    })
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.name?.[0] || data.errors?.color?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update label.");
                    }
                    return response.json();
                })
                .then(updatedLabel => {
                    // ★ 成功時の処理 ★
                    // 1. boardLabels 配列内の該当ラベルを置き換え
                    const index = this.boardLabels.findIndex(l => l.id === updatedLabel.id);
                    if (index > -1) {
                        this.boardLabels[index] = updatedLabel;
                    }
                    // (※ boardLabels がリアクティブなので、モーダル内の表示も自動更新される)
                    
                    // 2. ポップオーバーのフォームをリセット (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating label:", error);
                    alert(error.message);
                });
            },

            // ★ ここから追加 (ラベル削除メソッド)
            deleteLabel(detail) {
                // detail = { label: {...}, callback: () => {} }
                if (!confirm("Are you sure you want to delete the label \"" + detail.label.name + "\"? This will remove it from all cards on this board.")) {
                    return;
                }

                fetch(`/labels/${detail.label.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (!response.ok) { // 204 No Content も .ok は true
                        throw new Error("Failed to delete label.");
                    }
                    
                    // ★ 成功時の処理 ★
                    // 1. boardLabels 配列から該当ラベルを削除
                    const index = this.boardLabels.findIndex(l => l.id === detail.label.id);
                    if (index > -1) {
                        this.boardLabels.splice(index, 1);
                    }
                    
                    // 2. ポップオーバーのフォームをリセット (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error deleting label:", error);
                    alert("An error occurred while deleting the label.");
                });
            },

            toggleLabel(detail) {
                // detail = { card: {...}, label: {...}, isAttached: true/false }
                
                let endpoint = `/cards/${detail.card.id}/labels/${detail.label.id}`;
                let method = detail.isAttached ? "POST" : "DELETE"; // チェックされたらPOST, 外されたらDELETE

                fetch(endpoint, {
                    method: method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    // 1. モーダル内のデータを手動で更新
                    if (detail.isAttached) {
                        this.selectedCardData.labels.push(detail.label);
                    } else {
                        const index = this.selectedCardData.labels.findIndex(l => l.id === detail.label.id);
                        if (index > -1) {
                            this.selectedCardData.labels.splice(index, 1);
                        }
                    }

                    // 2. メインボード(背景)のデータも更新 (即時反映のため)
                    // (detail.card は APIから取得した selectedCardData)
                    const listIndex = this.lists.findIndex(l => l.id == detail.card.list.id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == detail.card.id);
                        if (cardIndex > -1) {
                            // モーダルと全く同じロジックで背景の "labels" 配列も更新
                            if (detail.isAttached) {
                                this.lists[listIndex].cards[cardIndex].labels.push(detail.label);
                            } else {
                                const labelIndex = this.lists[listIndex].cards[cardIndex].labels.findIndex(l => l.id === detail.label.id);
                                if (labelIndex > -1) {
                                    this.lists[listIndex].cards[cardIndex].labels.splice(labelIndex, 1);
                                }
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error("Error toggling label:", error);
                    alert("An error occurred while toggling the label.");
                });
            },

            submitNewChecklist(detail) {
                // detail = { card: {...}, title: "...", callback: () => {} }
                if (detail.title.trim() === "") {
                    alert("Checklist title is required.");
                    return;
                }

                fetch(`/cards/${detail.card.id}/checklists`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: detail.title })
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.title?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to create checklist.");
                    }
                    return response.json();
                })
                .then(newChecklist => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内の checklists 配列に追加
                    this.selectedCardData.checklists.push(newChecklist);

                    this.syncBoardData();
                    
                    // 2. ポップオーバーのフォームをリセット (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error creating checklist:", error);
                    alert(error.message);
                });
            },

            submitNewChecklistItem(detail) {
                // detail = { checklist: {...}, content: "...", callback: () => {} }
                if (detail.content.trim() === "") {
                    detail.callback(true); // エラーだがフォームは閉じる
                    return;
                }

                fetch(`/checklists/${detail.checklist.id}/items`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ content: detail.content })
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.content?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to create checklist item.");
                    }
                    return response.json();
                })
                .then(newItem => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内の checklists[...].items 配列に追加
                    // (※ $watch("selectedCardId", ...) で $watch をネストできないため、
                    //    selectedCardData を直接変更する)
                    const checklistIndex = this.selectedCardData.checklists.findIndex(c => c.id === detail.checklist.id);
                    if (checklistIndex > -1) {
                        this.selectedCardData.checklists[checklistIndex].items.push(newItem);
                        this.syncBoardData();
                    }
                    
                    // 2. ポップオーバーのフォームをリセット (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error creating checklist item:", error);
                    alert(error.message);
                });
            },

            toggleChecklistItem(detail) {
                // detail = { item: {...}, isAttached: true/false }
                
                fetch(`/checklist-items/${detail.item.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ is_completed: detail.isCompleted }) // is_completed を送信
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update checklist item status.");
                    }
                    return response.json();
                })
                .then(updatedItem => {
                    // ★ 成功時の処理 ★
                    // UIの "is_completed" 状態を、サーバーからのレスポンスで上書き（同期）する
                    detail.item.is_completed = updatedItem.is_completed;
                    // (プログレスバーは item.is_completed を参照しているため、自動で更新される)
                    this.syncBoardData();
                })
                .catch(error => {
                    console.error("Error toggling checklist item:", error);
                    alert("An error occurred while updating the item.");
                    // エラー時はチェックボックスを元に戻す
                    detail.item.is_completed = !detail.isCompleted; 
                });
            },

            deleteChecklistItem(detail) {
                // detail = { item: {...}, checklist: {...} }
                if (!confirm("Are you sure you want to delete this item?")) {
                    return;
                }

                fetch(`/checklist-items/${detail.item.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (!response.ok) { 
                        throw new Error("Failed to delete checklist item.");
                    }
                    // ★ 修正: .splice() の代わりに、.filter() で新しい配列を作成
                    detail.checklist.items = detail.checklist.items.filter(i => i.id !== detail.item.id);
                    this.syncBoardData();
                })
                .catch(error => {
                    console.error("Error deleting checklist item:", error);
                    alert("An error occurred while deleting the item.");
                });
            },

            updateChecklistItem(detail) {
                // detail = { item: {...}, content: "...", callback: () => {} }
                const newContent = detail.content.trim();

                // 内容が空、または変更がない場合は、編集をキャンセルするだけ
                if (newContent === "" || newContent === detail.item.content) {
                    detail.callback(); // フォームを閉じる
                    return;
                }

                fetch(`/checklist-items/${detail.item.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ content: newContent }) // content を送信
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.content?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update checklist item.");
                    }
                    return response.json();
                })
                .then(updatedItem => {
                    // ★ 成功時の処理 ★
                    // 1. UIの "content" 状態を、サーバーからのレスポンスで上書き（同期）
                    detail.item.content = updatedItem.content;
                    
                    // 2. フォームを閉じる (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating checklist item:", error);
                    alert(error.message);
                    detail.callback();
                });
            },

            handleChecklistItemSort(detail) {
                // detail = { checklistId: ..., itemId: ..., newPosition: ..., oldPosition: ... }

                // 1. ローカルの Alpine.js データを即時更新 (UIの即時反映)
                const checklistIndex = this.selectedCardData.checklists.findIndex(c => c.id == detail.checklistId);
                if (checklistIndex === -1) {
                    console.error("Could not find checklist");
                    return;
                }
                const checklist = this.selectedCardData.checklists[checklistIndex];

                // ★★★ ここから修正 (強制再描画ロジック) ★★★
                
                // a. 現在の items 配列の「コピー」を作成
                let items = Array.from(checklist.items);

                // b. コピーをD&Dの結果に基づいて並び替える
                const [movedItem] = items.splice(detail.oldPosition, 1);
                if (!movedItem) {
                    console.error("SortableJS reported an invalid oldPosition.");
                    return; // 同期ズレによるエラーを防止
                }
                items.splice(detail.newPosition, 0, movedItem);
                
                // c. checklist.items を一度リセット（Alpine.jsに「変更」を通知）
                checklist.items = [];

                // d. $nextTick で、並び替えた新しい配列をセット（強制再描画）
                this.$nextTick(() => {
                    checklist.items = items;
                });
                
                // ★★★ 修正ここまで ★★★

                // 2. サーバーに新しい順序を送信 (API呼び出し)
                
                // APIが要求する形式 [1, 3, 2] にデータを整形
                // ★ 修正: "checklist.items" ではなく、並び替え済みの "items" を参照
                const orderedItemIds = items.map(item => item.id);

                fetch("{{ route('checklist_items.updateOrder') }}", {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({
                        checklist_id: detail.checklistId,
                        ordered_item_ids: orderedItemIds
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update item order on server.");
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Checklist item order updated:", data.message);
                })
                .catch(error => {
                    console.error("Error updating item order:", error);
                    alert("An error occurred while saving the new order. Please reload the page.");
                });
            },

            updateChecklist(detail) {
                // detail = { checklist: {...}, title: "...", callback: () => {} }
                const newTitle = detail.title.trim();

                if (newTitle === "" || newTitle === detail.checklist.title) {
                    detail.callback(); // フォームを閉じる
                    return;
                }

                fetch(`/checklists/${detail.checklist.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ title: newTitle }) // title を送信
                })
                .then(async response => {
                    // ★ 422 エラーハンドリング (追加)
                    if (response.status === 422) {
                        const data = await response.json();
                        const message = data.errors?.title?.[0] || data.message || "Invalid input.";
                        throw new Error(message);
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update checklist.");
                    }
                    return response.json();
                })
                .then(updatedChecklist => {
                    // ★ 成功時の処理 ★
                    detail.checklist.title = updatedChecklist.title;
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating checklist:", error);
                    alert(error.message);
                    detail.callback();
                });
            },

            deleteChecklist(detail) {
                // detail = { checklist: {...} }
                if (!confirm("Are you sure you want to delete the checklist \"" + detail.checklist.title + "\"? This will delete all items within it.")) {
                    return;
                }

                // ★★★ この fetch(...) が抜けていました ★★★
                fetch(`/checklists/${detail.checklist.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (!response.ok) { // 204 No Content も .ok は true
                        throw new Error("Failed to delete checklist.");
                    }
                    
                    // ★ 修正: .splice() の代わりに、.filter() で新しい配列を作成
                    this.selectedCardData.checklists = this.selectedCardData.checklists.filter(c => c.id !== detail.checklist.id);
                    this.syncBoardData();
                })
                .catch(error => {
                    console.error("Error deleting checklist:", error);
                    alert("An error occurred while deleting the checklist.");
                });
            },

            submitNewAttachment(detail) {
                // detail = { card: {...}, formData: FormData, callback: () => {} }
                
                // (ローディングスピナーなどを表示するロジックを将来ここに追加できる)

                fetch(`/cards/${detail.card.id}/attachments`, {
                    method: "POST",
                    headers: {
                        // "Content-Type": "multipart/form-data", // ★ FormData を使う場合、Content-Type は「指定しない」
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                        "Accept": "application/json" // LaravelがJSONで応答するように
                    },
                    body: detail.formData // ★ FormData オブジェクトをそのまま送信
                })
                .then(response => {
                    if (response.status === 422) { // バリデーションエラー
                        alert("File is too large (max 10MB) or invalid file type.");
                        throw new Error("Validation failed");
                    }
                    if (!response.ok) {
                        throw new Error("Failed to upload file.");
                    }
                    return response.json();
                })
                .then(newAttachment => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内の attachments 配列に追加
                    this.selectedCardData.attachments.push(newAttachment);

                    this.syncBoardData();
                    
                    // 2. ポップオーバーを閉じる (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error uploading file:", error);
                    if (error.message !== "Validation failed") {
                        alert("An error occurred while uploading the file.");
                    }
                    detail.callback(); // エラー時もポップオーバーを閉じる
                });
            },

            deleteAttachment(detail) {
                // detail = { attachment: {...} }
                if (!confirm("Are you sure you want to delete the file \"" + detail.attachment.file_name + "\"?")) {
                    return;
                }

                fetch(`/attachments/${detail.attachment.id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    }
                })
                .then(response => {
                    if (response.status === 403) {
                        throw new Error("You do not have permission to delete this file.");
                    }
                    if (!response.ok) { // 204 No Content も .ok は true
                        throw new Error("Failed to delete attachment.");
                    }
                    
                    // ★ 修正: .splice() の代わりに、.filter() で新しい配列を作成
                    this.selectedCardData.attachments = this.selectedCardData.attachments.filter(a => a.id !== detail.attachment.id);
                    this.syncBoardData();
                })
                .catch(error => {
                    console.error("Error deleting attachment:", error);
                    alert(error.message || "An error occurred while deleting the attachment.");
                });
            },

            updateReviewStatus(detail) {
                // detail = { attachment: {...}, status: "...", callback: () => {} }
                
                // 既にそのステータスなら何もしない
                if (detail.attachment.review_status === detail.status) {
                    detail.callback(); // ポップオーバーを閉じる
                    return;
                }

                fetch(`/attachments/${detail.attachment.id}/review`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ review_status: detail.status }) // review_status を送信
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update review status.");
                    }
                    return response.json();
                })
                .then(updatedAttachment => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内の該当添付ファイルのステータスを更新
                    detail.attachment.review_status = updatedAttachment.review_status;
                    
                    // 2. ポップオーバーを閉じる (コールバックを実行)
                    detail.callback();
                })
                .catch(error => {
                    console.error("Error updating review status:", error);
                    alert("An error occurred while updating the status.");
                    detail.callback(); // エラー時もポップオーバーを閉じる
                });
            },

            toggleAssignedUser(detail) {
                // detail = { card: {...}, member: {...}, isAttached: true/false }
                
                let endpoint = `/cards/${detail.card.id}/assign-user/${detail.member.id}`;
                let method = detail.isAttached ? "POST" : "DELETE";

                fetch(endpoint, {
                    method: method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                        "Accept": "application/json"
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to toggle assigned user.");
                    }
                    return response.json();
                })
                .then(data => {
                    // ★★★ ここから修正 ★★★
                    
                    // 1. (assignedUsers が null/undefined の場合、|| [] で空配列をセット)
                    const currentAssignments = this.selectedCardData.assignedUsers || [];

                    if (detail.isAttached) {
                        // (attach)
                        this.selectedCardData.assignedUsers = [
                            ...currentAssignments, 
                            detail.member
                        ];
                    } else {
                        // (detach)
                        this.selectedCardData.assignedUsers = 
                            currentAssignments.filter(u => u.id !== detail.member.id);
                    }
                    // ★★★ 修正ここまで ★★★
                })
                .catch(error => {
                    console.error("Error toggling assigned user:", error);
                    alert("An error occurred while toggling the member assignment.");
                });
            },

            toggleCardCompleted(detail) {
                // detail = { card: {...}, isCompleted: true/false }
                const card = this.selectedCardData;
                
                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ is_completed: detail.isCompleted }) // is_completed を送信
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card status.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内のデータを更新
                    this.selectedCardData.is_completed = updatedCard.is_completed;
                    
                    // 2. メインボード(背景)のデータも更新 (同期のため)
                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].is_completed = updatedCard.is_completed;
                        }
                    }
                })
                .catch(error => {
                    console.error("Error updating card status:", error);
                    alert("An error occurred while updating the card status.");
                    // エラー時はチェックボックスを元に戻す
                    this.selectedCardData.is_completed = !detail.isCompleted;
                });
            },

            toggleCardCompletedFromBoard(card, isCompleted) {
                // card = this.lists[...].cards[...] の card オブジェクト
                // isCompleted = $event.target.checked (true/false)

                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ is_completed: isCompleted })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card status.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    // 1. メインボード(背景)のデータを更新
                    // (card オブジェクトは参照渡しなので、これを更新すれば this.lists が更新される)
                    card.is_completed = updatedCard.is_completed; 
                    
                    // 2. モーダルが開いていれば、モーダル内のデータも更新 (同期のため)
                    if (this.selectedCardData && this.selectedCardData.id === updatedCard.id) {
                        this.selectedCardData.is_completed = updatedCard.is_completed;
                    }
                })
                .catch(error => {
                    console.error("Error updating card status:", error);
                    alert("An error occurred while updating the card status.");
                    // エラー時はチェックボックスを元に戻す
                    card.is_completed = !isCompleted;
                });
            },

            updateCardCoverImage(detail) {
                // detail = { attachmentId: 123 (or null for remove) }
                const card = this.selectedCardData;

                // 既に設定済み、または既に解除済みなら何もしない
                if (card.cover_image_id === detail.attachmentId) {
                    return;
                }

                fetch(`/cards/${card.id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                    },
                    body: JSON.stringify({ cover_image_id: detail.attachmentId }) // cover_image_id (ID or null) を送信
                })
                .then(response => {
                    if (response.status === 422) { // バリデーションエラー
                        alert("Invalid cover image selected.");
                        throw new Error("Validation failed");
                    }
                    if (!response.ok) {
                        throw new Error("Failed to update cover image.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // ★ 成功時の処理 ★
                    // 1. モーダル内のデータを更新
                    this.selectedCardData.cover_image_id = updatedCard.cover_image_id;
                    // (coverImage リレーションも手動で更新)
                    this.selectedCardData.coverImage = this.selectedCardData.attachments.find(a => a.id === updatedCard.cover_image_id) || null;
                    
                    // 2. メインボード(背景)のデータも更新
                    const listIndex = this.lists.findIndex(l => l.id == updatedCard.board_list_id);
                    if (listIndex > -1) {
                        const cardIndex = this.lists[listIndex].cards.findIndex(c => c.id == updatedCard.id);
                        if (cardIndex > -1) {
                            this.lists[listIndex].cards[cardIndex].cover_image_id = updatedCard.cover_image_id;
                            // (※ 背景の coverImage リレーションは、
                            //    "cards.labels" のように Eager Loading していないため、
                            //    ここでは更新不要)
                        }
                    }
                })
                .catch(error => {
                    console.error("Error updating cover image:", error);
                    if (error.message !== "Validation failed") {
                        alert("An error occurred while updating the cover image.");
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
                    if (!response.ok) { 
                        throw new Error("Failed to delete card."); 
                    }
                    // ★ 修正: .splice() の代わりに、.filter() で新しい配列を作成
                    list.cards = list.cards.filter(c => c.id !== card.id);
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
            },

            syncBoardData() {
                if (!this.selectedCardData) return;
                
                const cardId = this.selectedCardData.id;
                
                // 全リストから該当するカードを探す
                for (let list of this.lists) {
                    const card = list.cards.find(c => c.id === cardId);
                    if (card) {
                        // フッター表示に必要なデータを同期 (参照渡しではなく、配列の中身を更新)
                        card.description = this.selectedCardData.description;
                        
                        // 配列はリアクティブに検知させるため、代入で更新
                        card.comments = this.selectedCardData.comments;
                        card.attachments = this.selectedCardData.attachments;
                        card.checklists = this.selectedCardData.checklists;
                        
                        break; // 見つかったら終了
                    }
                }
            },

            get filteredLists() {
                const keyword = this.filterKeyword.trim();
                const normalizedKeyword = keyword.normalize("NFKC").toLowerCase();

                // ★★★ 1. [FIX] Date Helper の定義をメソッド内部に移動 ★★★
                const jsNow = new Date(); 
                const jsTomorrow = new Date(jsNow.getTime() + 24 * 60 * 60 * 1000);
                const jsEndOfWeek = (() => { // 今週末を計算 (土曜の終わり)
                    const d = new Date();
                    d.setDate(d.getDate() + (6 - d.getDay()));
                    d.setHours(23, 59, 59, 999);
                    return d;
                })(); 
                const jsEndOfMonth = (() => { // 今月末を計算
                    const d = new Date();
                    d.setMonth(d.getMonth() + 1, 0); 
                    d.setHours(23, 59, 59, 999);
                    return d;
                })();
                // ★★★ 修正ここまで ★★★


                // フィルターの変数を取得 (既存ロジック)
                const labels = this.filterLabels;
                const period = this.filterPeriod;
                const member = this.filterMember;
                const checklist = this.filterChecklist;
                const completed = this.filterCompleted;
                
                // PHPからBladeで埋め込まれたAuth::id()を文字列として取得
                const currentUserId = String({{ Auth::id() }});
                
                
                const cardMatches = (card) => {
                    
                    // --- 1-1. キーワード検索 (変更なし) ---
                    let matchesKeyword = (keyword === "");
                    if (!matchesKeyword) {
                        const cardContent = [
                            card.title,
                            card.description,
                            ...((card.comments || []).map(c => c.content)),
                            ...((card.attachments || []).map(a => a.file_name)),
                            ...((card.checklists || []).flatMap(c => [c.title, ...((c.items || []).map(i => i.content))])),
                        ].join(" ").normalize("NFKC").toLowerCase();

                        if (cardContent.includes(normalizedKeyword)) {
                            matchesKeyword = true;
                        }
                    }

                    // --- 1-2. 静的フィルターチェック (AND 条件で組み合わせる) ---
                    let matchesStatic = true;
                    const assigned = card.assigned_users || [];
                    const dueDate = card.end_date ? new Date(card.end_date) : null;

                    // ★ 修正: assignedUsers ではなく assigned を使う
                    const assignmentUserIds = assigned.map(u => String(u.id));


                    // a. Completion Status
                    if (completed === "true") {
                        matchesStatic = matchesStatic && card.is_completed;
                    } else if (completed === "false") {
                        matchesStatic = matchesStatic && !card.is_completed;
                    }
                    
                    // b. Member (★ FIX: Null 安全と Type Coercion)
                    if (member === "mine") {
                        // FIX: currentUserId (String) が assignmentUserIds (Array of String) に含まれているかチェック
                        matchesStatic = matchesStatic && assignmentUserIds.includes(currentUserId);
                    } else if (member === "none") {
                        // FIX: 割り当てられたIDの配列の長さがゼロであることを確認
                        matchesStatic = matchesStatic && assigned.length === 0; // ← assigned を参照
                    }
                    
                    // c. Labels / d. Checklist (変更なし)
                    if (labels === "has") {
                        matchesStatic = matchesStatic && (card.labels && card.labels.length > 0);
                    } else if (labels === "none") {
                        matchesStatic = matchesStatic && (card.labels && card.labels.length === 0);
                    }
                    if (checklist === "has") {
                        matchesStatic = matchesStatic && (card.checklists && card.checklists.length > 0);
                    } else if (checklist === "none") {
                        matchesStatic = matchesStatic && (card.checklists && card.checklists.length === 0);
                    }
                    
                    // e. Period (★ FIX: 厳密な null/date チェックと今週/今月のロジック追加)
                    if (period !== "") {
                        if (period === "none_due") {
                            matchesStatic = matchesStatic && dueDate === null;
                        } else if (dueDate === null) {
                            matchesStatic = false; // 期限必須のフィルターで、期限がない場合は強制的に除外
                        } else {
                            // 期限がある場合のチェック
                            if (period === "overdue") {
                                matchesStatic = matchesStatic && dueDate < jsNow;
                            } else if (period === "tomorrow") {
                                // FIX: 明日までの期間
                                matchesStatic = matchesStatic && dueDate.toDateString() === jsTomorrow.toDateString();
                            } else if (period === "this_week") {
                                // FIX: 今週の終わりまで (現在から今週末まで)
                                matchesStatic = matchesStatic && dueDate >= jsNow && dueDate <= jsEndOfWeek;
                            } else if (period === "this_month") {
                                // FIX: 今月の終わりまで (現在から今月末まで)
                                matchesStatic = matchesStatic && dueDate >= jsNow && dueDate <= jsEndOfMonth;
                            }
                        }
                    }
                    
                    // 最終判定
                    return matchesKeyword && matchesStatic;
                };

                // ★★★ 2. フィルタリングとリストのマッピング (変更なし) ★★★
                const listsWithFilteredCards = this.lists.map(list => {
                    const matchingCards = list.cards.filter(card => 
                        cardMatches(card)
                    );
                    return { ...list, cards: matchingCards };
                });

                // 3. リスト自体を絞り込む
                return listsWithFilteredCards.filter(list => 
                    list.title.normalize("NFKC").toLowerCase().includes(normalizedKeyword) || 
                    list.cards.length > 0
                );
            },

            initCalendar() {
                // (既存のインスタンスチェックは変更なし)
                if (this.calendarInstance) {
                    this.calendarInstance.refetchEvents();
                    return;
                }
                
                const calendarEl = this.$refs.calendarContainer;
                
                this.calendarInstance = new window.FullCalendar.Calendar(calendarEl, {
                    plugins: [ window.FullCalendar.dayGridPlugin, window.FullCalendar.interactionPlugin ],
                    initialView: "dayGridMonth",
                    height: "auto",
                    
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },

                    events: (fetchInfo, successCallback, failureCallback) => {
                        const url = new URL("{{ route("boards.calendarEvents", $board) }}");
                        
                        // ★ 1. [NEW] 全てのフィルターを URL に追加
                        const filters = {
                            q: this.filterKeyword.trim(),
                            filterLabels: this.filterLabels,
                            filterPeriod: this.filterPeriod,
                            filterMember: this.filterMember,
                            filterChecklist: this.filterChecklist,
                            filterCompleted: this.filterCompleted
                        };
                        
                        for (const key in filters) {
                            const value = filters[key];
                            // 値が存在し、かつ空文字 (全件表示) ではない場合のみ追加
                            if (value && value !== "") { 
                                url.searchParams.append(key, value);
                            }
                        }
                        
                        // ★ 2. [FIX] view パラメータを追加
                        url.searchParams.append("view", (this.viewMode === "board" || this.viewMode === "calendar") ? "calendar" : "timeline");

                        fetch(url)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error("Failed to load events. API returned error.");
                                }
                                return response.json();
                            })
                            .then(data => successCallback(data))
                            .catch(error => {
                                console.error(error);
                                alert(error.message);
                                failureCallback(error);
                            });
                    },

                    eventClick: (info) => {
                        info.jsEvent.preventDefault();
                        this.selectedCardId = info.event.id;
                    },

                    // ★★★ ここから修正 ★★★
                    editable: true, // ドラッグ（移動）は有効

                    // 1. 伸縮（Resize）に関する設定を削除（または false に）
                    eventResizableFromStart: false, // 削除
                    
                    // 2. eventDrop (移動) は残す
                    eventDrop: (info) => {
                        this.updateCardDates(info, "drop");
                    },

                    // 3. eventResize (伸縮) のコールバックを削除
                    // eventResize: (info) => { ... }
                    // ★★★ 修正ここまで ★★★
                });

                this.calendarInstance.render();
            },

            initTimeline() {
                // (既存のインスタンスチェックは変更なし)
                if (this.timelineInstance) {
                    this.timelineInstance.refetchEvents();
                    return;
                }
                
                const timelineEl = this.$refs.timelineContainer;
                
                this.timelineInstance = new window.FullCalendar.Calendar(timelineEl, {
                    plugins: [ 
                        window.FullCalendar.timeGridPlugin,
                        window.FullCalendar.interactionPlugin
                    ], 
                    initialView: "timeGridWeek",
                    height: "auto",
                    
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: "timeGridWeek,timeGridDay"
                    },

                    events: (fetchInfo, successCallback, failureCallback) => {
                        const url = new URL("{{ route("boards.calendarEvents", $board) }}");
                        
                        // ★ 1. [NEW] 全てのフィルターを URL に追加
                        const filters = {
                            q: this.filterKeyword.trim(),
                            filterLabels: this.filterLabels,
                            filterPeriod: this.filterPeriod,
                            filterMember: this.filterMember,
                            filterChecklist: this.filterChecklist,
                            filterCompleted: this.filterCompleted
                        };
                        
                        for (const key in filters) {
                            const value = filters[key];
                            // 値が存在し、かつ空文字 (全件表示) ではない場合のみ追加
                            if (value && value !== "") { 
                                url.searchParams.append(key, value);
                            }
                        }
                        
                        // ★ 2. [FIX] view パラメータを追加
                        url.searchParams.append("view", (this.viewMode === "board" || this.viewMode === "calendar") ? "calendar" : "timeline");

                        fetch(url)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error("Failed to load events. API returned error.");
                                }
                                return response.json();
                            })
                            .then(data => successCallback(data))
                            .catch(error => {
                                console.error(error);
                                alert(error.message);
                                failureCallback(error);
                            });
                    },

                    eventClick: (info) => {
                        info.jsEvent.preventDefault(); 
                        this.selectedCardId = info.event.id;
                    },
                    editable: true,
                    eventResizableFromStart: true, 
                    
                    // ★ 2. [FIX] "updateCardDates" を呼び出し、info オブジェクト全体を渡す
                    eventDrop: (info) => {
                        this.updateCardDates(info, "drop");
                    },
                    eventResize: (info) => {
                        this.updateCardDates(info, "resize");
                    }
                });

                this.timelineInstance.render();
            },

            updateCardDates(info, actionType) {
                const event = info.event;
                const revertCallback = info.revert;

                let newStartDate = event.start;
                let newEndDate = event.end;

                // --- FullCalendar の日付補正 ---
                
                // 1. timeGrid (時間軸ビュー) の場合:
                if (event.allDay === false) {
                    if (!newEndDate) { newEndDate = newStartDate; } 
                } 
                // 2. dayGrid (月表示ビュー) の場合:
                else if (event.allDay === true) { 
                    
                    if (actionType === "resize" && newEndDate === null) {
                        // ★ [Fix] 開始日(start)をリサイズした場合 (newEndDate が null)
                        // event._instance.range.end (元の終了日) を使う
                        newEndDate = event._instance.range.end;
                    }

                    if (newEndDate) {
                        // "end" (翌日0時) から 1秒引いて「その日の終わり」に直す
                        newEndDate = new Date(newEndDate.getTime() - 1000); 
                    } else {
                        // (フォールバック: 1日イベントのDropなど)
                        newEndDate = newStartDate;
                    }
                }

                // 3. 最終フェイルセーフ
                if (!newEndDate) {
                    newEndDate = newStartDate;
                }
                // --- 補正ここまで ---

                fetch(`/cards/${event.id}`, { 
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        start_date: newStartDate.toISOString(),
                        end_date: newEndDate.toISOString()
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to update card dates.");
                    }
                    return response.json();
                })
                .then(updatedCard => {
                    // 成功時の処理 (モーダルデータも同期)
                    if (this.selectedCardData && this.selectedCardData.id == updatedCard.id) {
                        this.selectedCardData.start_date = updatedCard.start_date;
                        this.selectedCardData.end_date = updatedCard.end_date;
                    }
                    
                    // カレンダー/タイムラインのイベント更新ロジック
                    const calendarEvent = this.calendarInstance ? this.calendarInstance.getEventById(updatedCard.id) : null;
                    const timelineEvent = this.timelineInstance ? this.timelineInstance.getEventById(updatedCard.id) : null;
                    
                    // APIから返ってきた日付文字列 (UTC)
                    let apiStartDate = new Date(updatedCard.start_date);
                    let apiEndDate = new Date(updatedCard.end_date);

                    if (calendarEvent) {
                        // ★★★ [FIX] タイムゾーン/日付ズレ修正 ★★★
                        // .getUTCDate() (UTCの日付) ではなく、.getDate() (ローカルの日付) を使う
                        
                        // 1. ローカルの「年」「月」「日」を取得
                        let calendarStart = new Date(apiStartDate.getFullYear(), apiStartDate.getMonth(), apiStartDate.getDate());
                        
                        // 2. APIが返す end は (例) 22日 23:59:59Z なので、ローカルの 22日 が取れる
                        let calendarEnd = new Date(apiEndDate.getFullYear(), apiEndDate.getMonth(), apiEndDate.getDate() + 1); // +1 して 23 (翌日) にする

                        calendarEvent.setDates(calendarStart, calendarEnd, { allDay: true });
                    }
                    
                    if (timelineEvent) {
                        // タイムラインビュー(timeGrid)用:
                        // APIが返した正確な時刻をそのまま使う (変更なし)
                        timelineEvent.setDates(apiStartDate, apiEndDate);
                    }
                })
                .catch(error => {
                    console.error("Error updating card dates:", error);
                    alert("An error occurred while updating the card date.");
                    revertCallback(); // 失敗時にドラッグを元に戻す
                });
            }
        }'
        x-init="
            init(); 
            watchSelectedCard();
            
            // ★ [MODIFIED] ビューモードを監視
            $watch('viewMode', (value) => { 
                if (value === 'calendar' && !calendarInstance) {
                    setTimeout(() => initCalendar(), 50); 
                }
                
                // ★ [NEW] タイムラインが選択されたら初期化
                if (value === 'timeline' && !timelineInstance) {
                    setTimeout(() => initTimeline(), 50); 
                }

                // ★ [MODIFIED] フィルターキーワードの監視 (フィルター監視も $watch('viewMode') の中に移動)
                if (value === 'calendar' && calendarInstance) {
                    calendarInstance.refetchEvents();
                }
                if (value === 'timeline' && timelineInstance) {
                    timelineInstance.refetchEvents();
                }
            });

            // ★ [REPLACED] 全てのフィルターの変更を監視し、即時反映させる
            $watch('[filterKeyword, filterLabels, filterPeriod, filterMember, filterChecklist, filterCompleted]', (value) => {
                
                // カンバンボードのカードは、get filteredLists() 経由でリアクティブに更新されます。
                
                // カレンダー/タイムラインのデータ同期のみを行う（API呼び出し）
                if (viewMode === 'calendar' && calendarInstance) {
                    calendarInstance.refetchEvents(); 
                } 
                if (viewMode === 'timeline' && timelineInstance) {
                    timelineInstance.refetchEvents();
                }
            });

            // ★ [MODIFIED] 既存のキーワード監視は残す
            $watch('filterKeyword', (value) => { 
                if (viewMode === 'calendar' && calendarInstance) {
                    calendarInstance.refetchEvents(); 
                } 
                if (viewMode === 'timeline' && timelineInstance) {
                    timelineInstance.refetchEvents();
                }
            });
        ">
        {{-- (1) カンバンボード専用ヘッダー (変更なし) --}}
        <div class="bg-white dark:bg-gray-800 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $board->title }}</h1>
                        {{-- ★ [NEW] ビュー切替ボタン --}}
                        <div class="flex items-center space-x-1 p-1 bg-gray-200 dark:bg-gray-700 rounded-md">
                            {{-- 1. ボード（カンバン）ビュー --}}
                            <button @click="viewMode = 'board'"
                                    :class="{
                                        'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-100 shadow': viewMode === 'board',
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200': viewMode !== 'board'
                                    }"
                                    class="px-3 py-1 text-sm font-medium rounded-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path></svg>
                            </button>
                            {{-- 2. カレンダービュー --}}
                            <button @click="viewMode = 'calendar'"
                                    :class="{
                                        'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-100 shadow': viewMode === 'calendar',
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200': viewMode !== 'calendar'
                                    }"
                                    class="px-3 py-1 text-sm font-medium rounded-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </button>
                            {{-- ★ 3. [NEW] タイムラインビュー (timeGrid) --}}
                            <button @click="viewMode = 'timeline'"
                                    :class="{
                                        'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-100 shadow': viewMode === 'timeline',
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200': viewMode !== 'timeline'
                                    }"
                                    class="px-3 py-1 text-sm font-medium rounded-md transition-colors">
                                {{-- (時計のアイコン) --}}
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="flex -space-x-2">
                            {{-- ★ 1. @php use ... @endphp は削除 --}}

                            @foreach ($board->users->take(4) as $member)
                                {{-- ★ 2. 修正: フルネームで \Illuminate\Support\Str::startsWith() を呼び出す --}}
                                @if (\Illuminate\Support\Str::startsWith($member->avatar, 'avatars/'))
                                    <img class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover" 
                                         src="{{ asset('storage/' . $member->avatar) }}" 
                                         alt="{{ $member->name }}"
                                         title="{{ $member->name }}">
                                @else
                                    {{-- フォールバック: イニシャル --}}
                                    <span class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-500 items-center justify-center"
                                          title="{{ $member->name }}">
                                        <span class="text-sm font-medium leading-none text-white">
                                            @php
                                                // 1. 名前の前後の空白を削除 (NULLの場合は空文字にする)
                                                $name = trim($member->name ?? '');
                                                
                                                // 2. mb_substr で「文字単位」で1文字目を取得
                                                $initial = mb_substr($name, 0, 1, 'UTF-8');
                                                
                                                // 3. mb_strtoupper で大文字に変換
                                                $initials = mb_strtoupper($initial, 'UTF-8');
                                            @endphp
                                            {{ $initials }}
                                        </span>
                                    </span>
                                @endif
                            @endforeach

                            {{-- もし5人以上いたら、残りの人数を表示 --}}
                            @if ($board->users->count() > 4)
                                <span class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 text-gray-700 items-center justify-center text-xs font-medium">
                                    +{{ $members->count() - 4 }}
                                </span>
                            @endif
                        </div>
                        {{-- ★ [NEW] フィルター・ポップオーバー --}}
                        <div x-data="{ open: false }" class="relative">
                            {{-- 1. フィルター・ボタン本体 --}}
                            <button @click="open = !open" 
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-white p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            </button>
                            
                            {{-- 2. ポップオーバー本体 --}}
                            <div x-show="open"
                                @click.away="open = false"
                                x-transition
                                x-cloak
                                class="absolute z-20 right-0 mt-1 w-64 bg-white dark:bg-gray-900 rounded-md shadow-lg border border-gray-200 dark:border-gray-700
                                       max-h-[80vh] overflow-y-auto"
                            >
                                <div class="p-4">
                                    <h4 class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Filter Board</h4>
                                    <button @click="open = false" type="button" class="absolute top-2 right-2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>

                                    {{-- キーワード入力欄 --}}
                                    <div>
                                        <label for="filter-keyword" class="sr-only">Keyword</label>
                                        {{-- ★ 3. 修正: '$root.' を削除 --}}
                                        <input type="text" id="filter-keyword"
                                            x-model.debounce.300ms="filterKeyword" 
                                            class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Filter by keyword...">
                                    </div>

                                    {{-- ★★★ ここから新しいフィルタオプションの追加 (英語化済み) ★★★ --}}
        
                                    <div class="space-y-4">
                                        {{-- 1. Labels filter --}}
                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Labels</p>
                                            <div class="space-y-1">
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterLabels" value="" x-model="filterLabels" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">All</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterLabels" value="has" x-model="filterLabels" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Has Labels</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterLabels" value="none" x-model="filterLabels" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">No Labels</span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- 2. Due Date filter --}}
                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Due Date</p>
                                            <div class="space-y-1">
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="" x-model="filterPeriod" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">All</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="none_due" x-model="filterPeriod" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">No Due Date</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="overdue" x-model="filterPeriod" class="form-radio h-4 w-4 text-red-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Overdue</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="tomorrow" x-model="filterPeriod" class="form-radio h-4 w-4 text-yellow-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Due Tomorrow</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="this_week" x-model="filterPeriod" class="form-radio h-4 w-4 text-yellow-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Due This Week</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterPeriod" value="this_month" x-model="filterPeriod" class="form-radio h-4 w-4 text-yellow-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Due This Month</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- 3. Member filter --}}
                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Member</p>
                                            <div class="space-y-1">
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterMember" value="" x-model="filterMember" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">All</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterMember" value="mine" x-model="filterMember" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Assigned to Me</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterMember" value="none" x-model="filterMember" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">No Assignee</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- 4. Checklist filter --}}
                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Checklist</p>
                                            <div class="space-y-1">
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterChecklist" value="" x-model="filterChecklist" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">All</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterChecklist" value="has" x-model="filterChecklist" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Has Checklist</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterChecklist" value="none" x-model="filterChecklist" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">No Checklist</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- 5. Completion Status filter --}}
                                        <div class="pb-1">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Completion Status</p>
                                            <div class="space-y-1">
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterCompleted" value="" x-model="filterCompleted" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">All</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterCompleted" value="true" x-model="filterCompleted" class="form-radio h-4 w-4 text-green-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Completed</span>
                                                </label>
                                                <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="radio" name="filterCompleted" value="false" x-model="filterCompleted" class="form-radio h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600">
                                                    <span class="ms-2">Incomplete</span>
                                                </label>
                                            </div>
                                        </div>

                                    </div>
                                    {{-- ★★★ 新しいフィルタオプションの追加ここまで ★★★ --}}
                                </div>
                            </div>
                        </div>
                        {{-- ★ 修正: @click で open-invite-modal イベントを発火 --}}
                        <x-primary-button @click.prevent="showInviteModal = true">
                            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Invite
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </div>

        {{-- (2) カンバンボード本体 --}}
        <div class="p-4 sm:p-6 lg:p-8 h-full flex-grow overflow-x-auto">

            {{-- ★ 1. [修正] カンバンボード (x-show を追加) --}}
            <div x-show="viewMode === 'board'"
                 class="flex space-x-4 h-full" 
                 x-sortable @sortable-end.self="handleSortEnd($event.detail)">
                {{-- ▼▼▼ PHPの@foreachをAlpine.jsの<template x-for>に変更 ▼▼▼ --}}
                {{-- リストのループ --}}
                {{-- ★ 修正: 'lists' を 'filteredLists' に変更 --}}
                <template x-for="list in filteredLists" :key="list.id">
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
                                {{-- ★ 1. 'flex items-center p-3' を親に追加 --}}
                                <div class="card-item bg-white dark:bg-gray-800 rounded-md shadow flex items-center p-3"
                                    :data-card-id="card.id">

                                    {{-- ★ 2. [NEW] チェックボックス --}}
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" 
                                            :checked="card.is_completed"
                                            {{-- ★ 3. 新しいメソッドを呼び出す --}}
                                            @change="toggleCardCompletedFromBoard(card, $event.target.checked)"
                                            @click.stop {{-- ★ モーダルが開くのを防ぐ --}}
                                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500 w-5 h-5 mr-3">
                                    </div>

                                    {{-- ★ 4. [MODIFIED] メインコンテンツ (モーダルを開くトリガー) --}}
                                    <div @click="selectedCardId = card.id" class="flex-grow cursor-pointer min-w-0">
                                        {{-- ラベルバー表示 --}}
                                        <div class="flex flex-wrap gap-1 mb-2" x-show="card.labels && card.labels.length > 0">
                                            <template x-for="label in (card.labels || [])" :key="label.id">
                                                <span :class="label.color"
                                                    :title="label.name"
                                                    class="h-2 w-10 rounded-full">
                                                </span>
                                            </template>
                                        </div>

                                        {{-- ★ 5. タイトルに line-through を追加 --}}
                                        <p class="text-sm text-gray-800 dark:text-gray-100" 
                                        :class="{ 'line-through text-gray-500 dark:text-gray-400': card.is_completed }"
                                        x-text="card.title">
                                        </p>

                                        {{-- ★★★ [NEW] カードフッター (タイトルの下、divの中に挿入) ★★★ --}}
                                        <div class="flex items-center gap-3 mt-2 text-gray-500 dark:text-gray-400">
                                            
                                            {{-- 1. 説明文アイコン --}}
                                            <template x-if="card.description">
                                                <div title="This card has a description">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                                                </div>
                                            </template>

                                            {{-- 2. コメント数 --}}
                                            <template x-if="card.comments && card.comments.length > 0">
                                                <div class="flex items-center gap-1 text-xs" title="Comments">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                                    <span x-text="card.comments.length"></span>
                                                </div>
                                            </template>

                                            {{-- 3. 添付ファイル数 --}}
                                            <template x-if="card.attachments && card.attachments.length > 0">
                                                <div class="flex items-center gap-1 text-xs" title="Attachments">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.415a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                    <span x-text="card.attachments.length"></span>
                                                </div>
                                            </template>

                                            {{-- 4. チェックリスト進捗 --}}
                                            <template x-if="card.checklists && card.checklists.some(c => c.items.length > 0)">
                                                <div class="flex items-center gap-1 text-xs" title="Checklist items">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <span x-text="
                                                        (() => {
                                                            let total = 0;
                                                            let completed = 0;
                                                            card.checklists.forEach(list => {
                                                                total += list.items.length;
                                                                completed += list.items.filter(i => i.is_completed).length;
                                                            });
                                                            return completed + '/' + total;
                                                        })()
                                                    "></span>
                                                </div>
                                            </template>

                                        </div>
                                        {{-- ★★★ フッターここまで ★★★ --}}
                                    </div>
                                    
                                    {{-- ★ 6. [MOVED] 削除ボタン (右端に配置) --}}
                                    <div class="flex-shrink-0 ml-2">
                                        <button @click.prevent.stop="deleteCard(card, list)" 
                                                class="p-1 text-gray-400 rounded-md hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                            {{-- 「カードを追加」UI (トップレベルの newCardForm を参照) --}}
                            {{-- リストフッター --}}
                            <div class="list-footer p-3 border-t border-gray-200 dark:border-gray-600">
                                <div class="mt-2">

                                    <div x-show="newCardForm.listId !== list.id">
                                        <button @click="newCardForm.listId = list.id; $nextTick(() => $nextTick(() => { if ($refs['newCardTitleInput_' + list.id]) { $refs['newCardTitleInput_' + list.id].focus(); } }))"
                                                class="w-full p-2 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm font-medium text-left">
                                            + Add a card
                                        </button>
                                    </div>

                                    <div x-show="newCardForm.listId === list.id" x-cloak>
                                        <form @submit.prevent="submitNewCard(list)" 
                                            class="space-y-2">
                                            
                                            {{-- テキストエリア --}}
                                            <textarea x-model="newCardForm.title"
                                                    :x-ref="'newCardTitleInput_' + list.id"
                                                    {{-- ★ 入力したらエラーを消す --}}
                                                    @input="newCardForm.error = null"
                                                    @keydown.escape.prevent="newCardForm.listId = null; newCardForm.title = ''; newCardForm.error = null"
                                                    @keydown.enter.prevent="$event.target.form.requestSubmit()"
                                                    {{-- ★ エラー時に枠線を赤くするクラスバインディングを追加 --}}
                                                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': newCardForm.error }"
                                                    class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-colors duration-200"
                                                    rows="3" 
                                                    placeholder="Enter a title for this card..."></textarea>
                                            
                                            {{-- ★★★ エラーメッセージ表示エリア ★★★ --}}
                                            <div x-show="newCardForm.error" x-transition class="text-red-500 text-xs font-medium ml-1">
                                                <span x-text="newCardForm.error"></span>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <x-primary-button type="submit">Add card</x-primary-button>
                                                <button @click="newCardForm.listId = null; newCardForm.title = ''; newCardForm.error = null"
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
                
            </div>

            {{-- ★ 2. [MODIFIED] カレンダービュー用コンテナ --}}
            <div x-show="viewMode === 'calendar'" x-cloak
                 x-ref="calendarContainer" {{-- ★ 1. JSが参照するための名前 --}}
                 
                 {{-- ★ 2. 表示された時にカレンダーを初期化する --}}
                 x-init="$watch('viewMode', (value) => {
                    if (value === 'calendar') {
                        // 少し待機しないとカレンダーのサイズ計算が失敗することがある
                        setTimeout(() => initCalendar(), 50); 
                    }
                 })"
                 class="w-full h-full bg-white dark:bg-gray-800 rounded-md shadow p-4"
            >
                 {{-- (TODOの <p> タグは削除) --}}
            </div>

            {{-- ★ [NEW] タイムラインビュー用コンテナ (timeGrid) --}}
            <div x-show="viewMode === 'timeline'" x-cloak
                 x-ref="timelineContainer" {{-- ★ 1. JSが参照するための名前 --}}
                 class="w-full h-full bg-white dark:bg-gray-800 rounded-md shadow p-4"
            >
                {{-- (initTimeline がここに描画します) --}}
            </div>            

            {{-- ★ ここから追加: カード詳細モーダル --}}
            <x-card-detail-modal />
            {{-- ★ 追加ここまで --}}
        </div>
        {{-- ★ ここに追加: 招待モーダル --}}
        <x-board-invite-modal :board="$board" />
    </div> {{-- ★ x-data の閉じ div --}}
</x-app-layout>