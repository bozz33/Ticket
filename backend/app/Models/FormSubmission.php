<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'form_definition_id',
        'submitter_user_id',
        'status',
        'data',
        'files',
        'ip_hash',
        'user_agent_hash',
        'submitted_at',
        'reviewed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'files' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }
}
