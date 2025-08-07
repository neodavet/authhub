<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /**
     * Handle an incoming request for third-party applications using OAuth tokens
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'unauthorized',
                'error_description' => 'Access token required'
            ], 401);
        }

        // Hash del token para buscar en la base de datos
        $hashedToken = hash('sha256', $token);
        
        $apiToken = ApiToken::where('token', $hashedToken)
            ->where('is_active', true)
            ->with(['user', 'application'])
            ->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token provided is invalid'
            ], 401);
        }

        // Verificar si el token ha expirado
        if ($apiToken->isExpired()) {
            return response()->json([
                'error' => 'token_expired',
                'error_description' => 'The access token has expired'
            ], 401);
        }

        // Verificar si la aplicación está activa
        if (!$apiToken->application->is_active) {
            return response()->json([
                'error' => 'application_disabled',
                'error_description' => 'The application is currently disabled'
            ], 403);
        }

        // Actualizar el timestamp de último uso
        $apiToken->markAsUsed();

        // Añadir información del usuario y token al request
        $request->merge([
            'auth_user' => $apiToken->user,
            'auth_token' => $apiToken,
            'auth_application' => $apiToken->application
        ]);

        // Establecer el usuario autenticado para Laravel
        auth()->setUser($apiToken->user);

        return $next($request);
    }
}