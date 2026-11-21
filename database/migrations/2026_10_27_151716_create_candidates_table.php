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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('candidate_number')->unique();
            $table->string('chairman_name');
            $table->string('deputy_name');
            $table->string('faculty_info');
            $table->text('vision');
            $table->json('missions');
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
