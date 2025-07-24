<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('country_code', 2);
            $table->decimal('price', 8, 2);

            $table->timestamps();

            // FK to countries.code (not ID, since country_code is varchar)
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('cascade');

            $table->unique(['product_id', 'country_code']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
