<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WcagScan extends Model
{
    protected $fillable = ['url', 'page_title', 'issues', 'issue_count', 'scanned_at'];

    protected $casts = [
        'issues'     => 'array',
        'scanned_at' => 'datetime',
    ];

    public function scopeHasIssues($query)
    {
        return $query->where('issue_count', '>', 0);
    }

    public function issuesBySeverity(): array
    {
        $grouped = ['error' => [], 'warning' => []];

        foreach ($this->issues ?? [] as $issue) {
            $level = $issue['level'] ?? 'warning';
            $grouped[$level][] = $issue;
        }

        return $grouped;
    }
}
