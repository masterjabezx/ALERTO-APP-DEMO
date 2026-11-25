<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MediaAttachment;
use App\Policies\MediaPolicy;
use App\Services\AdminCommandCenterService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(MediaAttachment::class, MediaPolicy::class);

        RateLimiter::for('media-upload', function (Request $request): Limit {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : $request->ip();

            return Limit::perMinute(30)->by((string) $key);
        });

        View::composer(['components.layouts.admin', 'layouts.admin', 'components.layouts.super-admin'], function ($view): void {
            $user = Auth::user();
            if ($user && $user->isAdmin()) {
                $telemetry = app(AdminCommandCenterService::class)->getShellTelemetry($user);
                $view->with('shellTelemetry', $telemetry);
            } else {
                $view->with('shellTelemetry', [
                    'pending_incidents' => 0,
                    'critical_active' => 0,
                    'fb_unread_total' => 0,
                    'internal_unread' => 0,
                    'unread_notifications' => 0,
                    'notifications' => collect(),
                ]);
            }
        });
    }
}
