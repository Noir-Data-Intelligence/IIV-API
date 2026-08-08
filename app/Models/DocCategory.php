<?php

namespace App\Models;

use Database\Factories\DocCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocCategory extends Model
{
    /** @use HasFactory<DocCategoryFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'icon', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
