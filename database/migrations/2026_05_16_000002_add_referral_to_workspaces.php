<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $columns = [
            'referral_code'          => fn(Blueprint $t) => $t->string('referral_code', 10)->nullable()->unique(),
            'referral_count'         => fn(Blueprint $t) => $t->unsignedInteger('referral_count')->default(0),
            'referral_bonus_invoices'=> fn(Blueprint $t) => $t->unsignedInteger('referral_bonus_invoices')->default(0),
            'referred_by'            => fn(Blueprint $t) => $t->string('referred_by', 10)->nullable(),
        ];

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn('workspaces', $column)) {
                Schema::table('workspaces', $callback);
            }
        }
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referral_count', 'referral_bonus_invoices', 'referred_by']);
        });
    }
};
