<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('towers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 80);
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['building_id', 'code']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tower_name', 50);
            $table->unsignedTinyInteger('floor_number');
            $table->string('unit_code', 20)->unique();
            $table->enum('occupancy_status', ['occupied', 'vacant'])->default('vacant');
            $table->string('qr_token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('facility_code', 50)->unique();
            $table->string('facility_name', 100);
            $table->enum('category', ['elevator', 'plumbing', 'electrical', 'public_area']);
            $table->string('tower_name', 50);
            $table->unsignedTinyInteger('floor_number')->nullable();
            $table->string('qr_token', 64)->unique();
            $table->string('qr_image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('qr_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tower_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->enum('type', ['unit', 'facility', 'asset', 'location'])->index();
            $table->string('tower_name', 50);
            $table->unsignedTinyInteger('floor_number')->nullable();
            $table->string('zone', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('reference_image_path')->nullable();
            $table->text('installation_notes')->nullable();
            $table->timestamp('last_inspected_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('priority_level', 20)->unique();
            $table->string('name', 80);
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->string('escalation_role', 50)->default('manager');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qr_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reporter_name', 120)->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('public_token', 64)->unique();
            $table->string('ticket_number', 30)->unique();
            $table->enum('ticket_type', ['unit', 'facility', 'asset', 'location']);
            $table->string('title', 140);
            $table->text('issue_description');
            $table->string('initial_photo_path')->nullable();
            $table->enum('priority_level', ['emergency', 'high', 'regular'])->default('regular');
            $table->enum('status', ['submitted', 'verified', 'in_progress', 'pending_verification', 'closed', 'rework'])->default('submitted');
            $table->timestamp('target_sla_at');
            $table->timestamps();
            $table->index(['status', 'target_sla_at']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('tro_user_id')->constrained('users');
            $table->foreignId('technician_user_id')->constrained('users');
            $table->text('dispatch_notes')->nullable();
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->text('technician_notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('kind', ['report', 'before', 'after', 'other'])->default('other');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('notes')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['ticket_id', 'changed_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('ticket_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['read_at', 'created_at']);
        });

        Schema::create('sla_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->enum('breach_status', ['compliant', 'breached']);
            $table->unsignedInteger('resolution_duration_minutes')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_target_role', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('resident_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating_stars');
            $table->text('review_comment')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('sla_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ticket_status_logs');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('qr_locations');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('units');
        Schema::dropIfExists('towers');
        Schema::dropIfExists('buildings');
    }
};
