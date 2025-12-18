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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('category'); 
            $table->string('image_url')->nullable();
            $table->uuid('posted_by_id');
            $table->timestamps();

            $table->foreign('posted_by_id')->references('id')->on('users')->onDelete('cascade');

            $table->index('category');
            $table->index('price');
            $table->index('posted_by_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
