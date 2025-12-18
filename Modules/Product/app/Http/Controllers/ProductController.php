<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\CreateProductRequest;
use Modules\Product\Http\Requests\GetProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Requests\UploadProductImageRequest;
use Modules\Product\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(GetProductRequest $request): JsonResponse
    {
        $result = $this->productService->getAll($request->validated());

        return response()->json([
            'message' => 'Data produk berhasil diambil',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getById($id);

        return response()->json([
            'message' => 'Detail produk berhasil diambil',
            'data' => $product,
        ]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Produk berhasil dibuat',
            'data' => $product,
        ], 201);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->productService->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Produk berhasil diupdate',
            'data' => $product,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->productService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Produk berhasil dihapus',
        ]);
    }

    public function uploadImage(UploadProductImageRequest $request): JsonResponse
    {
        $url = $this->productService->uploadImage($request->file('file'));

        return response()->json([
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'image_url' => $url,
            ],
        ]);
    }
}
