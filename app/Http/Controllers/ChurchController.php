<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\User;
use App\Services\ChurchService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChurchController extends Controller
{
    protected ChurchService $churchService;

    public function __construct(ChurchService $churchService)
    {
        $this->middleware('auth:sanctum');
        $this->churchService = $churchService;
    }

    /**
     * GET /churches/{church} - getChurch
     */
    public function show(Church $church, Request $request)
    {
        try {
            $churchData = $this->churchService->getChurch($church->id, $request->user());
            return response()->json($churchData);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * GET /churches/{church}/users - getAllUsers
     */
    public function getUsers(Church $church, Request $request)
    {
        if ($request->user()->church_id !== $church->id) {
            return response()->json(['message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }
        return response()->json($church->users()->get());
    }

    /**
     * POST /churches/{church}/sections - createSection
     */
    public function createSection(Church $church, Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        try {
            $section = $this->churchService->createSection($church->id, $request->input('name'), $request->user());
            return response()->json($section, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * POST /users - createUser
     */
    public function createUser(Request $request)
    {
        // NOTE: A dedicated Request class should be used here for proper role/ID validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:Member,Department Head,Section President,Finance,Auditor',
            'section_id' => 'nullable|exists:sections,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $user = $this->churchService->createUser($request->only([
                'name',
                'email',
                'password',
                'role',
                'section_id',
                'department_id'
            ]), $request->user());

            return response()->json($user, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * POST /churches/{church}/extend-subscription - extendSubscription
     */
    public function extendSubscription(Church $church, Request $request)
    {
        $request->validate(['months' => ['required', 'integer', 'min:1']]);

        try {
            $updatedChurch = $this->churchService->extendSubscription($church, $request->input('months'), $request->user());
            return response()->json($updatedChurch);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * GET /churches/{church}/audit-logs - getAuditLogs
     */
    public function getAuditLogs(Church $church, Request $request)
    {
        try {
            $logs = $this->churchService->getAuditLogs($church, $request->user());
            return response()->json($logs);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}
