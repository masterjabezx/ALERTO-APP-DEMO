<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\HotlineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginGatewayController extends Controller
{
    public function index(HotlineService $hotlineService): View|RedirectResponse
    {
        if (Auth::check()) {
            return match (Auth::user()->account_type) {
                'citizen' => redirect()->route('citizen.dashboard'),
                'personnel' => redirect()->route('personnel.availability'),
                'office' => redirect()->route('office.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'super_admin' => redirect()->route('super-admin.dashboard'),
                default => redirect()->route('home'),
            };
        }

        $hotlines = $hotlineService->getDualHotlines();

        return view('welcome', compact('hotlines'));
    }
}
