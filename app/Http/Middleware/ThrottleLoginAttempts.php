<?php

namespace App\Http\Middleware;

use App\Support\LoginRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThrottleLoginAttempts
{
    public function __construct(private readonly LoginRateLimiter $rateLimiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->rateLimiter->tooManyAttempts($request)) {
            $seconds = $this->rateLimiter->availableIn($request);

            throw new HttpException(
                429,
                "Muitas tentativas de acesso. Tente novamente em {$seconds} segundos.",
                null,
                [
                    'Retry-After' => (string) $seconds,
                    'X-RateLimit-Limit' => (string) $this->rateLimiter->maxAttempts(),
                ]
            );
        }

        return $next($request);
    }
}
