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
        Schema::table('users', function (Blueprint $table) {
            $table->float('rating', 5)->after('email')->default(60);
        });

        Schema::create('user_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluating_user_id');
            $table->unsignedBigInteger('assessed_user_id');
            $table->integer('value');
            $table->timestamps();
        });

        Schema::create('user_ratings_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluating_user_id');
            $table->unsignedBigInteger('assessed_user_id');
            $table->integer('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
