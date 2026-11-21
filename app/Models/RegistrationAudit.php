<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'faculty',
        'status',
        'reason',
        'ip_address',
        'user_agent',
    ];

    public static function log(string $studentId, string $name, ?string $faculty, string $status, string $reason): self
    {
        return self::create([
            'student_id' => $studentId,
            'name' => $name,
            'faculty' => $faculty,
            'status' => $status,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
