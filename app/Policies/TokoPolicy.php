<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Toko;
use Illuminate\Auth\Access\HandlesAuthorization;

class TokoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Toko');
    }

    public function view(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('View:Toko');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Toko');
    }

    public function update(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('Update:Toko');
    }

    public function delete(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('Delete:Toko');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Toko');
    }

    public function restore(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('Restore:Toko');
    }

    public function forceDelete(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('ForceDelete:Toko');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Toko');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Toko');
    }

    public function replicate(AuthUser $authUser, Toko $toko): bool
    {
        return $authUser->can('Replicate:Toko');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Toko');
    }

}