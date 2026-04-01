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
        $tableName = config('pardakht.transaction_table', 'pardakht_transactions');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code')->unique();
            $table->string('reference_id')->nullable()->index();
            $table->string('transaction_id')->nullable();
            $table->string('gateway', 50)->index();
            $table->unsignedBigInteger('amount');
            $table->string('order_id')->index();
            $table->text('description')->nullable();
            $table->string('mobile', 11)->nullable();
            $table->string('email')->nullable();
            $table->text('callback_url')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->string('card_number', 16)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_message')->nullable();
            $table->json('verification_data')->nullable();
            $table->json('metadata')->nullable();

            // Polymorphic relation to any model
            $table->nullableMorphs('payable');

            $table->timestamps();

            // Indexes for better query performance (payable_type + payable_id already indexed by nullableMorphs)
            $table->index(['status', 'gateway']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('pardakht.transaction_table', 'pardakht_transactions');
        Schema::dropIfExists($tableName);
    }
};
