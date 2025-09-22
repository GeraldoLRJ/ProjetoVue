<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Apenas autenticado (já garantido pelo middleware)
    }

    public function view(User $user, Task $task): bool
    {
        return $user->role === User::ROLE_MASTER || $user->tenant_id === $task->tenant_id;
    }

    public function create(User $user): bool
    {
        return true; // autenticado pode criar no seu tenant (ou master em qualquer)
    }

    public function update(User $user, Task $task): bool
    {
        return $user->role === User::ROLE_MASTER || $user->tenant_id === $task->tenant_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->role === User::ROLE_MASTER || $user->tenant_id === $task->tenant_id;
    }
}
