<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_general_category', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('general_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['store_id', 'general_category_id']);
        });

        Schema::create('store_sub_category', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['store_id', 'sub_category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_sub_category');
        Schema::dropIfExists('store_general_category');
    }
};
