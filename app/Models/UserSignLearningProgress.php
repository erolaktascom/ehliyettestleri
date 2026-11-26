<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSignLearningProgress extends Model
{
    protected $table = 'user_sign_learning_progress';

    protected $fillable = ['user_id', 'traffic_sign_category_id', 'traffic_sign_id', 'filter'];
}
