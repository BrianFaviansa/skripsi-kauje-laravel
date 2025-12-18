<?php

namespace Modules\Collaboration\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Collaboration\Models\Collaboration;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CollaborationService
{
    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;
        $q = $query['q'] ?? null;
        $collaborationFieldId = $query['collaboration_field_id'] ?? null;
        $postedById = $query['posted_by_id'] ?? null;
        $sortBy = $query['sort_by'] ?? 'created_at';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $collaborationQuery = Collaboration::query();

        if ($q) {
            $collaborationQuery->where(function ($query) use ($q) {
                $query->where('title', 'ILIKE', "%{$q}%")
                    ->orWhere('content', 'ILIKE', "%{$q}%");
            });
        }

        if ($collaborationFieldId) {
            $collaborationQuery->where('collaboration_field_id', $collaborationFieldId);
        }

        if ($postedById) {
            $collaborationQuery->where('posted_by_id', $postedById);
        }

        $collaborationQuery->orderBy($sortBy, $sortOrder);

        $total = $collaborationQuery->count();
        $collaborations = $collaborationQuery
            ->with([
                'collaborationField:id,name',
                'postedBy:id,name,email',
            ])
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($collaboration) {
                return [
                    ...$collaboration->toArray(),
                    'collaboration_field' => $collaboration->collaborationField?->name,
                ];
            });

        return [
            'data' => $collaborations,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function getById(string $id): array
    {
        $collaboration = Collaboration::with([
            'collaborationField:id,name',
            'postedBy:id,name,profile_picture_url',
        ])->find($id);

        if (!$collaboration) {
            throw new NotFoundHttpException('Kolaborasi tidak ditemukan');
        }

        return [
            ...$collaboration->toArray(),
            'collaboration_field' => $collaboration->collaborationField?->name,
        ];
    }

    public function create(User $user, array $data): Collaboration
    {
        $data['posted_by_id'] = $user->id;

        if (empty($data['collaboration_field_id'])) {
            unset($data['collaboration_field_id']);
        }

        return Collaboration::create($data);
    }

    public function update(User $user, string $id, array $data): Collaboration
    {
        $collaboration = Collaboration::find($id);

        if (!$collaboration) {
            throw new NotFoundHttpException('Kolaborasi tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $collaboration);

        if (array_key_exists('collaboration_field_id', $data) && empty($data['collaboration_field_id'])) {
            $data['collaboration_field_id'] = null;
        }

        $collaboration->update($data);

        return $collaboration->fresh();
    }

    public function delete(User $user, string $id): void
    {
        $collaboration = Collaboration::find($id);

        if (!$collaboration) {
            throw new NotFoundHttpException('Kolaborasi tidak ditemukan');
        }

        $this->ensureOwnerOrAdmin($user, $collaboration);

        $collaboration->delete();
    }

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/collaborations', $filename, 'public');

        return '/storage/' . $path;
    }

    private function ensureOwnerOrAdmin(User $user, Collaboration $collaboration): void
    {
        $user->load('role');

        if ($user->role->name !== 'Admin' && $collaboration->posted_by_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk mengubah kolaborasi ini');
        }
    }
}
