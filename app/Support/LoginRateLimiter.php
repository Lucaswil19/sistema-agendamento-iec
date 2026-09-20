<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginRateLimiter
{
    public function tooManyAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->lockoutKey($request),
            1
        );
    }

    public function recordFailure(Request $request): void
    {
        $attempts = RateLimiter::hit(
            $this->attemptKey($request),
            $this->attemptWindowSeconds()
        );

        if ($attempts === $this->maxAttempts()) {
            RateLimiter::hit(
                $this->lockoutKey($request),
                $this->lockoutSeconds($request)
            );
            RateLimiter::clear($this->attemptKey($request));
            RateLimiter::hit(
                $this->progressionKey($request),
                $this->progressionWindowSeconds()
            );
        }
    }

    public function clear(Request $request): void
    {
        RateLimiter::clear($this->attemptKey($request));
        RateLimiter::clear($this->lockoutKey($request));
        RateLimiter::clear($this->progressionKey($request));
    }

    public function availableIn(Request $request): int
    {
        return RateLimiter::availableIn($this->lockoutKey($request));
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('auth.login_throttle.max_attempts', 5));
    }

    public function attemptKey(Request $request): string
    {
        return 'login-attempts:'.$this->requestFingerprint($request);
    }

    private function progressionKey(Request $request): string
    {
        return 'login-progression:'.$this->requestFingerprint($request);
    }

    private function lockoutKey(Request $request): string
    {
        return 'login-lockout:'.$this->requestFingerprint($request);
    }

    private function requestFingerprint(Request $request): string
    {
        $email = $request->input('email');
        $normalizedEmail = is_string($email) ? mb_strtolower(trim($email)) : '';

        return hash('sha256', $normalizedEmail.'|'.$request->ip());
    }

    private function lockoutSeconds(Request $request): int
    {
        $durations = array_values(array_filter(
            array_map(
                static fn (mixed $duration): int => (int) $duration,
                (array) config('auth.login_throttle.lockout_seconds', [60, 300, 900, 3600])
            ),
            static fn (int $duration): bool => $duration > 0
        ));

        if ($durations === []) {
            return 60;
        }

        $progression = (int) RateLimiter::attempts($this->progressionKey($request));

        return $durations[min($progression, count($durations) - 1)];
    }

    private function attemptWindowSeconds(): int
    {
        return max(
            1,
            (int) config('auth.login_throttle.attempt_window_seconds', 60)
        );
    }

    private function progressionWindowSeconds(): int
    {
        return max(
            1,
            (int) config('auth.login_throttle.progression_window_seconds', 86400)
        );
    }
}
