<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['site']['timezone'] ?? 'Europe/Moscow');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow', true);

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function clean_string(mixed $value, int $maxLength = 500): string
{
    $value = is_string($value) ? $value : '';
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }

    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }

    if (strlen($digits) !== 11 || $digits[0] !== '7') {
        return '';
    }

    return sprintf(
        '+7 (%s) %s-%s-%s',
        substr($digits, 1, 3),
        substr($digits, 4, 3),
        substr($digits, 7, 2),
        substr($digits, 9, 2)
    );
}

function get_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function storage_path(array $config, string $fileName): string
{
    $storage = rtrim($config['storage']['path'], '/\\');

    if (!is_dir($storage)) {
        mkdir($storage, 0755, true);
    }

    return $storage . DIRECTORY_SEPARATOR . $fileName;
}

function append_jsonl(string $path, array $record): bool
{
    $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

function check_rate_limit(array $config, string $ip): bool
{
    $path = storage_path($config, $config['storage']['rate_limit_file']);
    $window = (int) ($config['rate_limit']['window_seconds'] ?? 3600);
    $maxRequests = (int) ($config['rate_limit']['max_requests'] ?? 5);
    $now = time();

    $handle = fopen($path, 'c+');
    if ($handle === false) {
        return true;
    }

    try {
        flock($handle, LOCK_EX);
        $raw = stream_get_contents($handle);
        $data = $raw ? json_decode($raw, true) : [];
        $data = is_array($data) ? $data : [];
        $entries = array_filter($data[$ip] ?? [], static fn ($time) => is_int($time) && $time > $now - $window);

        if (count($entries) >= $maxRequests) {
            return false;
        }

        $entries[] = $now;
        $data[$ip] = array_values($entries);

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function send_mail_notification(array $config, array $lead): void
{
    if (empty($config['mail']['enabled'])) {
        return;
    }

    $to = clean_string($config['mail']['to'] ?? '', 160);
    $from = clean_string($config['mail']['from'] ?? '', 160);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $serviceForSubject = str_replace(["\r", "\n"], ' ', $lead['service'] ?: 'заявка');
    $subject = 'Новая заявка с сайта Смарт — ' . $serviceForSubject;
    $fromName = clean_string($config['mail']['from_name'] ?? 'Сайт Смарт', 120);

    $utm = $lead['utm'];
    $body = implode(PHP_EOL, [
        'Новая заявка с сайта автосервиса Смарт',
        '',
        'Имя: ' . $lead['name'],
        'Телефон: ' . $lead['phone'],
        'Автомобиль: ' . ($lead['car'] ?: 'Не указан'),
        'Услуга: ' . ($lead['service'] ?: 'Не выбрано'),
        'Комментарий: ' . ($lead['message'] ?: 'Нет комментария'),
        'Страница: ' . $lead['page_url'],
        'UTM source: ' . ($utm['utm_source'] ?: '-'),
        'UTM medium: ' . ($utm['utm_medium'] ?: '-'),
        'UTM campaign: ' . ($utm['utm_campaign'] ?: '-'),
        'UTM content: ' . ($utm['utm_content'] ?: '-'),
        'UTM term: ' . ($utm['utm_term'] ?: '-'),
        'Время: ' . $lead['created_at'],
        'IP: ' . $lead['ip'],
        'User-Agent: ' . $lead['user_agent'],
        'Чекбокс 1: да',
        'Чекбокс 2: да',
    ]);

    $encodedFromName = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($fromName, 'UTF-8')
        : '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $encodedFromName . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

function send_telegram_notification(array $config, array $lead): void
{
    if (empty($config['telegram']['enabled'])) {
        return;
    }

    $token = (string) ($config['telegram']['bot_token'] ?? '');
    $chatId = (string) ($config['telegram']['chat_id'] ?? '');

    if ($token === '' || str_contains($token, '[') || $chatId === '' || str_contains($chatId, '[')) {
        return;
    }

    $text = implode(PHP_EOL, [
        'Новая заявка с сайта Смарт',
        'Имя: ' . $lead['name'],
        'Телефон: ' . $lead['phone'],
        'Авто: ' . ($lead['car'] ?: 'Не указан'),
        'Услуга: ' . ($lead['service'] ?: 'Не выбрано'),
        'Комментарий: ' . ($lead['message'] ?: 'Нет комментария'),
        'Страница: ' . $lead['page_url'],
    ]);

    $payload = http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => '1',
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 4,
        ],
    ]);

    @file_get_contents('https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage', false, $context);
}

function send_amocrm_webhook(array $config, array $lead): void
{
    if (empty($config['amocrm']['enabled'])) {
        return;
    }

    $url = (string) ($config['amocrm']['webhook_url'] ?? '');
    if ($url === '' || str_contains($url, '[')) {
        return;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($lead, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timeout' => 4,
        ],
    ]);

    @file_get_contents($url, false, $context);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Некорректный способ отправки.'], 405);
    }

    $honeypot = clean_string($_POST['company_website'] ?? '', 120);
    if ($honeypot !== '') {
        json_response(['success' => true, 'message' => $config['lead']['success_message']]);
    }

    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
        json_response([
            'success' => false,
            'message' => 'Не удалось проверить отправку формы. Обновите страницу и попробуйте ещё раз.',
            'errors' => ['form' => 'Обновите страницу и повторите отправку.'],
        ], 403);
    }

    $name = clean_string($_POST['name'] ?? '', 80);
    $phone = normalize_phone(clean_string($_POST['phone'] ?? '', 80));
    $car = clean_string($_POST['car'] ?? '', 120);
    $service = clean_string($_POST['service'] ?? 'Не выбрано', 160);
    $message = clean_string($_POST['message'] ?? '', 1200);
    $pageUrl = clean_string($_POST['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''), 500);
    $userAgent = clean_string($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 500);
    $ip = get_client_ip();

    $consentPersonalData = isset($_POST['consent_personal_data']) && $_POST['consent_personal_data'] === '1';
    $consentPolicy = isset($_POST['consent_policy']) && $_POST['consent_policy'] === '1';

    $errors = [];
    if (function_exists('mb_strlen') ? mb_strlen($name) < 2 : strlen($name) < 2) {
        $errors['name'] = 'Укажите имя минимум из 2 символов.';
    }

    if ($phone === '') {
        $errors['phone'] = 'Укажите телефон в российском формате.';
    }

    if (!$consentPersonalData) {
        $errors['consent_personal_data'] = 'Нужно отдельное согласие на обработку персональных данных.';
    }

    if (!$consentPolicy) {
        $errors['consent_policy'] = 'Нужно подтвердить ознакомление с политикой обработки персональных данных.';
    }

    if ($errors !== []) {
        json_response([
            'success' => false,
            'message' => 'Проверьте поля формы.',
            'errors' => $errors,
        ], 422);
    }

    if (!check_rate_limit($config, $ip)) {
        json_response([
            'success' => false,
            'message' => 'Слишком много заявок с этого адреса. Попробуйте позже или позвоните нам.',
            'errors' => ['form' => 'Лимит отправки временно превышен.'],
        ], 429);
    }

    $utm = [
        'utm_source' => clean_string($_POST['utm_source'] ?? '', 160),
        'utm_medium' => clean_string($_POST['utm_medium'] ?? '', 160),
        'utm_campaign' => clean_string($_POST['utm_campaign'] ?? '', 160),
        'utm_content' => clean_string($_POST['utm_content'] ?? '', 160),
        'utm_term' => clean_string($_POST['utm_term'] ?? '', 160),
    ];

    $createdAt = (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);
    $lead = [
        'created_at' => $createdAt,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'page_url' => $pageUrl,
        'utm' => $utm,
        'name' => $name,
        'phone' => $phone,
        'car' => $car,
        'service' => $service ?: 'Не выбрано',
        'message' => $message,
        'consent_personal_data' => true,
        'consent_policy' => true,
    ];

    $consentLog = [
        'created_at' => $createdAt,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'consent_text_version' => $config['lead']['consent_text_version'],
        'consent_personal_data' => true,
        'consent_policy' => true,
        'page_url' => $pageUrl,
    ];

    append_jsonl(storage_path($config, $config['storage']['leads_file']), $lead);
    append_jsonl(storage_path($config, $config['storage']['consent_file']), $consentLog);

    send_mail_notification($config, $lead);
    send_telegram_notification($config, $lead);
    send_amocrm_webhook($config, $lead);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    json_response([
        'success' => true,
        'message' => $config['lead']['success_message'],
        'csrf_token' => $_SESSION['csrf_token'],
    ]);
} catch (Throwable $exception) {
    error_log('[smart-submit] ' . $exception->getMessage());
    json_response([
        'success' => false,
        'message' => 'Не удалось отправить заявку. Позвоните нам или попробуйте позже.',
    ], 500);
}
