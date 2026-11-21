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
        Schema::create('registration_audits', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('name');
            $table->string('faculty')->nullable();
            $table->string('status', 20); // accepted, rejected
            $table->string('reason');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_audits');
    }
};
