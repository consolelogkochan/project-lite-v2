import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;

// --- 1. ライブラリのインポートを先に ---

// flatpickr (日付ピッカー)
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
window.flatpickr = flatpickr;

// FullCalendar (本体と全プラグイン)
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

// グローバルスコープ（window）に登録
window.FullCalendar = {
    Calendar: Calendar,
    dayGridPlugin: dayGridPlugin,
    interactionPlugin: interactionPlugin,
    
};

// --- 2. 最後に Alpine を起動 ---
Alpine.start();