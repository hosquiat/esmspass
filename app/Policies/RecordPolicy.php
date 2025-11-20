<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

class RecordPolicy
{
    /**
     * Determine whether the user can view any records.
     * All authenticated users can view records.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the record.
     * All authenticated users can view records.
     */
    public function view(User $user, Record $record): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create records.
     * All authenticated users can create records.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the record.
     * All authenticated users can update all records.
     * (You could restrict this to only the creator if needed)
     */
    public function update(User $user, Record $record): bool
    {
        // Option 1: Anyone can update any record
        return true;

        // Option 2: Only creator or admin can update
        // return $user->id === $record->created_by || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the record.
     * Only admins can hard delete records.
     */
    public function delete(User $user, Record $record): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the record.
     * All authenticated users can restore archived records.
     */
    public function restore(User $user, Record $record): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the record.
     * Only admins can permanently delete records.
     */
    public function forceDelete(User $user, Record $record): bool
    {
        return $user->isAdmin();
    }
}
