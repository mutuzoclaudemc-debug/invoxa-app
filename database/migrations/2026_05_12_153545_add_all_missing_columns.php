<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add role to users if missing
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        }

        // Add all workspace columns if missing
        $workspaceColumns = [
            'plan_status' => function (Blueprint $table) {
                $table->string('plan_status')->default('active');
            },
            'plan_expires_at' => function (Blueprint $table) {
                $table->timestamp('plan_expires_at')->nullable();
            },
            'invoices_this_month' => function (Blueprint $table) {
                $table->integer('invoices_this_month')->default(0);
            },
            'last_invoice_month' => function (Blueprint $table) {
                $table->string('last_invoice_month')->nullable();
            },
            'logo_url' => function (Blueprint $table) {
                $table->string('logo_url')->nullable();
            },
            'company_email' => function (Blueprint $table) {
                $table->string('company_email')->nullable();
            },
            'company_phone' => function (Blueprint $table) {
                $table->string('company_phone')->nullable();
            },
            'company_address' => function (Blueprint $table) {
                $table->text('company_address')->nullable();
            },
            'tax_id' => function (Blueprint $table) {
                $table->string('tax_id')->nullable();
            },
            'website' => function (Blueprint $table) {
                $table->string('website')->nullable();
            },
            'invoice_footer' => function (Blueprint $table) {
                $table->text('invoice_footer')->nullable();
            },
            'brand_color' => function (Blueprint $table) {
                $table->string('brand_color')->default('#0b0d10');
            },
            'bank_name' => function (Blueprint $table) {
                $table->string('bank_name')->nullable();
            },
            'bank_account_number' => function (Blueprint $table) {
                $table->string('bank_account_number')->nullable();
            },
            'bank_account_name' => function (Blueprint $table) {
                $table->string('bank_account_name')->nullable();
            },
        ];

        foreach ($workspaceColumns as $column => $callback) {
            if (!Schema::hasColumn('workspaces', $column)) {
                Schema::table('workspaces', $callback);
            }
        }
    }

    public function down(): void
    {
        $columns = ['plan_status', 'plan_expires_at', 'invoices_this_month', 'last_invoice_month',
            'logo_url', 'company_email', 'company_phone', 'company_address', 'tax_id', 'website',
            'invoice_footer', 'brand_color', 'bank_name', 'bank_account_number', 'bank_account_name'];
        
        Schema::table('workspaces', function (Blueprint $table) use ($columns) {
            foreach ($columns as $col) {
                if (Schema::hasColumn('workspaces', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};