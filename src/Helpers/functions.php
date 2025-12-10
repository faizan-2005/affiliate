<?php

function log_error($message, $context = [])
{
    $logFile = storage_path('logs/app.log');
    @mkdir(dirname($logFile), 0755, true);

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ERROR: {$message}";

    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function log_info($message, $context = [])
{
    $logFile = storage_path('logs/app.log');
    @mkdir(dirname($logFile), 0755, true);

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] INFO: {$message}";

    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function log_fraud($message, $context = [])
{
    $logFile = storage_path('logs/fraud.log');
    @mkdir(dirname($logFile), 0755, true);

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] FRAUD: {$message}";

    if (!empty($context)) {
        $logMessage .= ' | ' . json_encode($context);
    }

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function generate_click_id()
{
    return bin2hex(random_bytes(32));
}

function generate_session_id()
{
    return bin2hex(random_bytes(32));
}

function hash_user_agent($userAgent)
{
    return hash('sha256', $userAgent);
}

function generate_signature($data, $secret)
{
    ksort($data);
    $message = implode('|', array_values($data));
    return hash_hmac('sha256', $message, $secret);
}

function verify_signature($data, $signature, $secret)
{
    $expected = generate_signature($data, $secret);
    return hash_equals($expected, $signature);
}

function is_valid_ipv4($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

function is_valid_ipv6($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
}

function is_valid_ip($ip)
{
    return is_valid_ipv4($ip) || is_valid_ipv6($ip);
}

function is_bot_user_agent($userAgent)
{
    $botPatterns = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
        'java(?!script)', 'ruby', 'php', 'go-http-client', 'httpunit',
        'nutch', 'jyxobot', 'libwww', 'ezooms', 'googlebot', 'bingbot',
        'yandexbot', 'baiduspider', 'facebookexternalhit', 'twitterbot'
    ];

    $userAgentLower = strtolower($userAgent);

    foreach ($botPatterns as $pattern) {
        if (preg_match('/' . $pattern . '/i', $userAgentLower)) {
            return true;
        }
    }

    return false;
}

function sanitize_input($input)
{
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }

    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function get_country_from_ip($ip)
{
    // This would typically use a GeoIP library like MaxMind
    // For now, return a placeholder
    return 'US';
}

function format_currency($amount, $currency = 'USD')
{
    return number_format($amount, 2) . ' ' . $currency;
}

function percentage_change($old, $new)
{
    if ($old == 0) {
        return 0;
    }
    return round((($new - $old) / $old) * 100, 2);
}

function is_valid_url($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function truncate($string, $length = 100, $suffix = '...')
{
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - strlen($suffix)) . $suffix;
}

function get_client_info()
{
    $request = request();
    return [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'ua_hash' => hash_user_agent($request->userAgent()),
        'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}

function uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
