<div
    x-data="{ 
        show: false, 
        selectedColor: '#6366F1'
    }"
    x-show="show"
    @open-create-board-modal.window="show = true"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50"
>
    <div @click.away="show = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
        {{-- モーダルヘッダー --}}
        <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Create New Board</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        <form method="POST" action="{{ route('boards.store') }}">
            @csrf
            <div class="p-6 space-y-4">

                {{-- ボードタイトル入力 --}}
                <div>
                    <x-input-label for="title" :value="__('Board Title')" class="text-indigo-500" />
                    <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" required autofocus />
                </div>

                {{-- 背景色選択 --}}
                <div>
                    <x-input-label :value="__('Background Color')" class="text-indigo-500" />
                    <div class="flex justify-between mt-2">
                        @php $colors = ['#6366F1', '#EC4899', '#10B981', '#F59E0B', '#3B82F6']; @endphp
                        
                        @foreach ($colors as $color)
                            {{-- ▼▼▼ <label>と<input>を削除し、:classのロジックを修正 ▼▼▼ --}}
                            <div 
                                class="w-12 h-8 rounded-md cursor-pointer ring-2 ring-offset-2" 
                                :class="selectedColor === '{{ $color }}' ? 'ring-indigo-500' : 'ring-transparent'"
                                style="background-color: {{ $color }}"
                                @click="selectedColor = '{{ $color }}'"
                            ></div>
                        @endforeach
                    </div>
                    
                    <input type="hidden" name="background_color" :value="selectedColor">
                </div>

            </div>

            {{-- モーダルフッター --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t text-right">
                <x-primary-button>
                    {{ __('Create Board') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>