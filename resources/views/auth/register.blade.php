<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" /> {{-- ← この行を追加 --}}
    
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Invitation Code -->
        <div class="mt-4">
            <x-input-label for="invitation_code" :value="__('Invitation Code')" />
            <x-text-input id="invitation_code" class="block mt-1 w-full"
                            type="text"
                            name="invitation_code"
                            :value="old('invitation_code')"
                            required />
            <x-input-error :messages="$errors->get('invitation_code')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- パスワード表示チェックボックスを追加 --}}
        <div class="block mt-2">
            <label for="show_password_register" class="inline-flex items-center">
                <input id="show_password_register" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" onclick="togglePasswordVisibility('password', 'password_confirmation', this)">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Show Password') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<script>
    function togglePasswordVisibility(passwordFieldId, confirmationFieldId, checkbox) {
        const passwordField = document.getElementById(passwordFieldId);
        const confirmationField = confirmationFieldId ? document.getElementById(confirmationFieldId) : null;

        if (checkbox.checked) {
            passwordField.type = 'text';
            if (confirmationField) {
                confirmationField.type = 'text';
            }
        } else {
            passwordField.type = 'password';
            if (confirmationField) {
                confirmationField.type = 'password';
            }
        }
    }
</script>