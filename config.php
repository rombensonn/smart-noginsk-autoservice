<?php

declare(strict_types=1);

return [
    'site' => [
        'name' => 'Смарт',
        'city' => 'Ногинск',
        'address' => 'ул. 8 Марта, 3, Ногинск',
        'phone' => '+7 (925) 670-94-35',
        'phone_href' => '+79256709435',
        'site_url' => 'https://example.ru', // TODO: замените на боевой домен перед публикацией.
        'timezone' => 'Europe/Moscow',
    ],

    'storage' => [
        'path' => __DIR__ . '/storage',
        'leads_file' => 'leads.jsonl',
        'consent_file' => 'consent-log.jsonl',
        'rate_limit_file' => 'rate-limit.json',
    ],

    'lead' => [
        'success_message' => 'Заявка отправлена. Мы свяжемся с вами для уточнения деталей и записи.',
        'consent_text_version' => '2026-05-13-smart-site-v1',
    ],

    'rate_limit' => [
        'max_requests' => 5,
        'window_seconds' => 3600,
    ],

    'mail' => [
        'enabled' => false,
        'to' => '[УКАЗАТЬ EMAIL ДЛЯ ЗАЯВОК]',
        'from' => 'noreply@example.ru',
        'from_name' => 'Сайт автосервиса Смарт',
    ],

    'telegram' => [
        'enabled' => false,
        'bot_token' => '[УКАЗАТЬ TELEGRAM BOT TOKEN]',
        'chat_id' => '[УКАЗАТЬ TELEGRAM CHAT ID]',
    ],

    'amocrm' => [
        'enabled' => false,
        'webhook_url' => '[УКАЗАТЬ AMOCRM WEBHOOK URL]',
    ],
];
