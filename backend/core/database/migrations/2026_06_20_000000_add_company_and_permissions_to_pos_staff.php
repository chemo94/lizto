<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pos_staff', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_staff', 'seller_company_id')) {
                $table->foreignId('seller_company_id')->nullable()->constrained('seller_companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_staff', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('pos_staff', 'permissions')) {
                $table->text('permissions')->nullable()->after('password');
            }
        });
    }

    public function down()
    {
        Schema::table('pos_staff', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_company_id');
            $table->dropColumn(['password', 'permissions']);
        });
    }
};
