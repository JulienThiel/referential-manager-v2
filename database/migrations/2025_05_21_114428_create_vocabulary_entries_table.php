<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vocabulary_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vocabulary_id');
            $table->foreign('vocabulary_id')
                  ->references('id')
                  ->on('vocabularies')
                  ->cascadeOnDelete();

            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('vocabulary_entries')
                  ->nullOnDelete();

            $table->string('entry_value')->index();
            $table->json('entry_labels');
            $table->unsignedInteger('rank')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_entries');
    }
};
