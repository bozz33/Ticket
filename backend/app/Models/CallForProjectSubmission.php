<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallForProjectSubmission extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $connection = 'tenant';

    protected $fillable = [
        'public_id',
        'call_for_project_id',
        'status',
        'applicant_name',
        'applicant_email',
        'phone_country_code',
        'phone_number',
        'country_code',
        'city_name',
        'answers',
        'files',
        'submitted_at',
        'reviewed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'files' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function callForProject(): BelongsTo
    {
        return $this->belongsTo(CallForProject::class);
    }
}
