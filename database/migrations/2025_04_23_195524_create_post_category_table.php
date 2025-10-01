<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostCategoryTable extends Migration
{
    public function up()
    {
        Schema::create('post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');  // Связь с публикацией
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');  // Связь с категорией
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_category');
    }
}

