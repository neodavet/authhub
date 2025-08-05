<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\Application;
use App\Http\Requests\StoreApiTokenRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    /**
     * Display a listing of tokens for a specific application.
     */
    public function index(Request $request, Application $application = null): View|JsonResponse
    {
        // If application is provided, show tokens for that application
        if ($application) {
            $this->authorize('view', $application);
            
            $tokens = $application->apiTokens()
                ->with(['user'])
                ->latest()
                ->paginate(10);
        } else {
            // Show all user's tokens across all applications
            $tokens = Auth::user()->apiTokens()
                ->with(['application', 'user'])
                ->latest()
                ->paginate(10);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $tokens->items(),
                'current_page' => $tokens->currentPage(),
                'last_page' => $tokens->lastPage(),
                'per_page' => $tokens->perPage(),
                'total' => $tokens->total(),
            ]);
        }

        return view('api-tokens.index', compact('tokens', 'application'));
    }

    /**
     * Store a newly created API token for an application.
     */
    public function store(StoreApiTokenRequest $request, Application $application = null): RedirectResponse|JsonResponse
    {
        // If application is provided via route parameter
        if ($application) {
            $this->authorize('update', $application);
            $targetApplication = $application;
        } else {
            // Application might be provided in request data
            $targetApplication = Application::findOrFail($request->input('application_id'));
            $this->authorize('update', $targetApplication);
        }

        // Generate plain text token for one-time display
        $plaintextToken = Str::random(64);
        $hashedToken = hash('sha256', $plaintextToken);

        // Create the API token
        $apiToken = $targetApplication->apiTokens()->create([
            'name' => $request->input('name'),
            'token' => $hashedToken,
            'abilities' => $request->input('abilities'),
            'user_id' => Auth::id(),
            'expires_at' => $request->input('expires_at'),
            'is_active' => true,
            'created_from_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'API token created successfully.',
                'token' => $apiToken->load(['application', 'user']),
                'plaintext_token' => $plaintextToken, // Only shown once
                'expires_at' => $apiToken->expires_at,
            ], 201);
        }

        return redirect()->route('applications.show', $targetApplication)
            ->with('success', 'API token created successfully.')
            ->with('plaintext_token', $plaintextToken);
    }

    /**
     * Display the specified API token.
     */
    public function show(ApiToken $apiToken): View|JsonResponse
    {
        $this->authorize('view', $apiToken->application);

        $apiToken->load(['application', 'user']);

        if (request()->expectsJson()) {
            return response()->json(['token' => $apiToken]);
        }

        return view('api-tokens.show', compact('apiToken'));
    }

    /**
     * Remove (revoke) the specified API token.
     */
    public function destroy(ApiToken $apiToken): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $apiToken->application);

        // Revoke the token by setting it as inactive
        $apiToken->revoke();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'API token revoked successfully.'
            ]);
        }

        return redirect()->back()
            ->with('success', 'API token revoked successfully.');
    }

    /**
     * Get tokens for all user's applications (API endpoint).
     */
    public function userTokens(Request $request): JsonResponse
    {
        $tokens = Auth::user()->apiTokens()
            ->with(['application'])
            ->active()
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $tokens->items(),
            'current_page' => $tokens->currentPage(),
            'last_page' => $tokens->lastPage(),
            'per_page' => $tokens->perPage(),
            'total' => $tokens->total(),
        ]);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $count = Auth::user()->apiTokens()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'message' => "Successfully revoked {$count} API tokens.",
            'revoked_count' => $count,
        ]);
    }

    /**
     * Get token statistics for user.
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'total_tokens' => $user->apiTokens()->count(),
            'active_tokens' => $user->apiTokens()->where('is_active', true)->count(),
            'expired_tokens' => $user->apiTokens()->where('expires_at', '<', now())->count(),
            'never_used_tokens' => $user->apiTokens()->whereNull('last_used_at')->count(),
            'recently_used_tokens' => $user->apiTokens()->where('last_used_at', '>=', now()->subWeek())->count(),
        ];

        return response()->json($stats);
    }
}
