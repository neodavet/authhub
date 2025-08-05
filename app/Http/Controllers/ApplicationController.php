<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        $applications = Auth::user()->applications()
            ->with(['apiTokens' => function($query) {
                $query->where('is_active', true);
            }])
            ->paginate(10);

        if ($request->expectsJson()) {
            return response()->json([
                'applications' => [
                    'data' => $applications->items(),
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ]
            ]);
        }

        return view('applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('applications.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreApplicationRequest $request): RedirectResponse|JsonResponse
    {
        $application = Auth::user()->applications()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Application created successfully.',
                'application' => $application->load('user'),
            ], 201);
        }

        return redirect()->route('applications.show', $application)
            ->with('success', 'Application created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application): View|JsonResponse
    {
        $this->authorize('view', $application);
        
        $application->load(['apiTokens' => function($query) {
            $query->where('is_active', true)->latest();
        }]);

        if (request()->expectsJson()) {
            return response()->json(['application' => $application]);
        }

        return view('applications.show', compact('application'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Application $application): View
    {
        $this->authorize('update', $application);

        return view('applications.edit', compact('application'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $application);
        
        $application->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Application updated successfully.',
                'application' => $application->fresh()->load('user'),
            ]);
        }

        return redirect()->route('applications.show', $application)
            ->with('success', 'Application updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $application);
        
        $application->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Application deleted successfully.'
            ]);
        }

        return redirect()->route('applications.index')
            ->with('success', 'Application deleted successfully.');
    }

    /**
     * Regenerate the client secret for the application.
     */
    public function regenerateSecret(Application $application): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $application);
        
        $newSecret = $application->regenerateClientSecret();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Client secret regenerated successfully.',
                'client_secret' => $newSecret,
            ]);
        }

        return redirect()->route('applications.show', $application)
            ->with('success', 'Client secret regenerated successfully.')
            ->with('new_secret', $newSecret);
    }

    /**
     * Toggle the active status of the application.
     */
    public function toggleStatus(Application $application): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $application);
        
        $application->update(['is_active' => !$application->is_active]);
        $status = $application->is_active ? 'activated' : 'deactivated';

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Application {$status} successfully.",
                'is_active' => $application->is_active,
            ]);
        }

        return redirect()->route('applications.show', $application)
            ->with('success', "Application {$status} successfully.");
    }
}
