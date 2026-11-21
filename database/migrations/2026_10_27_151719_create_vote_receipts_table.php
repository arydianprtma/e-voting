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
        Schema::create('vote_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_code', 64)->unique();
            $table->string('voter_hash', 64)->index();
            $table->timestamp('voted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_receipts');
    }
};
