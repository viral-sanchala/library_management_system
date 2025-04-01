<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RolePermission extends Model
{
    protected $table = "role_permissions";

    protected $primaryKey = 'id'; // Ensure primary key is 'uuid'
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'role_id', 'permission_id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }
}
