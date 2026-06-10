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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->integer('default_price');
            $table->string('picture');
            $table->text('content');
            $table->boolean('has_discount')->default(false);
            $table->integer('discount_percent')->nullable();
            $table->date('discount_start')->nullable();
            $table->date('discount_end')->nullable();
            $table->text('discount_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
