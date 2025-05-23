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
        Schema::create('product_vocabulary', function (Blueprint $table) {
            $table->uuid('vocabulary_id');
            $table->uuid('product_id');
            $table->foreign('vocabulary_id')
                ->references('id')->on('vocabularies')
                ->cascadeOnDelete();
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();
            $table->primary(['vocabulary_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_vocabulary');
    }
};
