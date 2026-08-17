<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('media_type', 10)->default('image');
            $table->string('media_path');
            $table->string('caption')->nullable();
            $table->integer('duration')->default(5);
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('cpi', 12, 6)->default(0.005);
            $table->integer('total_impressions')->default(0);
            $table->integer('consumed_impressions')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'paused', 'completed'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['store_story_id', 'user_id']);
            $table->index(['store_story_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('store_stories');
    }
};
