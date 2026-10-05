<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('shipping_area')->nullable();
        });

        // Customers now register into the same table; only the existing staff account stays an admin.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
        DB::table('users')->where('email', 'admin@lilycrown.com')->update(['is_admin' => true]);

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['is_admin', 'phone', 'address', 'city', 'shipping_area']);
        });
    }
};
