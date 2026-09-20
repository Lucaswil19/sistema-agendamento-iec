<?php

namespace App\Providers;

use App\Mail\ResendTransportFactory;
use App\Models\Beneficiario;
use App\Policies\BeneficiarioPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('resend', fn (array $config) => (new ResendTransportFactory)($config));

        Gate::policy(Beneficiario::class, BeneficiarioPolicy::class);

        Password::defaults(
            fn () => Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
        );
    }
}
