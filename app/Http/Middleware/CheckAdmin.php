<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $admins = explode(env('ADMIN_USER'), ",");
        $currentUser = Auth::user()->username;
        if(array_has($admins, $currentUser)) {
        // if (Auth::user()->username != env('ADMIN_USER')) {
            return redirect('/');
        }

        return $next($request);
    }
}
