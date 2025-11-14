<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'sender',
        'channel',
        'mode',
        'started_at',
        'first_response_at',
        'first_response_ms',
        'finished_at',
        'total_duration_ms',
        'satisfaction',
        'escalated',
        'concluded',
        'awaiting_satisfaction',
        'business_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'first_response_at' => 'datetime',
        'finished_at' => 'datetime',
        'escalated' => 'boolean',
        'concluded' => 'boolean',
        'awaiting_satisfaction' => 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(\App\Models\Message::class, 'conversation_id');
    }

    public function metric()
    {
        return $this->hasOne(ConversationMetric::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
