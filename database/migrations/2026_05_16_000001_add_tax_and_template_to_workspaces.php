<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $columns = [
            'tax_type'         => fn(Blueprint $t) => $t->string('tax_type')->default('on_total')->nullable(),
            'tax_rate'         => fn(Blueprint $t) => $t->decimal('tax_rate', 5, 2)->default(0)->nullable(),
            'tax_label'        => fn(Blueprint $t) => $t->string('tax_label')->default('Tax')->nullable(),
            'tax_inclusive'    => fn(Blueprint $t) => $t->boolean('tax_inclusive')->default(false),
            'invoice_template' => fn(Blueprint $t) => $t->string('invoice_template')->default('classic'),
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
            $table->dropColumn(['tax_type', 'tax_rate', 'tax_label', 'tax_inclusive', 'invoice_template']);
        });
    }
};
