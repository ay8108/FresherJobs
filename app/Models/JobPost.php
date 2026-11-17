<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPost extends Model
{
    protected $fillable = [
        'title',
        'company_name',
        'description',
        'location',
        'employment_type',
        'experience_level',
        'salary',
        'source_name',
        'source_url',
        'source_job_id',
        'ai_role',
        'ai_city',
        'ai_confidence',
        'ai_raw_response',
        'dedup_hash',
        'posted_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_confidence' => 'decimal:2',
            'posted_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}