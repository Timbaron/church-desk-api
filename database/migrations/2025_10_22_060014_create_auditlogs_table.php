<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('requisition_id')->constrained()->onDelete('cascade')->unique();
            $table->decimal('amount_paid', 10, 2);
            $table->enum('payment_method', ['Bank Transfer', 'Cash', 'Cheque']);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->json('proof_file')->nullable();
            $table->foreignId('recorded_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
