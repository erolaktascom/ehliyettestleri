<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionImport extends Model
{
    protected $fillable = ['file_name', 'user_id', 'status', 'total', 'ready', 'failed', 'duplicate', 'imported'];
    protected $casts = ['total' => 'integer', 'ready' => 'integer', 'failed' => 'integer', 'duplicate' => 'integer', 'imported' => 'integer'];

    public function user() { return $this->belongsTo(User::class); }
}
