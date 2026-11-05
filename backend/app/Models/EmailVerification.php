<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'purpose',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function issue(User $user, string $purpose): string
    {
        $user->emailVerifications()->where('purpose', $purpose)->whereNull('used_at')->delete();

        return $user->emailVerifications()->create([
            'code' => random_int(100000, 999999),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(15),
        ])->code;
    }
}
