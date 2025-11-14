<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMetric extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'conversation_id',
        'avg_response_time_ms',
        'total_messages',
        'user_messages',
        'bot_messages',
        'total_duration_ms',
        'mode',
        'escalated',
        'satisfaction',
    ];

    protected $casts = [
        'escalated' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
