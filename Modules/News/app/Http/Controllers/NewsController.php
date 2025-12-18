<?php

namespace Modules\News\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\News\Http\Requests\CreateNewsRequest;
use Modules\News\Http\Requests\GetNewsRequest;
use Modules\News\Http\Requests\UpdateNewsRequest;
use Modules\News\Http\Requests\UploadNewsImageRequest;
use Modules\News\Services\NewsService;

class NewsController extends Controller
{
    public function __construct(
        protected NewsService $newsService
    ) {}

    public function index(GetNewsRequest $request): JsonResponse
    {
        $result = $this->newsService->getAll($request->validated());

        return response()->json([
            'message' => 'Data berita berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $news = $this->newsService->getById($id);

        return response()->json([
            'message' => 'Detail berita berhasil diambil',
            'data' => $news,
        ]);
    }

    public function store(CreateNewsRequest $request): JsonResponse
    {
        $news = $this->newsService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Berita berhasil dibuat',
            'data' => $news,
        ], 201);
    }

    public function update(UpdateNewsRequest $request, string $id): JsonResponse
    {
        $news = $this->newsService->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Berita berhasil diupdate',
            'data' => $news,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->newsService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Berita berhasil dihapus',
        ]);
    }

    public function uploadImage(UploadNewsImageRequest $request): JsonResponse
    {
        $url = $this->newsService->uploadImage($request->file('file'));

        return response()->json([
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'image_url' => $url,
            ],
        ]);
    }
}
