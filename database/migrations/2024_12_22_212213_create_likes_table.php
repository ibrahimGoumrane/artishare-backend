Schema::create('likes', function (Blueprint $table) {
$table->id(); // Primary key
$table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key references 'users'
$table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade'); // Foreign key references 'blogs'
$table->timestamps(); // created_at and updated_at
});<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
        $table->id(); // Primary key
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key references 'users'
        $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade'); // Foreign key references 'blogs'
        $table->timestamps(); // created_at and updated_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
