<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCode as CodeEmail;
use Illuminate\Auth\Events\Verified;
use Throwable;

/**
 * 6-digit email verification codes (replaces the "click the link" email).
 *
 * - Only an HMAC of the code is stored; a database leak doesn't reveal live codes.
 * - Codes expire after `email_code_minutes`, allow `email_code_attempts` wrong tries,
 *   and a new one can be requested every `email_code_resend_seconds`.
 * - A code is tied to the address it was sent to, so changing email needs a new code.
 */
class EmailCodeService
{
    public const SENT = 'sent';

    public const OK = 'ok';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const LOCKED = 'locked';

    public const MISSING = 'missing';

    public function minutes(): int
    {
        return (int) config('platform.email_code_minutes', 10);
    }

    public function maxAttempts(): int
    {
        return (int) config('platform.email_code_attempts', 5);
    }

    public function resendSeconds(): int
    {
        return (int) config('platform.email_code_resend_seconds', 60);
    }

    public function current(User $user): ?EmailVerificationCode
    {
        $row = EmailVerificationCode::query()->where('user_id', $user->id)->first();

        return $row && $row->email === $user->email ? $row : null;
    }

    /** Seconds the user must wait before another code can be sent (0 = can send now). */
    public function secondsUntilResend(User $user): int
    {
        $row = $this->current($user);
        if (! $row) {
            return 0;
        }

        return max(0, $this->resendSeconds() - (int) $row->sent_at->diffInSeconds(now()));
    }

    /**
     * Email a fresh code. Returns false (and sends nothing) during the resend
     * cool-down, or if the mail server fails; the user can simply try again.
     */
    public function send(User $user): bool
    {
        if ($user->hasVerifiedEmail() || $this->secondsUntilResend($user) > 0) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            // Sent straight away (not queued): people are waiting on the screen for it.
            $user->notifyNow(new CodeEmail($code, $this->minutes()));
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        EmailVerificationCode::query()->updateOrCreate(['user_id' => $user->id], [
            'email' => $user->email,
            'code_hash' => $this->hash($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->minutes()),
            'sent_at' => now(),
        ]);

        return true;
    }

    /** Check a code; on success the email is marked verified and the code is used up. */
    public function verify(User $user, string $code): string
    {
        $row = $this->current($user);
        if (! $row) {
            return self::MISSING;
        }
        if ($row->attempts >= $this->maxAttempts()) {
            return self::LOCKED;
        }
        if ($row->expires_at->isPast()) {
            return self::EXPIRED;
        }

        if (! hash_equals($row->code_hash, $this->hash(preg_replace('/\D/', '', $code)))) {
            $row->increment('attempts');

            return $row->attempts >= $this->maxAttempts() ? self::LOCKED : self::INVALID;
        }

        $row->delete();
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return self::OK;
    }

    public function attemptsLeft(User $user): int
    {
        $row = $this->current($user);

        return $row ? max(0, $this->maxAttempts() - $row->attempts) : 0;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
