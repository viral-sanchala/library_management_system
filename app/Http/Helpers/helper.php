<?php

use App\Models\Book;

function validateBook($book_id)
{
    $check = Book::where('id', $book_id)->first();
    return $check;
}

function isAdmin()
{
    $user = auth('api')->user();
    if (!empty($user->user_role)) {
        return $user->user_role->slug == 'admin' ? 1 : 0;
    } else {
        return 0;
    }
}