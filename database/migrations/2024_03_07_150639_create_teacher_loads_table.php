<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_loads', function (Blueprint $table) {
            $table->id();
            $table->integer('hours_allocated');
            $table->unsignedBigInteger('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('users');

            $table->unsignedBigInteger('study_year_id');
            $table->foreign('study_year_id')->references('id')->on('study_years')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('half_year');
            $table->unsignedBigInteger('department_load_id');
            $table->foreign('department_load_id')->references('id')->on('department_loads')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('type_id')->nullable();
            $table->foreign('type_id')->references('id')->on('types')->onDelete('set null')->onUpdate('cascade');

            $table->unsignedBigInteger('subtype_id')->nullable();
            $table->foreign('subtype_id')->references('id')->on('subtypes')->onDelete('set null')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_loads');
    }
};
