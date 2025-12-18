<?php

namespace Modules\Product\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Product\Models\Product;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{
    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $q = $query['q'] ?? null;
        $category = $query['category'] ?? null;
        $minPrice = $query['min_price'] ?? null;
        $maxPrice = $query['max_price'] ?? null;
        $postedById = $query['posted_by_id'] ?? null;
        $sortBy = $query['sort_by'] ?? 'created_at';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $productQuery = Product::query();

        // Search filter
        if ($q) {
            $productQuery->where(function ($query) use ($q) {
                $query->where('name', 'ILIKE', "%{$q}%")
                    ->orWhere('description', 'ILIKE', "%{$q}%");
            });
        }

        // Category filter
        if ($category) {
            $productQuery->where('category', $category);
        }

        // Price range filter
        if ($minPrice !== null) {
            $productQuery->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $productQuery->where('price', '<=', $maxPrice);
        }

        // Posted by filter
        if ($postedById) {
            $productQuery->where('posted_by_id', $postedById);
        }

        $productQuery->orderBy($sortBy, $sortOrder);

        $total = $productQuery->count();
        $products = $productQuery
            ->with(['postedBy:id,name,email'])
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'data' => $products,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function getById(string $id): Product
    {
        $product = Product::with(['postedBy:id,name,profile_picture_url'])->find($id);

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan');
        }

        return $product;
    }

    public function create(User $user, array $data): Product
    {
        $data['posted_by_id'] = $user->id;

        return Product::create($data);
    }

    public function update(User $user, string $id, array $data): Product
    {
        $product = Product::find($id);

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan');
        }

        // Authorization: Owner or Admin
        $this->ensureOwnerOrAdmin($user, $product);

        $product->update($data);

        return $product->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $product = Product::find($id);

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan');
        }

        // Authorization: Owner or Admin
        $this->ensureOwnerOrAdmin($user, $product);

        $product->delete();
    }

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/products', $filename, 'public');

        return '/storage/' . $path;
    }

    private function ensureOwnerOrAdmin(User $user, Product $product): void
    {
        $user->load('role');

        $isAdmin = $user->role->name === 'Admin';
        $isOwner = $product->posted_by_id === $user->id;

        if (!$isAdmin && !$isOwner) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk melakukan aksi ini');
        }
    }
}
