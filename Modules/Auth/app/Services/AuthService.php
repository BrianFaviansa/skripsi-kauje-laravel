<?php

namespace Modules\Auth\Services;

use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @throws ValidationException
     */
    public function register(array $data): User
    {
        $city = City::find($data['city_id']);

        if (! $city) {
            throw ValidationException::withMessages([
                'city_id' => ['Kota tidak ditemukan'],
            ]);
        }

        if ($city->province_id !== $data['province_id']) {
            throw ValidationException::withMessages([
                'city_id' => ['Kota tidak sesuai dengan provinsi yang dipilih'],
            ]);
        }

        if (empty($data['role_id'])) {
            $alumniRole = Role::where('name', 'Alumni')->first();

            if (! $alumniRole) {
                throw ValidationException::withMessages([
                    'role_id' => ['Role default "Alumni" tidak ditemukan'],
                ]);
            }

            $data['role_id'] = $alumniRole->id;
        }

        $user = User::create($data);

        $user->load(['role', 'province', 'city', 'faculty', 'major']);

        return $user;
    }

    /**
     * Login a user and return access token.
     *
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $user = User::where('nim', $data['nim'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'nim' => ['NIM atau password salah'],
            ]);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'nim' => ['NIM atau password salah'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Get current authenticated user with relationships.
     */
    public function me(User $user): User
    {
        $user->load(['role', 'province', 'city', 'faculty', 'major']);

        return $user;
    }

    /**
     * Logout user by deleting current access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Upload verification file and return the URL.
     */
    public function uploadVerificationFile(UploadedFile $file): string
    {
        $filename = uniqid().'_'.time().'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('uploads/verification', $filename, 'public');

        return '/storage/'.$path;
    }
}
