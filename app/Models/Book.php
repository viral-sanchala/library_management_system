<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];

    protected $keyType = 'string';

    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function borrowings()
    {
        return $this->hasMany(BorrowHistory::class, 'book_id', 'id');
    }

    public function borrower_details()
    {
        return $this->hasOne(User::class, 'id', 'borrow_user_id');
    }

}
