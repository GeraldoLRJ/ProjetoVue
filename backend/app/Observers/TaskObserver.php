<?php

namespace App\Observers;

use App\Jobs\SendTaskCreatedEmail;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        // Dispara o job com atraso de 1 minuto.
        SendTaskCreatedEmail::dispatch($task)->delay(now()->addMinute());
    }
}
