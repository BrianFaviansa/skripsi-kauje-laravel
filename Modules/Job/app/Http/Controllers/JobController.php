<?php

namespace Modules\Job\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Job\Http\Requests\CreateJobRequest;
use Modules\Job\Http\Requests\GetJobRequest;
use Modules\Job\Http\Requests\UpdateJobRequest;
use Modules\Job\Http\Requests\UploadJobImageRequest;
use Modules\Job\Services\JobService;

class JobController extends Controller
{
    public function __construct(
        protected JobService $jobService
    ) {}

    public function index(GetJobRequest $request): JsonResponse
    {
        $result = $this->jobService->getAll($request->validated());

        return response()->json([
            'message' => 'Data lowongan berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $job = $this->jobService->getById($id);

        return response()->json([
            'message' => 'Detail lowongan berhasil diambil',
            'data' => $job,
        ]);
    }

    public function store(CreateJobRequest $request): JsonResponse
    {
        $job = $this->jobService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Lowongan berhasil dibuat',
            'data' => $job,
        ], 201);
    }

    public function update(UpdateJobRequest $request, string $id): JsonResponse
    {
        $job = $this->jobService->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Lowongan berhasil diupdate',
            'data' => $job,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->jobService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Lowongan berhasil dihapus',
        ]);
    }

    public function uploadImage(UploadJobImageRequest $request): JsonResponse
    {
        $url = $this->jobService->uploadImage($request->file('file'));

        return response()->json([
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'image_url' => $url,
            ],
        ]);
    }
}
