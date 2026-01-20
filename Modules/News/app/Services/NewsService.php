<?php

namespace Modules\News\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsService
{
    private const CACHE_TTL_LIST = 60; // 1 minute
    private const CACHE_TTL_ITEM = 300; // 5 minutes
    private const CACHE_PREFIX = 'news:';

    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $q = $query['q'] ?? null;
        $startDate = $query['start_date'] ?? null;
        $endDate = $query['end_date'] ?? null;
        $sortBy = $query['sort_by'] ?? 'date';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $cacheKey = self::CACHE_PREFIX . "list:{$page}:{$limit}:" . md5(json_encode($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($q, $startDate, $endDate, $sortBy, $sortOrder, $page, $limit) {
            $newsQuery = News::query();

            if ($q) {
                $newsQuery->where(function ($query) use ($q) {
                    $query->where('title', 'ILIKE', "%{$q}%")
                        ->orWhere('content', 'ILIKE', "%{$q}%");
                });
            }

            if ($startDate) {
                $newsQuery->where('date', '>=', $startDate);
            }

            if ($endDate) {
                $newsQuery->where('date', '<=', $endDate);
            }

            $newsQuery->orderBy($sortBy, $sortOrder);

            $total = $newsQuery->count();
            $news = $newsQuery
                ->with(['postedBy:id,name,email'])
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            return [
                'data' => $news,
                'meta' => [
                    'total' => $total,
                    'page' => (int) $page,
                    'limit' => (int) $limit,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ];
        });
    }

    public function getById(string $id): News
    {
        $cacheKey = self::CACHE_PREFIX . "item:{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_ITEM, function () use ($id) {
            $news = News::with(['postedBy:id,name,profile_picture_url'])->find($id);

            if (!$news) {
                throw new NotFoundHttpException('Berita tidak ditemukan');
            }

            return $news;
        });
    }

    public function create(User $user, array $data): News
    {
        $this->ensureAdmin($user);

        $data['posted_by_id'] = $user->id;

        $news = News::create($data);

        // Invalidate list cache
        $this->invalidateCache();

        return $news;
    }

    public function update(User $user, string $id, array $data): News
    {
        $this->ensureAdmin($user);

        $news = News::find($id);

        if (!$news) {
            throw new NotFoundHttpException('Berita tidak ditemukan');
        }

        $news->update($data);

        // Invalidate caches
        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");

        return $news->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $this->ensureAdmin($user);

        $news = News::find($id);

        if (!$news) {
            throw new NotFoundHttpException('Berita tidak ditemukan');
        }

        $news->delete();

        // Invalidate caches
        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");
    }

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/news', $filename, 'public');

        return '/storage/' . $path;
    }

    private function ensureAdmin(User $user): void
    {
        $user->load('role');

        if ($user->role->name !== 'Admin') {
            throw new AccessDeniedHttpException('Hanya admin yang dapat melakukan aksi ini');
        }
    }

    private function invalidateCache(): void
    {
        // Clear all news list caches using Redis pattern
        try {
            $redis = Cache::getStore()->getRedis();
            $keys = $redis->keys(config('database.redis.options.prefix') . self::CACHE_PREFIX . 'list:*');
            if (!empty($keys)) {
                // Remove prefix from keys for deletion
                $prefix = config('database.redis.options.prefix');
                $keysToDelete = array_map(fn($key) => str_replace($prefix, '', $key), $keys);
                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            // Fallback: just log and continue if Redis pattern delete fails
            \Log::warning('Failed to invalidate news cache: ' . $e->getMessage());
        }
    }
}
