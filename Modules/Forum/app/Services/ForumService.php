<?php

namespace Modules\Forum\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumComment;
use Modules\Forum\Models\ForumLike;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ForumService
{
    private const CACHE_TTL_LIST = 60;
    private const CACHE_TTL_ITEM = 300;
    private const CACHE_PREFIX = 'forums:';

    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $q = $query['q'] ?? null;
        $postedById = $query['posted_by_id'] ?? null;
        $sortBy = $query['sort_by'] ?? 'created_at';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $cacheKey = self::CACHE_PREFIX . "list:{$page}:{$limit}:" . md5(json_encode($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($q, $postedById, $sortBy, $sortOrder, $page, $limit) {
            $forumQuery = Forum::query();

            if ($q) {
                $forumQuery->where(function ($query) use ($q) {
                    $query->where('title', 'ILIKE', "%{$q}%")
                        ->orWhere('content', 'ILIKE', "%{$q}%");
                });
            }

            if ($postedById) {
                $forumQuery->where('posted_by_id', $postedById);
            }

            $forumQuery->orderBy($sortBy, $sortOrder);

            $total = $forumQuery->count();
            $forums = $forumQuery
                ->with(['postedBy:id,name,email,profile_picture_url'])
                ->withCount(['comments', 'forumLikes'])
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            return [
                'data' => $forums,
                'meta' => [
                    'total' => $total,
                    'page' => (int) $page,
                    'limit' => (int) $limit,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ];
        });
    }

    public function getById(string $id, ?string $userId = null): array
    {
        $cacheKey = self::CACHE_PREFIX . "item:{$id}";

        $forum = Cache::remember($cacheKey, self::CACHE_TTL_ITEM, function () use ($id) {
            $forum = Forum::with(['postedBy:id,name,profile_picture_url'])
                ->withCount(['comments', 'forumLikes'])
                ->find($id);

            if (!$forum) {
                throw new NotFoundHttpException('Forum tidak ditemukan');
            }

            return $forum;
        });

        $isLiked = false;
        if ($userId) {
            $isLiked = ForumLike::where('forum_id', $id)
                ->where('liked_by_id', $userId)
                ->exists();
        }

        return [
            'forum' => $forum,
            'is_liked' => $isLiked,
        ];
    }

    public function create(User $user, array $data): Forum
    {
        $data['posted_by_id'] = $user->id;

        $forum = Forum::create($data);

        $this->invalidateCache();

        return $forum;
    }

    public function update(User $user, string $id, array $data): Forum
    {
        $forum = Forum::find($id);

        if (!$forum) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $forum->posted_by_id);

        $forum->update($data);

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");

        return $forum->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $forum = Forum::find($id);

        if (!$forum) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $forum->posted_by_id);

        $forum->delete();

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");
    }

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/forums', $filename, 'public');

        return '/storage/' . $path;
    }

    public function getComments(string $forumId, array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $sortOrder = $query['sort_order'] ?? 'asc';

        if (!Forum::find($forumId)) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $commentQuery = ForumComment::where('forum_id', $forumId)
            ->orderBy('created_at', $sortOrder);

        $total = $commentQuery->count();
        $comments = $commentQuery
            ->with(['postedBy:id,name,profile_picture_url'])
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'data' => $comments,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function createComment(User $user, string $forumId, array $data): ForumComment
    {
        if (!Forum::find($forumId)) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $comment = ForumComment::create([
            'content' => $data['content'],
            'posted_by_id' => $user->id,
            'forum_id' => $forumId,
        ]);

        $this->invalidateCache();

        return $comment;
    }

    public function updateComment(User $user, string $commentId, array $data): ForumComment
    {
        $comment = ForumComment::find($commentId);

        if (!$comment) {
            throw new NotFoundHttpException('Komentar tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $comment->posted_by_id);

        $comment->update($data);

        return $comment->fresh();
    }

    public function deleteComment(User $user, string $commentId): void
    {
        $comment = ForumComment::find($commentId);

        if (!$comment) {
            throw new NotFoundHttpException('Komentar tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $comment->posted_by_id);

        $comment->delete();

        $this->invalidateCache();
    }

    public function toggleLike(User $user, string $forumId): array
    {
        if (!Forum::find($forumId)) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $existingLike = ForumLike::where('forum_id', $forumId)
            ->where('liked_by_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $result = ['message' => 'Forum berhasil di-unlike', 'is_liked' => false];
        } else {
            ForumLike::create([
                'forum_id' => $forumId,
                'liked_by_id' => $user->id,
            ]);
            $result = ['message' => 'Forum berhasil di-like', 'is_liked' => true];
        }

        $this->invalidateCache();

        return $result;
    }

    public function getLikes(string $forumId): array
    {
        if (!Forum::find($forumId)) {
            throw new NotFoundHttpException('Forum tidak ditemukan');
        }

        $likes = ForumLike::where('forum_id', $forumId)
            ->with(['likedBy:id,name,profile_picture_url'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'data' => $likes,
            'total' => $likes->count(),
        ];
    }

    private function ensureOwnerOrAdmin(User $user, string $ownerId): void
    {
        $user->load('role');

        $isAdmin = $user->role->name === 'Admin';
        $isOwner = $ownerId === $user->id;

        if (!$isAdmin && !$isOwner) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk melakukan aksi ini');
        }
    }

    private function invalidateCache(): void
    {
        try {
            $redis = Cache::getStore()->getRedis();
            $keys = $redis->keys(config('database.redis.options.prefix') . self::CACHE_PREFIX . '*');
            if (!empty($keys)) {
                $prefix = config('database.redis.options.prefix');
                $keysToDelete = array_map(fn($key) => str_replace($prefix, '', $key), $keys);
                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to invalidate forums cache: ' . $e->getMessage());
        }
    }
}
