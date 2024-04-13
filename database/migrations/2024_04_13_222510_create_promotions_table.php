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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('promotion_title');
            $table->string('promotion_description');
            //Time of promotion
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            
            //type of promotion
            $table->string('promotion_type');
            //promotion status
            $table->string('promotion_status');
            //discount amount
            $table->string('discount_promotion');
            
            //relation with business
            $table->foreignId('business_id')->constrained('businesses')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};