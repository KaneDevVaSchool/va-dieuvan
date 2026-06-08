<?php

namespace App\Providers;

use App\Models\DispatchRequest;
use App\Policies\DispatchRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        DispatchRequest::class => DispatchRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (! $user instanceof \App\Models\User) {
                return null;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }
            if (is_string($ability) && str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }

            return null;
        });
    }
}
