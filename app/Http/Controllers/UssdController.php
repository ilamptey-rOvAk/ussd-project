<?php

namespace App\Http\Controllers;

use App\Models\UssdSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UssdController extends Controller
{
    /**
     * Primary handler for Nalo USSD requests (GET for health check, POST for USSD callbacks).
     */
    public function handle(Request $request): JsonResponse
    {
        // Handle GET health check
        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'healthy',
                'service' => 'Nalo USSD Menu Service (Laravel)',
                'menu' => 'Mood & Reasons Flow',
                'endpoint' => $request->path(),
            ]);
        }

        // Handle POST USSD callback
        $userId = $request->input('USERID') ?? config('ussd.user_id', 'NALOTest');
        $msisdn = (string) ($request->input('MSISDN') ?? '');
        $userdata = trim((string) ($request->input('USERDATA') ?? ''));
        $network = (string) ($request->input('NETWORK') ?? '');
        $sessionId = trim((string) ($request->input('SESSIONID') ?? $msisdn ?: 'default_session'));

        $msgTypeRaw = $request->input('MSGTYPE');
        $isFirstDial = in_array($msgTypeRaw, [true, 1, '1', 'true', 'True'], true);

        // Find or create session
        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'msisdn' => $msisdn,
                'network' => $network,
                'step' => UssdSession::STEP_WELCOME,
            ]
        );

        // Reset to initial screen if it's the first dial, newly created session, or a shortcode dial string
        if ($isFirstDial || $session->wasRecentlyCreated || str_starts_with($userdata, '*')) {
            $session->reset($msisdn, $network);

            $welcomeMsg = "Welcome! How are you feeling today?\n"
                . "1. Feeling fine\n"
                . "2. Feeling sad\n"
                . "3. Feeling frisky";

            return $this->buildResponse($userId, $msisdn, $welcomeMsg, true);
        }

        $feelings = config('ussd.feelings', []);
        $reasons = config('ussd.reasons', []);

        // Step 1: User selecting a feeling
        if ($session->step === UssdSession::STEP_WELCOME) {
            if (array_key_exists($userdata, $feelings)) {
                $feeling = $feelings[$userdata];
                $session->selected_feeling = $feeling;
                $session->step = UssdSession::STEP_REASON;
                $session->save();

                $reasonMsg = "You are feeling {$feeling} because...\n"
                    . "1. Relationship issues\n"
                    . "2. Money problems\n"
                    . "3. Family issues";

                return $this->buildResponse($userId, $msisdn, $reasonMsg, true);
            }

            // Invalid input on welcome screen
            $retryMsg = "Invalid option. How are you feeling today?\n"
                . "1. Feeling fine\n"
                . "2. Feeling sad\n"
                . "3. Feeling frisky";

            return $this->buildResponse($userId, $msisdn, $retryMsg, true);
        }

        // Step 2: User selecting a reason
        if ($session->step === UssdSession::STEP_REASON) {
            $feeling = $session->selected_feeling ?? 'fine';

            if (array_key_exists($userdata, $reasons)) {
                $reason = $reasons[$userdata];
                $session->selected_reason = $reason;
                $session->step = UssdSession::STEP_COMPLETED;
                $session->save();

                // Final confirmation screen - session ends (MSGTYPE: false)
                $finalMsg = "You are feeling {$feeling} because of {$reason}.\n"
                    . "Feel well soon!";

                return $this->buildResponse($userId, $msisdn, $finalMsg, false);
            }

            // Invalid input on reason screen
            $retryMsg = "Invalid option. You are feeling {$feeling} because...\n"
                . "1. Relationship issues\n"
                . "2. Money problems\n"
                . "3. Family issues";

            return $this->buildResponse($userId, $msisdn, $retryMsg, true);
        }

        // Session completed or unknown state
        $closingMsg = "Your session has ended. Thank you!";
        return $this->buildResponse($userId, $msisdn, $closingMsg, false);
    }

    /**
     * Build standardized Nalo Solutions USSD JSON response.
     */
    protected function buildResponse(string $userId, string $msisdn, string $message, bool $continueSession): JsonResponse
    {
        return response()->json([
            'USERID' => $userId,
            'MSISDN' => $msisdn,
            'MSG' => $message,
            'MSGTYPE' => (bool) $continueSession,
        ]);
    }
}
