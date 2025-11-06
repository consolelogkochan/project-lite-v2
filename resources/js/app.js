import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ★ ここから追加
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // flatpickrのCSS

window.flatpickr = flatpickr; // Alpine.jsから使えるようグローバルに公開
// ★ 追加ここまで