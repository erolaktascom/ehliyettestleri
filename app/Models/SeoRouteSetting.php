<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SeoRouteSetting extends Model
{
    use \App\Models\Concerns\HasSeo;
    protected $fillable = ['route_key'];
}