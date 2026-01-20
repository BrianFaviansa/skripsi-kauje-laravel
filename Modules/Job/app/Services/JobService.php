<?php

namespace Modules\Job\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\Job\Models\Job;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobService
{
    private const CACHE_TTL_LIST = 60;
    private const CACHE_TTL_ITEM = 300;
    private const CACHE_PREFIX = 'jobs:';

    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $q = $query['q'] ?? null;
        $jobType = $query['job_type'] ?? null;
        $provinceId = $query['province_id'] ?? null;
        $cityId = $query['city_id'] ?? null;
        $jobFieldId = $query['job_field_id'] ?? null;
        $company = $query['company'] ?? null;
        $sortBy = $query['sort_by'] ?? 'created_at';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $cacheKey = self::CACHE_PREFIX . "list:{$page}:{$limit}:" . md5(json_encode($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($q, $jobType, $provinceId, $cityId, $jobFieldId, $company, $sortBy, $sortOrder, $page, $limit) {
            $jobQuery = Job::query();

            if ($q) {
                $jobQuery->where(function ($query) use ($q) {
                    $query->where('title', 'ILIKE', "%{$q}%")
                        ->orWhere('company', 'ILIKE', "%{$q}%")
                        ->orWhere('content', 'ILIKE', "%{$q}%");
                });
            }

            if ($jobType) {
                $jobQuery->where('job_type', $jobType);
            }

            if ($provinceId) {
                $jobQuery->where('province_id', $provinceId);
            }

            if ($cityId) {
                $jobQuery->where('city_id', $cityId);
            }

            if ($jobFieldId) {
                $jobQuery->where('job_field_id', $jobFieldId);
            }

            if ($company) {
                $jobQuery->where('company', 'ILIKE', "%{$company}%");
            }

            $jobQuery->orderBy($sortBy, $sortOrder);

            $total = $jobQuery->count();
            $jobs = $jobQuery
                ->with([
                    'jobField:id,name',
                    'province:id,name',
                    'city:id,name',
                    'postedBy:id,name,email',
                ])
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($job) {
                    return [
                        ...$job->toArray(),
                        'job_field' => $job->jobField->name,
                        'province' => $job->province->name,
                        'city' => $job->city->name,
                    ];
                });

            return [
                'data' => $jobs,
                'meta' => [
                    'total' => $total,
                    'page' => (int) $page,
                    'limit' => (int) $limit,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ];
        });
    }

    public function getById(string $id): array
    {
        $cacheKey = self::CACHE_PREFIX . "item:{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_ITEM, function () use ($id) {
            $job = Job::with([
                'jobField:id,name',
                'province:id,name',
                'city:id,name',
                'postedBy:id,name,profile_picture_url',
            ])->find($id);

            if (!$job) {
                throw new NotFoundHttpException('Lowongan tidak ditemukan');
            }

            return [
                ...$job->toArray(),
                'job_field' => $job->jobField->name,
                'province' => $job->province->name,
                'city' => $job->city->name,
            ];
        });
    }

    public function create(User $user, array $data): Job
    {
        $data['posted_by_id'] = $user->id;

        $job = Job::create($data);

        $this->invalidateCache();

        return $job;
    }

    public function update(User $user, string $id, array $data): Job
    {
        $job = Job::find($id);

        if (!$job) {
            throw new NotFoundHttpException('Lowongan tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $job);

        $job->update($data);

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");

        return $job->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $job = Job::find($id);

        if (!$job) {
            throw new NotFoundHttpException('Lowongan tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $job);

        $job->delete();

        $this->invalidateCache();
        Cache::forget(self::CACHE_PREFIX . "item:{$id}");
    }

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/jobs', $filename, 'public');

        return '/storage/' . $path;
    }

    private function ensureOwnerOrAdmin(User $user, Job $job): void
    {
        $user->load('role');

        if ($user->role->name !== 'Admin' && $job->posted_by_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk mengubah lowongan ini');
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
            \Log::warning('Failed to invalidate jobs cache: ' . $e->getMessage());
        }
    }
}
