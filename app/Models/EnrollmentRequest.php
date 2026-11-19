<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de matrícula de un usuario en un curso (R05F01). */
class EnrollmentRequest extends Model
{
    use HasFactory;

    public const PENDING = 'pendiente';

    public const APPROVED = 'aprobada';

    public const REJECTED = 'rechazada';

    protected $fillable = ['user_id', 'course_id', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
