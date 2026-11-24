<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UssdSession extends Model
{
    use HasFactory;

    public const STEP_WELCOME = 'WELCOME';
    public const STEP_REASON = 'REASON';
    public const STEP_COMPLETED = 'COMPLETED';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ussd_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'msisdn',
        'network',
        'step',
        'selected_feeling',
        'selected_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->step === self::STEP_COMPLETED;
    }

    /**
     * Reset session to the initial welcome step.
     */
    public function reset(string $msisdn = null, string $network = null): void
    {
        $this->step = self::STEP_WELCOME;
        if ($msisdn !== null) {
            $this->msisdn = $msisdn;
        }
        if ($network !== null) {
            $this->network = $network;
        }
        $this->selected_feeling = null;
        $this->selected_reason = null;
        $this->save();
    }
}
