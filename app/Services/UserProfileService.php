<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function uploadProfilePhoto(User $user, UploadedFile $file): User
    {
        DB::beginTransaction();
        
        try {
            $this->deleteProfilePhotoFile($user);

            $path = $file->store('profile_photos', 'public');
            
            $user->update(['profile_photo' => $path]);
            
            DB::commit();
            
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
        
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            
            throw $e;
        }
    }

    public function deleteProfilePhoto(User $user): User
    {
        DB::beginTransaction();
        
        try {
            $this->deleteProfilePhotoFile($user);
            
            $user->update(['profile_photo' => null]);
            
            DB::commit();
            
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getPublicProfile(string $userId): ?User
    {
        return User::where('id', $userId)
            ->where('is_active', true)
            ->first();
    }

    public function deactivateAccount(User $user): User
    {
        DB::beginTransaction();
        
        try {
            $user->update(['is_active' => false]);
            
            $user->tokens()->delete();
            
            DB::commit();
            
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteAccount(User $user): bool
    {
        DB::beginTransaction();
        
        try {
            $this->deleteProfilePhotoFile($user);
            
            $user->tokens()->delete();
            
            $deleted = $user->delete();
            
            DB::commit();
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getProfilePhotoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url('storage/' . $path);
    }

    private function deleteProfilePhotoFile(User $user): void
    {
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }
    }

    public function isProfileComplete(User $user): bool
    {
        $requiredFields = ['first_name', 'last_name', 'email'];
        
        if ($user->isContractor()) {
            $requiredFields = array_merge($requiredFields, ['phone_number', 'city', 'bio']);
        }

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                return false;
            }
        }

        return true;
    }

    public function getProfileCompletionPercentage(User $user): int
    {
        $fields = ['first_name', 'last_name', 'email', 'phone_number', 'city', 'bio', 'profile_photo'];
        
        if ($user->isClient()) {
            $fields = array_diff($fields, ['bio']);
        }

        $totalFields = count($fields);
        $completedFields = 0;

        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / $totalFields) * 100);
    }
}