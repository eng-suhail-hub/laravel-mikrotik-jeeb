<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('user_id')) {
            $user = User::find($request->user_id);
            if ($user && $user->is_banned) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك محظور بسبب نشاط غير نظامي. يرجى التواصل مع الدعم.',
                ], 403);
            }
        }

        return $next($request);
    }
}
