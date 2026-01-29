<?php

namespace App\Models\Security;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PasswordPolicy extends Model
{
    protected $table = 'password_policies';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'min_length',
        'max_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_special_chars',
        'password_history_count',
        'max_age_days',
        'min_age_days',
        'lockout_threshold',
        'lockout_duration_minutes',
        'session_timeout_minutes',
        'force_change_on_first_login',
        'forbidden_words',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_special_chars' => 'boolean',
        'force_change_on_first_login' => 'boolean',
        'forbidden_words' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'password_policy_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'password_policy_id');
    }

    // التحقق من صحة كلمة المرور
    public function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->min_length) {
            $errors[] = "كلمة المرور يجب أن تكون {$this->min_length} أحرف على الأقل";
        }

        if (strlen($password) > $this->max_length) {
            $errors[] = "كلمة المرور يجب ألا تتجاوز {$this->max_length} حرف";
        }

        if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير';
        }

        if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف صغير';
        }

        if ($this->require_numbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رقم';
        }

        if ($this->require_special_chars && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رمز خاص';
        }

        if ($this->forbidden_words) {
            foreach ($this->forbidden_words as $word) {
                if (stripos($password, $word) !== false) {
                    $errors[] = 'كلمة المرور تحتوي على كلمات محظورة';
                    break;
                }
            }
        }

        return $errors;
    }

    // الحصول على السياسة الافتراضية
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    // توليد regex للتحقق
    public function getValidationRegex(): string
    {
        $pattern = '^';
        
        if ($this->require_uppercase) $pattern .= '(?=.*[A-Z])';
        if ($this->require_lowercase) $pattern .= '(?=.*[a-z])';
        if ($this->require_numbers) $pattern .= '(?=.*[0-9])';
        if ($this->require_special_chars) $pattern .= '(?=.*[!@#$%^&*(),.?":{}|<>])';
        
        $pattern .= ".{{$this->min_length},{$this->max_length}}$";
        
        return $pattern;
    }
}
