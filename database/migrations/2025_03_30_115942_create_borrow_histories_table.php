<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrow_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->references('id')->on('books')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('borrow_user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('borrow_date')->nullable();
            $table->timestamp('return_date')->nullable();
            $table->enum("status", ["R", "B"])->comment("R-Returned , B-Borrowed")->default("B");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_histories');
    }
};
