<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $fillable = ['old_url', 'sources', 'new_url', 'type', 'status', 'usage_count', 'last_used_at'];
    protected $casts = ['sources' => 'array', 'usage_count' => 'integer', 'last_used_at' => 'datetime'];

    public function sourceEntries(): array
    {
        return $this->sources ?: [['url' => $this->old_url, 'match_type' => 'exact', 'ignore_case' => false]];
    }

}
