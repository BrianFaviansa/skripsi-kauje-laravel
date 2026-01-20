<?php

namespace Modules\Product\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Models\Product;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{
    private const CACHE_TTL_LIST = 60;
    private const CACHE_TTL_ITEM = 300;
    private const CACHE_PREFIX = 'products:';

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

        $cacheKey = self::CACHE_PREFIX . "list:{$page}:{$limit}:" . md5(json_encode($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($q, $category, $minPrice, $maxPrice, $postedById, $sortBy, $sortOrder, $page, $limit) {
            $productQuery = Product::query();

            if ($q) {
                $productQuery->where(function ($query) use ($q) {
                    $query->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('description', 'ILIKE', "%{$q}%");
                });
            }

            if ($category) {
                $productQuery->where('category', $category);
            }

            if ($minPrice !== null) {
                $productQuery->where('price', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $productQuery->where('price', '<=', $maxPrice);
            }

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
        });
    }

    public function getById(string $id): Product
    {
        $cacheKey = self::CACHE_PREFIX . "item:{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_ITEM, function () use ($id) {
            $product = Product::with(['postedBy:id,name,profile_picture_url'])->find($id);

            if (!$product) {
                throw new NotFoundHttpException('Produk tidak ditemukan');
            }

            return $product;
        });
    }

    public function create(User $user, array $data): Product
    {
        $data['posted_by_id'] = $user->id;

        $product = Product::create($data);

        $this->invalidateCache();

        return $product;
    }

    public function update(User $user, string $id, array $data): Product
    {
        $product = Product::find($id);

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $product);

        $product->update($data);

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");

        return $product->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $product = Product::find($id);

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $product);

        $product->delete();

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");
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

    private function invalidateCache(): void
    {
        try {
            $redis = Cache::getStore()->getRedis();
            $keys = $redis->keys(config('database.redis.options.prefix') . self::CACHE_PREFIX . 'list:*');
            if (!empty($keys)) {
                $prefix = config('database.redis.options.prefix');
                $keysToDelete = array_map(fn($key) => str_replace($prefix, '', $key), $keys);
                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to invalidate products cache: ' . $e->getMessage());
        }
    }
}
