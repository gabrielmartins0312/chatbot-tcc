<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'title',
        'content',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
