<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PixOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'transaction_amount',
        'payment_method',
        'paid_at',
        'customer_name',
        'customer_email',
        'customer_document',
        'product_description',
        'mercado_pago_id',
        'qr_code_base64',
        'qr_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
