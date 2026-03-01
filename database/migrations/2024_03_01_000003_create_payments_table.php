<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colocation_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('from_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('to_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['colocation_id', 'from_user_id', 'to_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
