<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'datetime:Y-m-d H:i',
    ];

    public function setDueDateAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['due_date'] = null;
            return;
        }

        $this->attributes['due_date'] = Carbon::parse($value, 'America/Sao_Paulo')->utc();
    }

    public function getDueDateAttribute($value)
    {
        if (!$value) return null;

        return Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('Y-m-d H:i');
    }

    public function company()
    {
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
