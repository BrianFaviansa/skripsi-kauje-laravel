<?php

namespace Modules\Collaboration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Collaboration\Http\Requests\CreateCollaborationRequest;
use Modules\Collaboration\Http\Requests\GetCollaborationRequest;
use Modules\Collaboration\Http\Requests\UpdateCollaborationRequest;
use Modules\Collaboration\Http\Requests\UploadCollaborationImageRequest;
use Modules\Collaboration\Services\CollaborationService;

class CollaborationController extends Controller
{
    public function __construct(
        protected CollaborationService $collaborationService
    ) {}

    public function index(GetCollaborationRequest $request): JsonResponse
    {
        $result = $this->collaborationService->getAll($request->validated());

        return response()->json([
            'message' => 'Data kolaborasi berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $collaboration = $this->collaborationService->getById($id);

        return response()->json([
            'message' => 'Detail kolaborasi berhasil diambil',
            'data' => $collaboration,
        ]);
    }

    public function store(CreateCollaborationRequest $request): JsonResponse
    {
        $collaboration = $this->collaborationService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Kolaborasi berhasil dibuat',
            'data' => $collaboration,
        ], 201);
    }

    public function update(UpdateCollaborationRequest $request, string $id): JsonResponse
    {
        $collaboration = $this->collaborationService->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Kolaborasi berhasil diupdate',
            'data' => $collaboration,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->collaborationService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Kolaborasi berhasil dihapus',
        ]);
    }

    public function uploadImage(UploadCollaborationImageRequest $request): JsonResponse
    {
        $url = $this->collaborationService->uploadImage($request->file('file'));

        return response()->json([
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'image_url' => $url,
            ],
        ]);
    }
}
