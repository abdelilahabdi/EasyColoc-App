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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            

            $table->foreignId('debtor_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('creditor_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('colocation_id')->constrained()->cascadeOnDelete();

            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();



            $table->decimal('amount' , 9 , 2);
            $table->enum('status' , ['pending' , 'paid']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
