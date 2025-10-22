<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterChurchRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /login
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );

            return response()->json([
                'user' => $result['user'],
                'token' => $result['token'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Login failed: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * POST /register
     */
    public function registerChurch(RegisterChurchRequest $request)
    {
        $user = $this->authService->registerChurch(
            $request->input('churchName'),
            $request->input('adminName'),
            $request->input('adminEmail'),
            $request->input('adminPassword')
        );

        // Automatically log in the new admin user
        $token = $user->createToken('register-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out.'], Response::HTTP_OK);
    }
}
