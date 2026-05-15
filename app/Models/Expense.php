<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'created_by_id', 'title', 'category',
        'amount', 'currency', 'expense_date', 'description',
        'receipt_url', 'vendor', 'status',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    const CATEGORIES = [
        'general', 'office', 'travel', 'meals', 'utilities',
        'software', 'marketing', 'salaries', 'rent', 'equipment', 'other',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
