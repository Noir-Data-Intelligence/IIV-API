<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'kicker', 'title', 'subtitle', 'cta_label', 'cta_link',
        'image_url', 'sort_order', 'published',
    ];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
