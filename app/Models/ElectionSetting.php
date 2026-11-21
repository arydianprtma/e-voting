<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'organization_name',
        'logo_path',
        'announcement',
        'start_time',
        'end_time',
        'is_active',
        'show_quick_count',
        'helpdesk_contact',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'show_quick_count' => 'boolean',
    ];

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }

        return asset('images/logo-default.png');
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'title' => 'Pemilihan Raya Mahasiswa 2026/2027',
            'organization_name' => 'Komisi Pemilihan Umum Mahasiswa',
            'announcement' => 'Gunakan hak suara Anda secara jujur, adil, langsung, umum, bebas, dan rahasia.',
            'helpdesk_contact' => '081234567890',
            'is_active' => true,
            'show_quick_count' => false,
        ]);
    }
}
