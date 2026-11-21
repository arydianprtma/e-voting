<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_name',
        'study_program',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Dapatkan daftar Jurusan unik yang aktif diisi oleh admin.
     */
    public static function getActiveFaculties(): array
    {
        return self::active()
            ->distinct()
            ->orderBy('faculty_name', 'asc')
            ->pluck('faculty_name')
            ->toArray();
    }

    /**
     * Dapatkan daftar Prodi terkelompok per Jurusan.
     */
    public static function getGroupedByFaculty(): array
    {
        return self::active()
            ->orderBy('faculty_name', 'asc')
            ->orderBy('study_program', 'asc')
            ->get()
            ->groupBy('faculty_name')
            ->map(fn ($items) => $items->pluck('study_program')->toArray())
            ->toArray();
    }
}
