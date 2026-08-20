<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escalation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notified_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('triggered_at');
            $table->text('message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_logs');
    }
};