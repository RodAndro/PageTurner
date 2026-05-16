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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->string('author', 200);
            $table->string('isbn', 13)->unique();
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('publisher', 200)->nullable();
            $table->date('publication_date')->nullable();
            $table->string('language', 50)->default('English');
            $table->unsignedInteger('page_count')->nullable();
            $table->string('format', 50)->default('Paperback');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('cover_image', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign key
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
