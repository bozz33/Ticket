<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormDefinition extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'owner_type',
        'owner_id',
        'name',
        'title',
        'description',
        'submit_label',
        'success_message',
        'status',
        'schema',
        'validation_schema',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'validation_schema' => 'array',
            'settings' => 'array',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
