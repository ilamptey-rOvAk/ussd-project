<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\UssdController;

class SimulateUssdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ussd:simulate
                            {--url= : Optional target HTTP URL (e.g. http://localhost:8000/api/ussd)}
                            {--phone=233241234567 : Simulated MSISDN phone number}
                            {--network=MTN : Simulated mobile network operator}
                            {--userid= : Nalo User ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive CLI USSD phone simulator for testing Nalo USSD flows locally';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetUrl = $this->option('url');
        $phone = $this->option('phone');
        $network = $this->option('network');
        $userId = $this->option('userid') ?: config('ussd.user_id', 'NALOTest');
        $sessionId = 'SIM-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 6);

        $this->line(str_repeat('=', 60));
        $this->info('📱 NALO USSD PHONE SIMULATOR (LARAVEL)');
        $this->line('Mode: ' . ($targetUrl ? "HTTP to {$targetUrl}" : 'Internal Laravel Kernel'));
        $this->line("User ID: {$userId}");
        $this->line("Phone (MSISDN): {$phone}");
        $this->line("Network: {$network}");
        $this->line("Session ID: {$sessionId}");
        $this->line(str_repeat('=', 60));
        $this->comment("Dialing *920# ...\n");

        $isFirst = true;
        $inputData = '*920#';

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
                    $response = $this->sendHttpRequest($targetUrl, $payload);
                } else {
                    $response = $this->sendInternalRequest($payload);
                }
            } catch (\Throwable $e) {
                $this->error("❌ Error during request: {$e->getMessage()}");
                return Command::FAILURE;
            }

            $msg = $response['MSG'] ?? '';
            $continueSession = (bool) ($response['MSGTYPE'] ?? false);

            $this->renderScreen($msg);

            if (!$continueSession) {
                $this->warn("\n[Session Terminated (MSGTYPE=false)]\n");
                break;
            }

            $isFirst = false;
            $userInput = $this->ask('Phone Input');

            if ($userInput === null || strtolower(trim($userInput)) === 'exit') {
                $this->info('Exiting simulator.');
                break;
            }

            $inputData = trim($userInput);
        }

        return Command::SUCCESS;
    }

    protected function renderScreen(string $msg): void
    {
        $lines = explode("\n", $msg);
        $width = 46;

        $this->line('┌' . str_repeat('─', $width) . '┐');
        foreach ($lines as $line) {
            $len = mb_strlen($line);
            $padding = max(0, $width - 2 - $len);
            $this->line('│ ' . $line . str_repeat(' ', $padding) . ' │');
        }
        $this->line('└' . str_repeat('─', $width) . '┘');
    }

    protected function sendInternalRequest(array $payload): array
    {
        $controller = app(UssdController::class);
        $request = Request::create('/api/ussd', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $controller->handle($request);
        return json_decode($response->getContent(), true) ?? [];
    }

    protected function sendHttpRequest(string $url, array $payload): array
    {
        $data = json_encode($payload);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                          . "Accept: application/json\r\n"
                          . 'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
                'timeout' => 10,
            ],
        ]);

        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new \RuntimeException('Failed to connect to target URL: ' . $url);
        }

        return json_decode($result, true) ?? [];
    }
}
