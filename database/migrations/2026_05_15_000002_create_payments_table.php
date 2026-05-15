<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('RWF');
            $table->date('payment_date');
            $table->string('method')->default('cash'); // cash, bank_transfer, mtn_momo, airtel_money, other
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add share_token column to quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('share_token')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
