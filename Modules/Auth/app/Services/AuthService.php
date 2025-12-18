<?php

namespace Modules\Auth\Services;

use App\Models\User;
use App\Models\City;
use App\Models\Role;
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
        // Check if city belongs to the selected province
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

        // If no role_id provided, assign default "Alumni" role
        if (empty($data['role_id'])) {
            $alumniRole = Role::where('name', 'Alumni')->first();

            if (! $alumniRole) {
                throw ValidationException::withMessages([
                    'role_id' => ['Role default "Alumni" tidak ditemukan'],
                ]);
            }

            $data['role_id'] = $alumniRole->id;
        }

        // Create user (password will be automatically hashed by the model cast)
        $user = User::create($data);

        // Load relationships for response
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

        // Create Sanctum token
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
        // Revoke the current token
        $user->currentAccessToken()->delete();
    }

    /**
     * Upload verification file and return the URL.
     */
    public function uploadVerificationFile(UploadedFile $file): string
    {
        // Generate unique filename
        $filename = uniqid().'_'.time().'.'.$file->getClientOriginalExtension();

        // Store file in public/uploads/verification
        $path = $file->storeAs('uploads/verification', $filename, 'public');

        // Return the URL
        return '/storage/'.$path;
    }
}
