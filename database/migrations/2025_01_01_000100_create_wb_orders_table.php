<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wb_orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->date('date');
            $table->string('status')->nullable();
            $table->json('payload');
            $table->unique(['external_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_orders');
    }
};