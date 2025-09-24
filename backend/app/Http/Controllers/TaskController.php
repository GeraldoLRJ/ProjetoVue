<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use Illuminate\Http\Request;

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
        if ($user->role !== $user::ROLE_MASTER) {
            $query->where(function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id)
                  ->orWhere('user_id', $user->id);
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        return $query->orderByDesc('id')->paginate(15);
    }

    public function store(TaskStoreRequest $request)
    {
        $user = auth('api')->user();
        $data = $request->validated();

        if ($user->role === $user::ROLE_MASTER && !empty($data['tenant_id'])) {
        } else {
            $data['tenant_id'] = $user->tenant_id;
        }

        $data['user_id'] = $data['user_id'] ?? $user->id;

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
        unset($data['tenant_id']);
        unset($data['user_id']);
        $task->update($data);
        return $task;
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
