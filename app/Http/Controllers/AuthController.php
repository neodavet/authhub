<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Application;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Crear token de autenticación usando Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = Auth::user();
        
        // Revocar tokens anteriores (opcional)
        // $user->tokens()->delete();
        
        // Crear nuevo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'applications_count' => $user->applications()->count(),
                'active_tokens_count' => $user->activeApiTokens()->count()
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Issue OAuth token for third-party applications
     */
    public function issueToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'scope' => 'sometimes|string',
            'user_id' => 'sometimes|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing required parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        // Buscar y validar la aplicación
        $application = Application::where('client_id', $request->client_id)
            ->where('is_active', true)
            ->first();

        if (!$application || !hash_equals($application->client_secret, $request->client_secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed'
            ], 401);
        }

        // Determinar el usuario (para demo, usamos el propietario de la app o el proporcionado)
        $userId = $request->user_id ?? $application->user_id;
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The user could not be found'
            ], 400);
        }

        // Procesar y validar scopes
        $requestedScopes = $request->scope ? explode(' ', $request->scope) : ['read'];
        $allowedScopes = $application->allowed_scopes ?? ['read'];
        $grantedScopes = array_intersect($requestedScopes, $allowedScopes);

        if (empty($grantedScopes)) {
            return response()->json([
                'error' => 'invalid_scope',
                'error_description' => 'The requested scope is invalid'
            ], 400);
        }

        // Generar token único
        $plainTextToken = Str::random(64);
        $hashedToken = hash('sha256', $plainTextToken);

        // Crear API token en la base de datos
        $apiToken = ApiToken::create([
            'name' => 'OAuth Token for ' . $application->name,
            'token' => $hashedToken,
            'abilities' => $grantedScopes,
            'application_id' => $application->id,
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30), // 30 días de expiración
            'is_active' => true,
            'created_from_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'access_token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 2592000, // 30 días en segundos
            'scope' => implode(' ', $grantedScopes),
            'created_at' => $apiToken->created_at->timestamp
        ]);
    }

    /**
     * Verify OAuth token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No token provided'
            ], 400);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = ApiToken::where('token', $hashedToken)
            ->where('is_active', true)
            ->with(['user', 'application'])
            ->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token not found or inactive'
            ], 401);
        }

        if ($apiToken->isExpired()) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token has expired'
            ], 401);
        }

        // Actualizar último uso
        $apiToken->markAsUsed();

        return response()->json([
            'valid' => true,
            'user_id' => $apiToken->user_id,
            'client_id' => $apiToken->application->client_id,
            'scope' => implode(' ', $apiToken->abilities),
            'expires_at' => $apiToken->expires_at?->timestamp,
            'user' => [
                'id' => $apiToken->user->id,
                'name' => $apiToken->user->name,
                'email' => $apiToken->user->email,
            ]
        ]);
    }

    /**
     * Revoke OAuth token
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $token = $request->bearerToken() ?? $request->input('token');

        if (!$token) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No token provided'
            ], 400);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = ApiToken::where('token', $hashedToken)->first();

        if ($apiToken) {
            $apiToken->revoke();
        }

        return response()->json([
            'message' => 'Token revoked successfully'
        ]);
    }

    /**
     * Validate Sanctum token
     */
    public function validateToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'applications_count' => $user->applications()->count(),
                'active_tokens_count' => $user->activeApiTokens()->count()
            ]
        ]);
    }

    /**
     * Refresh Sanctum token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Revocar token actual
        $request->user()->currentAccessToken()->delete();

        // Crear nuevo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Get authenticated user for third-party apps
     */
    public function getAuthenticatedUser(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email
            ]
        ]);
    }

    /**
     * Get user profile for third-party apps
     */
    public function getUserProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'applications_count' => $user->applications()->count(),
                'active_tokens_count' => $user->activeApiTokens()->count(),
            ]
        ]);
    }

    /**
     * Update authenticated user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'updated_at' => $user->updated_at,
                'applications_count' => $user->applications()->count(),
                'active_tokens_count' => $user->activeApiTokens()->count()
            ]
        ]);
    }

    /**
     * Delete authenticated user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        // Store user info before deletion
        $userName = $user->name;
        $userId = $user->id;

        // Revoke all tokens
        $user->tokens()->delete();
        
        // Revoke all API tokens
        $user->apiTokens()->update(['is_active' => false]);

        // Delete user account
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
            'deleted_user' => [
                'id' => $userId,
                'name' => $userName,
                'deleted_at' => now()
            ]
        ]);
    }
}
 