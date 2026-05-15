<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('general');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('RWF');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('vendor')->nullable();
            $table->string('status')->default('recorded'); // recorded, reimbursed
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
