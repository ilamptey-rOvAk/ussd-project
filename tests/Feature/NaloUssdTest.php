<?php

namespace Tests\Feature;

use App\Models\UssdSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NaloUssdTest extends TestCase
{
    use RefreshDatabase;

    protected string $userId = 'AIK12345';
    protected string $msisdn = '233541234567';
    protected string $sessionId = 'SESSION-TEST-LARAVEL-1001';

    protected function postUssd(string $userdata, bool|int|string $msgtype = false, ?string $sessionId = null)
    {
        $payload = [
            'USERID' => $this->userId,
            'MSISDN' => $this->msisdn,
            'USERDATA' => $userdata,
            'MSGTYPE' => $msgtype,
            'NETWORK' => 'MTN',
            'SESSIONID' => $sessionId ?? $this->sessionId,
        ];

        return $this->postJson('/api/ussd', $payload);
    }

    public function test_health_check_get_returns_healthy(): void
    {
        $response = $this->getJson('/api/ussd');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'Nalo USSD Menu Service (Laravel)',
            ]);
    }

    public function test_web_route_health_check_get(): void
    {
        $response = $this->getJson('/ussd');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    public function test_complete_flow_feeling_fine_family_issues(): void
    {
        // Step 1: Initial Dial (MSGTYPE = true)
        $response1 = $this->postUssd(userdata: '*920#', msgtype: true);

        $response1->assertStatus(200)
            ->assertJson([
                'USERID' => $this->userId,
                'MSISDN' => $this->msisdn,
                'MSGTYPE' => true,
            ]);
        $this->assertStringContainsString('Welcome! How are you feeling today?', $response1->json('MSG'));
        $this->assertStringContainsString('1. Feeling fine', $response1->json('MSG'));
        $this->assertStringContainsString('2. Feeling sad', $response1->json('MSG'));
        $this->assertStringContainsString('3. Feeling frisky', $response1->json('MSG'));

        // Step 2: Select Feeling '1' (fine)
        $response2 = $this->postUssd(userdata: '1', msgtype: false);

        $response2->assertStatus(200)
            ->assertJson([
                'MSGTYPE' => true,
            ]);
        $this->assertStringContainsString('You are feeling fine because...', $response2->json('MSG'));
        $this->assertStringContainsString('1. Relationship issues', $response2->json('MSG'));
        $this->assertStringContainsString('2. Money problems', $response2->json('MSG'));
        $this->assertStringContainsString('3. Family issues', $response2->json('MSG'));

        // Step 3: Select Reason '3' (family issues)
        $response3 = $this->postUssd(userdata: '3', msgtype: false);

        $response3->assertStatus(200)
            ->assertJson([
                'MSGTYPE' => false, // Session terminates
            ]);
        $this->assertStringContainsString('You are feeling fine because of family issues.', $response3->json('MSG'));
        $this->assertStringContainsString('Feel well soon!', $response3->json('MSG'));

        // Verify DB State
        $session = UssdSession::where('session_id', $this->sessionId)->first();
        $this->assertNotNull($session);
        $this->assertEquals(UssdSession::STEP_COMPLETED, $session->step);
        $this->assertEquals('fine', $session->selected_feeling);
        $this->assertEquals('family issues', $session->selected_reason);
    }

    public function test_complete_flow_feeling_sad_relationship_issues(): void
    {
        $this->postUssd(userdata: '', msgtype: true);

        $resp2 = $this->postUssd(userdata: '2', msgtype: false);
        $resp2->assertJson(['MSGTYPE' => true]);
        $this->assertStringContainsString('You are feeling sad because...', $resp2->json('MSG'));

        $resp3 = $this->postUssd(userdata: '1', msgtype: false);
        $resp3->assertJson(['MSGTYPE' => false]);
        $this->assertStringContainsString('You are feeling sad because of relationship issues.', $resp3->json('MSG'));

        $session = UssdSession::where('session_id', $this->sessionId)->first();
        $this->assertEquals('sad', $session->selected_feeling);
        $this->assertEquals('relationship issues', $session->selected_reason);
    }

    public function test_complete_flow_feeling_frisky_money_problems(): void
    {
        $this->postUssd(userdata: '', msgtype: true);

        $resp2 = $this->postUssd(userdata: '3', msgtype: false);
        $resp2->assertJson(['MSGTYPE' => true]);
        $this->assertStringContainsString('You are feeling frisky because...', $resp2->json('MSG'));

        $resp3 = $this->postUssd(userdata: '2', msgtype: false);
        $resp3->assertJson(['MSGTYPE' => false]);
        $this->assertStringContainsString('You are feeling frisky because of money problems.', $resp3->json('MSG'));

        $session = UssdSession::where('session_id', $this->sessionId)->first();
        $this->assertEquals('frisky', $session->selected_feeling);
        $this->assertEquals('money problems', $session->selected_reason);
    }

    public function test_invalid_feeling_choice_prompts_retry(): void
    {
        $this->postUssd(userdata: '', msgtype: true);

        // Invalid input
        $respInvalid = $this->postUssd(userdata: '99', msgtype: false);
        $respInvalid->assertJson(['MSGTYPE' => true]);
        $this->assertStringContainsString('Invalid option', $respInvalid->json('MSG'));
        $this->assertStringContainsString('1. Feeling fine', $respInvalid->json('MSG'));

        // Subsequent valid input proceeds
        $respValid = $this->postUssd(userdata: '1', msgtype: false);
        $respValid->assertJson(['MSGTYPE' => true]);
        $this->assertStringContainsString('You are feeling fine because...', $respValid->json('MSG'));
    }

    public function test_invalid_reason_choice_prompts_retry(): void
    {
        $this->postUssd(userdata: '', msgtype: true);
        $this->postUssd(userdata: '1', msgtype: false);

        // Invalid reason
        $respInvalid = $this->postUssd(userdata: 'invalid_text', msgtype: false);
        $respInvalid->assertJson(['MSGTYPE' => true]);
        $this->assertStringContainsString('Invalid option', $respInvalid->json('MSG'));
        $this->assertStringContainsString('You are feeling fine because...', $respInvalid->json('MSG'));

        // Subsequent valid reason completes
        $respValid = $this->postUssd(userdata: '2', msgtype: false);
        $respValid->assertJson(['MSGTYPE' => false]);
        $this->assertStringContainsString('You are feeling fine because of money problems.', $respValid->json('MSG'));
    }

    public function test_session_isolation_between_different_users(): void
    {
        $sessionA = 'SESSION-A-100';
        $sessionB = 'SESSION-B-200';

        $this->postUssd(userdata: '', msgtype: true, sessionId: $sessionA);
        $this->postUssd(userdata: '', msgtype: true, sessionId: $sessionB);

        // User A chooses 1 (fine)
        $this->postUssd(userdata: '1', msgtype: false, sessionId: $sessionA);
        // User B chooses 2 (sad)
        $this->postUssd(userdata: '2', msgtype: false, sessionId: $sessionB);

        $stateA = UssdSession::where('session_id', $sessionA)->first();
        $stateB = UssdSession::where('session_id', $sessionB)->first();

        $this->assertEquals('fine', $stateA->selected_feeling);
        $this->assertEquals('sad', $stateB->selected_feeling);
    }
}
