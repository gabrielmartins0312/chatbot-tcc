<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminConversationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook', function (Request $request) {
    Log::info('🔍 Verificação do Webhook iniciada', $request->all());
    $verifyToken = 'gabriel_tcc_2025';

    $mode      = $request->query('hub_mode')        ?? $request->query('hub.mode');
    $token     = $request->query('hub_verify_token')?? $request->query('hub.verify_token');
    $challenge = $request->query('hub_challenge')   ?? $request->query('hub.challenge');

    if ($mode === 'subscribe' && $token === $verifyToken) {
        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    return response('Token inválido', 403);
});

Route::post('/webhook', function (Request $request) {
    Log::info('📩 Webhook recebido:', $request->all());
    return response('EVENT_RECEIVED', 200);
});
