<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'email',
        'phone',
        'billing_address',
        'company_name',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}