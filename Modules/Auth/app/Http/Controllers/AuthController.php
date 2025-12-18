<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\UploadVerificationFileRequest;
use Modules\Auth\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registrasi berhasil',
            'data' => $user,
        ], 201);
    }

    /**
     * Login a user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login berhasil',
            'data' => $result,
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return response()->json([
            'message' => 'Data user berhasil diambil',
            'data' => $user,
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Upload verification file.
     */
    public function uploadVerificationFile(UploadVerificationFileRequest $request): JsonResponse
    {
        $url = $this->authService->uploadVerificationFile($request->file('file'));

        return response()->json([
            'message' => 'File berhasil diupload',
            'data' => [
                'verification_file_url' => $url,
            ],
        ]);
    }
}
