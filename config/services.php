<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mobile_money' => [
        // Dépôt/retrait simulé pour un opérateur non branché (dev local,
        // tests). Ignoré en production — voir CashioController.
        'simulate_unintegrated' => (bool) env('MOBILE_MONEY_SIMULATION', false),
    ],

    'anthropic' => [
        // Chatbot support (SupportController::chat) — voir
        // AiSupportChatService. Clé absente ou invalide = repli automatique
        // sur l'ancien système mot-clé (ChatbotAnswerResource), jamais
        // d'erreur exposée au client.
        'key' => env('ANTHROPIC_API_KEY'),
        // Au-delà, repli sur les réponses par mot-clé (voir AiSupportBudget).
        'daily_limit_per_account' => (int) env('ANTHROPIC_DAILY_LIMIT_PER_ACCOUNT', 30),
        'daily_limit_global' => (int) env('ANTHROPIC_DAILY_LIMIT_GLOBAL', 500),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
