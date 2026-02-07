<?php

namespace App\Policies;

use App\Models\ServiceListing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceListingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceListing $listing): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isContractor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceListing $listing): bool
    {
        return $user->id === $listing->contractor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceListing $listing): bool
    {
        return $user->id === $listing->contractor_id;
    }

    /**
     * Determine whether the user can manage photos for the listing.
     */
    public function managePhotos(User $user, ServiceListing $listing): bool
    {
        return $user->id === $listing->contractor_id;
    }

    /**
     * Determine whether the user can toggle the listing status.
     */
    public function toggleStatus(User $user, ServiceListing $listing): bool
    {
        return $user->id === $listing->contractor_id;
    }
}