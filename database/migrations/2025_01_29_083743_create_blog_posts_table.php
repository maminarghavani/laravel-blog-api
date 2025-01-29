<?php

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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->boolean('is_draft')->default(false);
            $table->boolean("is_published")->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('category');
            $table->foreign('category')->references('id')->on('blog_categories')->onDelete("cascade");
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users')->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
