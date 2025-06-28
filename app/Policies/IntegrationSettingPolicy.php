<?php

namespace App\Policies;

use App\Models\IntegrationSetting;
use App\Models\User;

class IntegrationSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'vendor' || $user->is_admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IntegrationSetting $integrationSetting): bool
    {
        return $user->id === $integrationSetting->vendor_id || $user->is_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'vendor' || $user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IntegrationSetting $integrationSetting): bool
    {
        return $user->id === $integrationSetting->vendor_id || $user->is_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IntegrationSetting $integrationSetting): bool
    {
        return $user->id === $integrationSetting->vendor_id || $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IntegrationSetting $integrationSetting): bool
    {
        return $user->id === $integrationSetting->vendor_id || $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IntegrationSetting $integrationSetting): bool
    {
        return $user->is_admin;
    }
}
