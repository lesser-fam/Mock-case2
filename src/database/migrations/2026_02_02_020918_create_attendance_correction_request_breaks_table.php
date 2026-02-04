<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('attendance_correction_requests')->cascadeOnDelete();
            $table->dateTime('break_start_at');
            $table->dateTime('break_end_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
}
