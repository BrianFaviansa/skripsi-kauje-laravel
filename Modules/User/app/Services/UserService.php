<?php

namespace Modules\User\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    public function getAll(array $query): array
    {
        $page = $query['page'] ?? 1;
        $limit = $query['per_page'] ?? 10;
        $q = $query['search'] ?? null;
        $facultyId = $query['faculty_id'] ?? null;
        $majorId = $query['major_id'] ?? null;
        $provinceId = $query['province_id'] ?? null;
        $cityId = $query['city_id'] ?? null;
        $enrollmentYear = $query['enrollment_year'] ?? null;
        $graduationYear = $query['graduation_year'] ?? null;
        $sortBy = $query['sort_by'] ?? 'created_at';
        $sortOrder = $query['sort_order'] ?? 'desc';

        $userQuery = User::query();

        // Exclude Admin users
        $userQuery->whereHas('role', function ($roleQuery) {
            $roleQuery->where('name', '!=', 'Admin');
        });

        if ($q) {
            $userQuery->where(function ($query) use ($q) {
                $query->where('name', 'ILIKE', "%{$q}%")
                    ->orWhere('nim', 'ILIKE', "%{$q}%")
                    ->orWhere('email', 'ILIKE', "%{$q}%");
            });
        }

        if ($facultyId) {
            $userQuery->where('faculty_id', $facultyId);
        }

        if ($majorId) {
            $userQuery->where('major_id', $majorId);
        }

        if ($provinceId) {
            $userQuery->where('province_id', $provinceId);
        }

        if ($cityId) {
            $userQuery->where('city_id', $cityId);
        }

        if ($enrollmentYear) {
            $userQuery->where('enrollment_year', $enrollmentYear);
        }

        if ($graduationYear) {
            $userQuery->where('graduation_year', $graduationYear);
        }

        $userQuery->orderBy($sortBy, $sortOrder);

        $total = $userQuery->count();
        $users = $userQuery
            ->with([
                'role:id,name',
                'province:id,name',
                'city:id,name',
                'faculty:id,name',
                'major:id,name',
            ])
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nim' => $user->nim,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'enrollment_year' => $user->enrollment_year,
                    'graduation_year' => $user->graduation_year,
                    'instance' => $user->instance,
                    'position' => $user->position,
                    'verification_status' => $user->verification_status,
                    'profile_picture_url' => $user->profile_picture_url,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'role' => $user->role?->name,
                    'province' => $user->province?->name,
                    'city' => $user->city?->name,
                    'faculty' => $user->faculty?->name,
                    'major' => $user->major?->name,
                ];
            });

        return [
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => (int) $page,
                'per_page' => (int) $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function getById(string $id): array
    {
        $user = User::with([
            'role:id,name',
            'province:id,name',
            'city:id,name',
            'faculty:id,name',
            'major:id,name',
        ])->find($id);

        if (!$user) {
            throw new NotFoundHttpException('User tidak ditemukan');
        }

        return [
            'id' => $user->id,
            'nim' => $user->nim,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'enrollment_year' => $user->enrollment_year,
            'graduation_year' => $user->graduation_year,
            'instance' => $user->instance,
            'position' => $user->position,
            'verification_status' => $user->verification_status,
            'verification_file_url' => $user->verification_file_url,
            'profile_picture_url' => $user->profile_picture_url,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'role' => $user->role?->name,
            'province' => $user->province?->name,
            'city' => $user->city?->name,
            'faculty' => $user->faculty?->name,
            'major' => $user->major?->name,
        ];
    }

    public function create(User $admin, array $data): User
    {
        $this->ensureAdmin($admin);

        // Check for existing user
        $existingUser = User::where('email', $data['email'])
            ->orWhere('nim', $data['nim'])
            ->orWhere('phone_number', $data['phone_number'])
            ->first();

        if ($existingUser) {
            if ($existingUser->email === $data['email']) {
                throw new ConflictHttpException('Email sudah terdaftar');
            }
            if ($existingUser->nim === $data['nim']) {
                throw new ConflictHttpException('NIM sudah terdaftar');
            }
            if ($existingUser->phone_number === $data['phone_number']) {
                throw new ConflictHttpException('Nomor telepon sudah terdaftar');
            }
        }

        // Get default role if not provided
        if (empty($data['role_id'])) {
            $alumniRole = Role::where('name', 'Alumni')->first();
            if (!$alumniRole) {
                throw new NotFoundHttpException('Role Alumni tidak ditemukan');
            }
            $data['role_id'] = $alumniRole->id;
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Auto verify users created by admin
        $data['verification_status'] = 'VERIFIED';

        return User::create($data);
    }

    public function update(User $admin, string $id, array $data): User
    {
        $this->ensureAdmin($admin);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundHttpException('User tidak ditemukan');
        }

        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }

    public function delete(User $admin, string $id): void
    {
        $this->ensureAdmin($admin);

        $user = User::find($id);

        if (!$user) {
            throw new NotFoundHttpException('User tidak ditemukan');
        }

        // Prevent deleting admin users
        $user->load('role');
        if ($user->role?->name === 'Admin') {
            throw new AccessDeniedHttpException('Tidak dapat menghapus user Admin');
        }

        $user->delete();
    }

    private function ensureAdmin(User $user): void
    {
        $user->load('role');

        if ($user->role?->name !== 'Admin') {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk mengakses fitur ini');
        }
    }
}
