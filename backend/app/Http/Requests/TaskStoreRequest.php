<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,done',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            // Apenas master pode definir tenant_id explicitamente; será validado e tratado no controller
            'tenant_id' => 'nullable|integer|exists:companies,id',
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
