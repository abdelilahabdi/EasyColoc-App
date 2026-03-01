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
            $table->foreignId('debtor_id')->references('id')->on('users');
            $table->foreignId('creditor_id')->references('id')->on('users');
            $table->foreignId('colocation_id')->references('id')->on('colocations');
            $table->foreignId('expense_id')->references('id')->on('expenses');
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
