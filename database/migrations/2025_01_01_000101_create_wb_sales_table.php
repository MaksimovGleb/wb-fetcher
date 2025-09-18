<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wb_sales', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->date('date');
            $table->decimal('amount', 14, 2)->nullable();
            $table->json('payload');
            $table->unique(['external_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_sales');
    }
};