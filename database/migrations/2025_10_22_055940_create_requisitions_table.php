<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('requested_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('department_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('section_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('church_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_requested', 10, 2);
            $table->string('category');
            $table->text('purpose');
            $table->date('date_needed');
            $table->enum('status', [
                'Pending',
                'Approved by Dept. Head',
                'Approved by Section President',
                'Awaiting Receipt',
                'Pending Finance Verification',
                'Completed',
                'Rejected',
                'Changes Requested',
                'Receipt Correction Requested'
            ])->default('Pending');
            $table->json('attachments')->nullable();
            $table->json('final_receipt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
