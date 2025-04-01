<?php

namespace App\Listeners;

use App\Events\BookBorrowed;
use App\Mail\BookBorrowedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendBookBorrowNotification
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookBorrowed $event): void
    {
        Mail::to($event->user->email)->send(new BookBorrowedMail($event->user, $event->book));
    }
}
