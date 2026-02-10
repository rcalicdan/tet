<?php

namespace App\Policies;

use App\Models\ServiceListing;
use App\Models\User;

class ServiceListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, ServiceListing $listing): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isContractor();
    }

    public function update(User $user, ServiceListing $listing): bool
    {
        return $user->is_active 
            && $user->isContractor() 
            && $user->id === $listing->contractor_id;
    }

    public function delete(User $user, ServiceListing $listing): bool
    {
        return $user->is_active 
            && $user->isContractor() 
            && $user->id === $listing->contractor_id;
    }

    public function managePhotos(User $user, ServiceListing $listing): bool
    {
        return $user->is_active 
            && $user->isContractor() 
            && $user->id === $listing->contractor_id;
    }

    public function toggleStatus(User $user, ServiceListing $listing): bool
    {
        return $user->is_active 
            && $user->isContractor() 
            && $user->id === $listing->contractor_id;
    }
}