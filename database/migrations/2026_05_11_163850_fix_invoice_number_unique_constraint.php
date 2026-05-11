<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the global unique constraint
            $table->dropUnique('invoices_invoice_number_unique');
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            // Add composite unique - unique per workspace
            $table->unique(['workspace_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'invoice_number']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_number');
        });
    }
};