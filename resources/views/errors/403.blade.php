<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-[80vh] text-center px-4">
        {{-- 数字: text-gray-400 --}}
        <div class="text-9xl font-black text-gray-400 dark:text-gray-700 select-none">
            403
        </div>
        <div class="-mt-12 relative z-10">
            {{-- タイトル: text-black --}}
            <h1 class="text-3xl font-bold text-black dark:text-white tracking-tight">
                Access Denied
            </h1>
            {{-- 本文: text-gray-800 --}}
            <p class="text-gray-800 dark:text-gray-300 mt-4 text-lg max-w-md mx-auto leading-relaxed">
                You do not have permission to access this page.<br>
                Please contact the administrator or check your account settings.
            </p>
            <div class="mt-8">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-500 font-medium dark:text-indigo-400 hover:underline">
                    Back to Dashboard &rarr;
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>