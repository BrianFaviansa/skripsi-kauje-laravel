<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\CreateUserRequest;
use Modules\User\Http\Requests\GetUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Services\UserService;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(GetUserRequest $request): JsonResponse
    {
        $result = $this->userService->getAll($request->validated());

        return response()->json([
            'message' => 'Berhasil mengambil data user',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'User berhasil dibuat',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(string $id): JsonResponse
    {
        $user = $this->userService->getById($id);

        return response()->json([
            'message' => 'Berhasil mengambil data user',
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->userService->update(
            $request->user(),
            $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'User berhasil diupdate',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->userService->delete(
            request()->user(),
            $id
        );

        return response()->json([
            'message' => 'User berhasil dihapus',
        ]);
    }
}
