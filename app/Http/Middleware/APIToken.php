<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class APIToken
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
        if ($request->header('Authorization')) {
            return $next($request);
        }
        return response()->json([
            'success' => false,
            'status' => 401,
            'message' => 'Your Token is expired or not a valid',
        ]);
    }
}
