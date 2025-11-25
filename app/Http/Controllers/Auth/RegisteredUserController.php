<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InvitationCode; // この行を追加
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Validation\Rule; // この行を追加

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // --- ▼▼▼ このブロックを丸ごと追加 ▼▼▼ ---
        $existingInactiveUser = User::where('email', $request->email)
                                    ->where('status', 'inactive')
                                    ->whereNull('email_verified_at')
                                    ->first();

        if ($existingInactiveUser) {
            $existingInactiveUser->sendEmailVerificationNotification();

            // 登録画面に戻り、メッセージを表示する
            return back()->with('status', 'verification-link-sent');
        }
        // --- ▲▲▲ 追加ブロックここまで ▲▲▲ ---

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invitation_code' => [ // このブロックを追加
                'required',
                'string',
                Rule::exists('invitation_codes', 'code')->where('is_used', false),
            ],
        ]);

        // 招待コードを検索
        $invitationCode = InvitationCode::where('code', $request->invitation_code)->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 招待コードを使用済みに更新
        // ★ 修正: 招待コードを使用済みにし、ユーザーIDを紐付ける
        $invitationCode->update([
            'is_used' => true,
            'user_id' => $user->id, // ★ これを追加
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice'));
    }
}
