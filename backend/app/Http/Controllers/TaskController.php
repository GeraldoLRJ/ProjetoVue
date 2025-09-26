<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = Task::query();
        // master sees everything
        if ($user->role !== $user::ROLE_MASTER) {
            // admin sees tasks owned by users in the same company (tenant),
            // but tasks owned by master users must be excluded
            if ($user->role === $user::ROLE_ADMIN) {
                $query->whereHas('user', function($q) use ($user) {
                    $q->where('tenant_id', $user->tenant_id)
                      ->where('role', '!=', $user::ROLE_MASTER);
                });
            } else {
                // regular users see only their own tasks
                $query->where('user_id', $user->id);
            }
            // For non-master viewers, always exclude tasks whose owner is a master user
            $query->whereDoesntHave('user', function($q) use ($user) {
                $q->where('role', $user::ROLE_MASTER);
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
    return $query->orderByDesc('id')->paginate(20);
    }

    public function store(TaskStoreRequest $request)
    {
        $user = auth('api')->user();
        $data = $request->validated();

        Log::info('Task created:', $request->all());

        if (empty($data['user_id'])) {
            $data['user_id'] = $user->id;
        } else {
            if ($user->role !== $user::ROLE_MASTER && $data['user_id'] !== $user->id) {
                return response()->json(['message' => 'Forbidden to set user_id'], 403);
            }
        }

        $task = Task::create($data);
        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return $task;
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $data = $request->validated();

        if (isset($data['user_id'])) {
            $authUser = auth('api')->user();
            if ($authUser->role !== $authUser::ROLE_MASTER) {
                unset($data['user_id']);
            }
        }

        $task->update($data);
        return $task;
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
