#!/usr/bin/env php
<?php
/**
 * Standalone Interactive USSD Phone Simulator for Nalo USSD Integration.
 * Allows testing USSD flows locally without needing live mobile network infrastructure.
 *
 * Usage:
 *   php simulate_ussd.php [optional_http_url]
 * Example:
 *   php simulate_ussd.php http://127.0.0.1:8000/api/ussd
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$targetUrl = $argv[1] ?? null;
$phone = '233241234567';
$network = 'MTN';
$userId = config('ussd.user_id', 'NALOTest');
$sessionId = 'SIM-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 6);

echo str_repeat('=', 60) . PHP_EOL;
echo "📱 NALO USSD PHONE SIMULATOR (LARAVEL)" . PHP_EOL;
echo "Target: " . ($targetUrl ? $targetUrl : 'Internal Laravel Kernel') . PHP_EOL;
echo "User ID: {$userId}" . PHP_EOL;
echo "Phone (MSISDN): {$phone}" . PHP_EOL;
echo "Network: {$network}" . PHP_EOL;
echo "Session ID: {$sessionId}" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo "Dialing *920*29# ...\n\n";

$isFirst = true;
$inputData = '*920*29#';

while (true) {
    $payload = [
        'USERID' => $userId,
        'MSISDN' => $phone,
        'USERDATA' => $inputData,
        'MSGTYPE' => $isFirst,
        'NETWORK' => $network,
        'SESSIONID' => $sessionId,
    ];

    try {
        if ($targetUrl) {
            $data = json_encode($payload);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'content' => $data,
                    'timeout' => 10,
                ],
            ]);
            $res = file_get_contents($targetUrl, false, $context);
            $resp = json_decode($res, true);
        } else {
            $controller = app(\App\Http\Controllers\UssdController::class);
            $req = \Illuminate\Http\Request::create('/api/ussd', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($payload));
            $response = $controller->handle($req);
            $resp = json_decode($response->getContent(), true);
        }
    } catch (\Throwable $e) {
        echo "❌ Error during request: " . $e->getMessage() . PHP_EOL;
        break;
    }

    $msg = $resp['MSG'] ?? '';
    $continueSession = (bool) ($resp['MSGTYPE'] ?? false);

    $lines = explode("\n", $msg);
    $width = 46;
    echo '┌' . str_repeat('─', $width) . '┐' . PHP_EOL;
    foreach ($lines as $line) {
        $len = mb_strlen($line);
        $padding = max(0, $width - 2 - $len);
        echo '│ ' . $line . str_repeat(' ', $padding) . ' │' . PHP_EOL;
    }
    echo '└' . str_repeat('─', $width) . '┘' . PHP_EOL;

    if (!$continueSession) {
        echo PHP_EOL . "[Session Terminated (MSGTYPE=false)]" . PHP_EOL;
        break;
    }

    $isFirst = false;
    echo PHP_EOL . "Phone Input > ";
    $line = fgets(STDIN);
    if ($line === false || strtolower(trim($line)) === 'exit') {
        echo "\nExiting simulator.\n";
        break;
    }
    $inputData = trim($line);
}
