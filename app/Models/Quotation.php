<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'customer_id', 'created_by_id',
        'quotation_number', 'status', 'issue_date', 'valid_until',
        'currency', 'notes', 'subtotal', 'tax_amount', 'total_amount'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }
}