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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name'); // Original filename
            $table->string('file_path'); // Path where file is stored
            $table->string('status')->default('queued'); // queued, processing, completed, error
            $table->integer('progress')->default(0); // Progress percentage (0-100)
            $table->text('error_message')->nullable(); // Error message if any
            $table->unsignedBigInteger('user_id')->nullable(); // User who uploaded the file
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
