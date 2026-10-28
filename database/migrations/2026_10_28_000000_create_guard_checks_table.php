<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guard_checks', function (Blueprint $table): void {
            $table->id();
            $table->text('prompt');
            $table->string('verdict', 10);
            $table->json('reasons');
            $table->text('rewritten_prompt')->nullable();
            $table->text('answer')->nullable();
            $table->boolean('is_example')->default(false);
            $table->timestamps();

            $table->index(['is_example', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_checks');
    }
};
