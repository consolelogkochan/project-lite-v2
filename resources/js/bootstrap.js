import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Sortable = Sortable; // ★ この行を追加

window.Alpine = Alpine;

// Alpine.jsにSortableJSを連携させる
Alpine.directive('sortable', (el, { expression }, { evaluate }) => {

    let options = expression ? evaluate(expression) : {};

    // SortableJSを初期化
    Sortable.create(el, {
        ...options,

        // ▼▼▼ ここを修正 ▼▼▼
        onEnd: (evt) => {
            // Alpine.jsのメソッドを直接呼ばず、
            // 'sortable-end' という名前のカスタムイベントを発信する
            el.dispatchEvent(new CustomEvent('sortable-end', {
                bubbles: true,
                detail: {
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex
                }
            }));
        }
        // ▲▲▲ 修正ここまで ▲▲▲
    });
});

// (Alpine.start()はapp.jsが実行するので、ここにはありません)