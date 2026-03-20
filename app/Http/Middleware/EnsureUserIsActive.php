<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_active) {
            return $next($request);
        }

        Auth::logout();

        $session = $request->session();

        $session->invalidate();
        $session->regenerateToken();

        return redirect()->guest($this->loginRoute())
            ->withInput([
                'email' => '',
            ])
            ->withErrors([
                'login' => __('auth.inactive_session'),
            ]);
    }

    private function loginRoute(): string
    {
        return Route::has('login') ? route('login') : '/login';
    }
}
