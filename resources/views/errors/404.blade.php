<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-[80vh] text-center px-4">
        {{-- 数字の部分: text-gray-400 (濃いめのグレー) --}}
        <div class="text-9xl font-black text-gray-400 dark:text-gray-700 select-none">
            404
        </div>

        <div class="-mt-12 relative z-10">
            {{-- タイトル: text-black (完全な黒) --}}
            <h1 class="text-3xl font-bold text-black dark:text-white tracking-tight">
                Page Not Found
            </h1>
            
            {{-- 本文: text-gray-800 (濃いグレー) --}}
            <p class="text-gray-800 dark:text-gray-300 mt-4 text-lg max-w-md mx-auto leading-relaxed">
                Sorry, the page you are looking for could not be found.<br>
                It may have been removed or the URL might be incorrect.
            </p>

            <div class="mt-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>