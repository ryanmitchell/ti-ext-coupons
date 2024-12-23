<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('igniter_coupons', function(Blueprint $table) {
            $table->timestamp('date_added')->change();
        });

        Schema::table('igniter_coupons', function(Blueprint $table) {
            $table->renameColumn('date_added', 'created_at');
        });

        Schema::table('igniter_coupons', function(Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('igniter_coupons')->update([
            'updated_at' => DB::raw('created_at'),
        ]);

        Schema::table('igniter_coupons_history', function(Blueprint $table) {
            $table->timestamp('date_used')->change();
        });

        Schema::table('igniter_coupons_history', function(Blueprint $table) {
            $table->renameColumn('date_used', 'created_at');
        });

        Schema::table('igniter_coupons_history', function(Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down() {}
};
