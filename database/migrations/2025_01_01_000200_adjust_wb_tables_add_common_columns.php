<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add common optional columns to all tables used by the importer
        $tables = ['wb_orders', 'wb_sales', 'wb_stocks', 'wb_incomes'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'amount')) {
                    $table->decimal('amount', 14, 2)->nullable()->after('date');
                }
                if (!Schema::hasColumn($tableName, 'qty')) {
                    $table->integer('qty')->nullable()->after('amount');
                }
                if (!Schema::hasColumn($tableName, 'status')) {
                    $table->string('status')->nullable()->after('qty');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['wb_orders', 'wb_sales', 'wb_stocks', 'wb_incomes'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn($tableName, 'qty')) {
                    $table->dropColumn('qty');
                }
                if (Schema::hasColumn($tableName, 'amount')) {
                    $table->dropColumn('amount');
                }
            });
        }
    }
};