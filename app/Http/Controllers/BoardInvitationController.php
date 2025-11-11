<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\User; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Validator; 
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BoardInvitationController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * ボードに招待するユーザーを検索する (API)
     */
    public function searchUsers(Request $request, Board $board)
    {
        // ★ 認可(Policy)チェック: ユーザーはこのボードに 'addMember' (招待) できるか？
        $this->authorize('addMember', $board);
        
        // バリデーション
        $request->validate([
            'q' => 'required|string|min:2', // 検索クエリ (q) は2文字以上
        ]);

        $query = $request->input('q');

        // 1. このボードに既に所属している全メンバーのIDリストを取得
        $existingMemberIds = $board->users()->pluck('users.id')->toArray();

        // 2. 検索クエリに一致し、かつ「既にメンバーであるID」を除外する
        $users = User::where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->whereNotIn('id', $existingMemberIds) // ★ 既にメンバーの人は除外
                    ->select('id', 'name', 'email', 'avatar') // ★ 必要なカラムのみ
                    ->limit(10) // 最大10件まで
                    ->get();

        return response()->json($users);
    }

    public function inviteUser(Request $request, Board $board)
    {
        // ★ 認可(Policy)チェック: 'addMember' (招待) 権限
        $this->authorize('addMember', $board);
        
        // ... (バリデーション)
        $validator = Validator::make($request->all(), [
            'user_id' => [
                'required', 'integer', 'exists:users,id',
                Rule::unique('board_user')->where(function ($query) use ($board) {
                    return $query->where('board_id', $board->id);
                }),
            ],
            'role' => ['required', 'string', Rule::in(['member', 'admin', 'guest'])]
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 3. ユーザーを中間テーブル (board_user) に追加
        $board->users()->attach($request->user_id, [
            'role' => $request->role
        ]);

        // ★★★ ここから修正 ★★★
        // 4. 招待（追加）したユーザーの情報を 'pivot' 付きで取得
        $invitedUser = $board->users()->find($request->user_id);
        
        // 201 Created でJSONを返す
        return response()->json($invitedUser, 201);
        // ★★★ 修正ここまで ★★★
    }

    /**
     * ボードの現在の全メンバーを取得する (API)
     * ★ このメソッドを追加
     */
    public function getMembers(Board $board)
    {
        // ★ 認可(Policy)チェック: 'view' (閲覧) 権限
        $this->authorize('view', $board);
        
        // 'users' リレーションを読み込む
        // (Board モデルの users() メソッドで withPivot('role') が設定済み)
        $members = $board->users;

        // $members コレクションには、各ユーザーの 'pivot' プロパティに 'role' が含まれる
        
        return response()->json($members);
    }

    /**
     * ボードメンバーの役割を更新する (API)
     * ★ このメソッドを追加
     */
    public function updateRole(Request $request, Board $board, User $user)
    {
        // ★ 認可(Policy)チェック: 'addMember' (招待/メンバー管理) 権限
        $this->authorize('addMember', $board);
        
        // （自分自身の役割を 'member' に降格しようとするのは許可するが、
        //    最後の管理者が自分自身の場合は降格を禁止する、などのロジックも将来追加可能）

        // ★ 3. バリデーション
        $validator = Validator::make($request->all(), [
            'role' => [
                'required',
                'string',
                Rule::in(['admin', 'member', 'guest']), // 許可する役割
            ]
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // ★ 4. 役割を更新
        // 'board_user' 中間テーブルの 'role' カラムを更新
        $board->users()->updateExistingPivot($user->id, [
            'role' => $request->role
        ]);
        
        // 更新後のメンバー情報を返す (pivot 付きで)
        $updatedMember = $board->users()->find($user->id);

        return response()->json($updatedMember, 200);
    }

    /**
     * ボードメンバーを退出させる (API)
     * ★ このメソッドを追加
     */
    public function removeMember(Request $request, Board $board, User $user)
    {
        // ★ 認可(Policy)チェック: 'addMember' (招待/メンバー管理) 権限
        $this->authorize('addMember', $board);

        // ★ 2. 認可チェック (オーナーは削除不可)
        // 対象ユーザー($user)がこのボードのオーナー($board->owner_id)か
        if ($user->id === $board->owner_id) {
            return response()->json(['message' => 'Forbidden: The board owner cannot be removed.'], 403);
        }
        
        // ★ 3. メンバーを退出させる (中間テーブルから削除)
        $board->users()->detach($user->id);

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }

    /**
     * 認証済みユーザー自身がボードから退出する (API)
     * ★ このメソッドを追加
     */
    public function leaveBoard(Request $request, Board $board)
    {
        $currentUser = Auth::user();

        // ★ 1. 認可チェック (オーナーは退出不可)
        if ($currentUser->id === $board->owner_id) {
            return response()->json(['message' => 'Forbidden: The board owner cannot leave the board. You must delete the board or transfer ownership.'], 403);
        }
        
        // ★ 2. メンバーを退出させる (中間テーブルから自分自身を削除)
        $board->users()->detach($currentUser->id);

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }
}
