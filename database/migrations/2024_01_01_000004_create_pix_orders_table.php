<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pix_orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->decimal('transaction_amount', 8, 2);
            $table->string('payment_method')->default('pix');
            $table->timestamp('paid_at')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_document');
            $table->string('product_description');
            $table->string('mercado_pago_id')->nullable();
            $table->text('qr_code_base64')->nullable();
            $table->text('qr_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pix_orders');
    }
};
