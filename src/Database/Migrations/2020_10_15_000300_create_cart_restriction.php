<?php

namespace Igniter\Coupons\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('igniter_coupons', function (Blueprint $table) {
            $table->boolean('is_limited_to_cart_item')->default(false);
        });
    }
};