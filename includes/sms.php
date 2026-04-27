<?php

function getSmsConfig(): array
{
    return [
        'account_sid' => 'TWILIO_ACCOUNT_SID_REDACTED',
        'auth_token' => 'TWILIO_AUTH_TOKEN_REDACTED',
        'from_number' => '+19784738034',
        'enabled' => false,
    ];
}

function normaliseSmsPhone(string $phone): string
{
    $phone = preg_replace('/\s+/', '', trim($phone));

    if (preg_match('/^07\d{9}$/', $phone)) {
        return '+44' . substr($phone, 1);
    }

    return $phone;
}

function sendParcelSms($conn, int $orderId, string $phone, string $message): bool
{
    $phone = normaliseSmsPhone($phone);
    $message = trim($message);

    if ($orderId <= 0 || $phone === '' || $message === '') {
        return false;
    }

    $config = getSmsConfig();

    $provider = 'system_log';
    $providerSid = null;
    $status = 'sent';
    $errorMessage = null;

    if (
        !empty($config['enabled']) &&
        str_starts_with($config['account_sid'], 'AC') &&
        $config['auth_token'] !== 'PASTE_YOUR_AUTH_TOKEN_HERE' &&
        str_starts_with($config['from_number'], '+')
    ) {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' .
            rawurlencode($config['account_sid']) . '/Messages.json';

        $postFields = http_build_query([
            'From' => $config['from_number'],
            'To' => $phone,
            'Body' => $message,
        ]);

        $ch = curl_init($url);

        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_USERPWD => $config['account_sid'] . ':' . $config['auth_token'],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode((string)$response, true);

            $provider = 'twilio';

            if ($response !== false && $curlError === '' && $httpCode >= 200 && $httpCode < 300) {
                $providerSid = $data['sid'] ?? null;
                $status = $data['status'] ?? 'sent';
            } else {
                $status = 'failed';
                $errorMessage = $data['message'] ?? $curlError ?: 'Twilio request failed.';
            }
        } else {
            $provider = 'twilio';
            $status = 'failed';
            $errorMessage = 'Could not initialise SMS request.';
        }
    }

    $result = pg_query_params(
        $conn,
        "INSERT INTO public.sms_notifications
            (order_id, phone, message, provider, provider_sid, status, error_message)
         VALUES
            ($1, $2, $3, $4, $5, $6, $7)",
        [$orderId, $phone, $message, $provider, $providerSid, $status, $errorMessage]
    );

    return $result !== false && $status !== 'failed';
}
