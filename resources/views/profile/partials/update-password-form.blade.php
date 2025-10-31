<section>
    <header id="update-password-header">
        <h2 class="text-lg font-medium text-indigo-700">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- パスワード表示チェックボックスを追加 --}}
        <div class="block mt-2">
            <label for="show_password_profile" class="inline-flex items-center">
                <input id="show_password_profile" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" onclick="togglePasswordVisibility(this, 'update_password_current_password', 'update_password_password', 'update_password_password_confirmation')">
                <span class="ms-2 text-sm text-gray-600">{{ __('Show Passwords') }}</span>
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="
                        setTimeout(() => show = false, 5000);
                        $nextTick(() => document.getElementById('update-password-header').scrollIntoView({ behavior: 'smooth' }));
                    "
                    class="text-sm text-green-600"
                >{{ __('Password updated successfully.') }}</p>
            @endif
        </div>
    </form>
</section>

<script>
    function togglePasswordVisibility(checkbox, ...fieldIds) {
        const isChecked = checkbox.checked;
        fieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.type = isChecked ? 'text' : 'password';
            }
        });
    }
</script>

@if($errors->updatePassword->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('update-password-header').scrollIntoView({ behavior: 'smooth' });
        });
    </script>
@endif