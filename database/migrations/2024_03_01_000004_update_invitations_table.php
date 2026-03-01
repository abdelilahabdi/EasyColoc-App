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
        Schema::table('invitations', function (Blueprint $table) {
            // Add expires_at column
            $table->timestamp('expires_at')->nullable()->after('declined_at');

            // Change enum from 'declined' to 'refused'
            $table->enum('status', ['pending', 'accepted', 'refused'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('expires_at');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->change();
        });
    }
};
