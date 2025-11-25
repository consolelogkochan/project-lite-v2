<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- ★ 修正1: px-4 を追加してスマホでの横余白を確保 --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- 1. 招待コード管理セクション --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-4 sm:p-6">
                {{-- ★ 修正2: ヘッダーを flex-col (縦) -> sm:flex-row (横) に変更 --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Unused Invitation Codes</h3>
                    
                    <form action="{{ route('admin.invitation.generate') }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        {{-- ボタンをスマホで幅いっぱいに --}}
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Generate Code
                        </button>
                    </form>
                </div>

                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($unusedCodes->count() > 0)
                    {{-- ★ 修正3: グリッドを grid-cols-1 (スマホ) からスタート --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach ($unusedCodes as $code)
                            <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-md text-center relative group border border-gray-200 dark:border-gray-600">
                                <span class="text-lg font-mono font-bold text-gray-800 dark:text-gray-200 select-all break-all">
                                    {{ $code->code }}
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    @if($code->expires_at)
                                        Expires: {{ $code->expires_at->format('Y-m-d') }}
                                    @else
                                        No Expiration
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">No unused codes available.</p>
                @endif
            </div>

            {{-- 2. ユーザーリストセクション --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Registered Users</h3>
                
                {{-- テーブルの横スクロールコンテナ --}}
                <div class="overflow-x-auto -mx-4 sm:mx-0">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Joined</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $user->id }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <div class="flex items-center">
                                                {{-- 名前が長い場合の省略対応 --}}
                                                <span class="truncate max-w-[100px] sm:max-w-none">{{ $user->name }}</span>
                                                @if($user->is_admin)
                                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                        Admin
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                            {{ $user->invitationCode ? $user->invitationCode->code : '-' }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $user->created_at->format('Y-m-d') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>