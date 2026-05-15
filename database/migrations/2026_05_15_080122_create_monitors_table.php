<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('url');
            $table->integer('check_interval');
            $table->integer('threshold');
            $table->string('status');
            $table->float('uptime_percentage')
                ->nullable();
            $table->timestamp('last_checked_at')
                ->nullable();
            $table->timestamp('next_check_at')
                ->nullable()
                ->index();
            $table->timestamps();
            $table->unique(['user_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
