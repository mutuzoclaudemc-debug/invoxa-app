<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'workspace_id', 'invoice_id', 'amount', 'currency',
        'payment_date', 'method', 'reference', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
