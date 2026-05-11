<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            // 'plan' column already exists, just modify the default
            $table->string('plan_status')->default('active')->after('plan');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_status');
            $table->integer('invoices_this_month')->default(0)->after('plan_expires_at');
            $table->string('last_invoice_month')->nullable()->after('invoices_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['plan_status', 'plan_expires_at', 'invoices_this_month', 'last_invoice_month']);
        });
    }
};