<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountType
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        if (! Auth::check()) {
            return redirect()->guest(route('home'))->with('error', 'Authentication required.');
        }

        $user = Auth::user();

        if (! in_array($user->account_type, $types, true)) {
            if ($request->expectsJson() || $request->is('*/data')) {
                abort(403, 'Unauthorized portal access.');
            }

            // Role escalation / cross-portal attempt: Redirect to their proper authorized portal
            $redirectRoute = match ($user->account_type) {
                'citizen' => route('citizen.dashboard'),
                'personnel' => route('personnel.availability'),
                'office' => route('office.dashboard'),
                'admin' => route('admin.dashboard'),
                'super_admin' => route('super-admin.dashboard'),
                default => route('home'),
            };

            return redirect($redirectRoute)->with('error', 'Unauthorized access attempt. Redirected to your designated portal.');
        }

        return $next($request);
    }
}
