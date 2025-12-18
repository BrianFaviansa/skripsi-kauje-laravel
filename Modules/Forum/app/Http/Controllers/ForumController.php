<?php

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Http\Requests\CreateCommentRequest;
use Modules\Forum\Http\Requests\CreateForumRequest;
use Modules\Forum\Http\Requests\GetCommentsRequest;
use Modules\Forum\Http\Requests\GetForumRequest;
use Modules\Forum\Http\Requests\UpdateCommentRequest;
use Modules\Forum\Http\Requests\UpdateForumRequest;
use Modules\Forum\Http\Requests\UploadForumImageRequest;
use Modules\Forum\Services\ForumService;

class ForumController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    // ========== Forum CRUD ==========

    public function index(GetForumRequest $request): JsonResponse
    {
        $result = $this->forumService->getAll($request->validated());

        return response()->json([
            'message' => 'Data forum berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()?->id;
        $result = $this->forumService->getById($id, $userId);

        return response()->json([
            'message' => 'Detail forum berhasil diambil',
            'data' => $result['forum'],
            'is_liked' => $result['is_liked'],
        ]);
    }

    public function store(CreateForumRequest $request): JsonResponse
    {
        $forum = $this->forumService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Forum berhasil dibuat',
            'data' => $forum,
        ], 201);
    }

    public function update(UpdateForumRequest $request, string $id): JsonResponse
    {
        $forum = $this->forumService->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Forum berhasil diupdate',
            'data' => $forum,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->forumService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Forum berhasil dihapus',
        ]);
    }

    public function uploadImage(UploadForumImageRequest $request): JsonResponse
    {
        $url = $this->forumService->uploadImage($request->file('file'));

        return response()->json([
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'image_url' => $url,
            ],
        ]);
    }

    // ========== Comment CRUD ==========

    public function getComments(GetCommentsRequest $request, string $forumId): JsonResponse
    {
        $result = $this->forumService->getComments($forumId, $request->validated());

        return response()->json([
            'message' => 'Data komentar berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function storeComment(CreateCommentRequest $request, string $forumId): JsonResponse
    {
        $comment = $this->forumService->createComment($request->user(), $forumId, $request->validated());

        return response()->json([
            'message' => 'Komentar berhasil dibuat',
            'data' => $comment,
        ], 201);
    }

    public function updateComment(UpdateCommentRequest $request, string $commentId): JsonResponse
    {
        $comment = $this->forumService->updateComment($request->user(), $commentId, $request->validated());

        return response()->json([
            'message' => 'Komentar berhasil diupdate',
            'data' => $comment,
        ]);
    }

    public function destroyComment(Request $request, string $commentId): JsonResponse
    {
        $this->forumService->deleteComment($request->user(), $commentId);

        return response()->json([
            'message' => 'Komentar berhasil dihapus',
        ]);
    }

    // ========== Like ==========

    public function toggleLike(Request $request, string $forumId): JsonResponse
    {
        $result = $this->forumService->toggleLike($request->user(), $forumId);

        return response()->json([
            'message' => $result['message'],
            'is_liked' => $result['is_liked'],
        ]);
    }

    public function getLikes(string $forumId): JsonResponse
    {
        $result = $this->forumService->getLikes($forumId);

        return response()->json([
            'message' => 'Data like berhasil diambil',
            'data' => $result['data'],
            'total' => $result['total'],
        ]);
    }
}
