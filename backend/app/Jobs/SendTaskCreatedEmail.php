<?php

namespace App\Jobs;

use App\Mail\NewTaskCreated;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTaskCreatedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;

    /**
     * Maximum number of attempts before the job fails permanently.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     *
     * @var int|array
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('SendTaskCreatedEmail: handle start', [
            'task_id' => $this->task->id ?? null,
            'queue' => $this->queue,
        ]);
        $user = $this->task->user;
        if (!$user || !$user->email) {
            Log::warning('SendTaskCreatedEmail: missing user/email', [
                'task_id' => $this->task->id ?? null,
            ]);
            return;
        }

        Mail::to($user->email)->send(new NewTaskCreated($this->task));
        Log::info('SendTaskCreatedEmail: email sent', [
            'task_id' => $this->task->id ?? null,
            'to' => $user->email,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception)
    {
        Log::error('SendTaskCreatedEmail: failed invoked', [
            'task_id' => $this->task->id ?? null,
            'error' => $exception->getMessage(),
        ]);

        try {
            DB::table('failed_jobs')->insert([
                'uuid' => (string) Str::uuid(),
                'connection' => config('queue.default'),
                'queue' => $this->queue ?? 'emails',
                'payload' => json_encode([
                    'job' => static::class,
                    'task_id' => $this->task->id ?? null,
                ]),
                'exception' => (string) $exception,
                'failed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log job failure: ' . $e->getMessage());
        }
    }
}
