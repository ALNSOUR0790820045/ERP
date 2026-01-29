<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class UserPasswordHistory extends Model
{
    protected $table = 'user_password_history';

    protected $fillable = [
        'user_id',
        'password_hash',
        'changed_at',
        'changed_by',
        'change_reason',
        'ip_address',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // التحقق من تكرار كلمة المرور
    public static function isPasswordUsedBefore(int $userId, string $password, int $historyCount = 5): bool
    {
        $history = static::where('user_id', $userId)
            ->orderBy('changed_at', 'desc')
            ->limit($historyCount)
            ->pluck('password_hash');

        foreach ($history as $hash) {
            if (Hash::check($password, $hash)) {
                return true;
            }
        }

        return false;
    }

    // حفظ كلمة المرور في السجل
    public static function recordPassword(User $user, ?int $changedBy = null, ?string $reason = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'password_hash' => $user->password,
            'changed_at' => now(),
            'changed_by' => $changedBy,
            'change_reason' => $reason,
            'ip_address' => request()->ip(),
        ]);
    }
}
