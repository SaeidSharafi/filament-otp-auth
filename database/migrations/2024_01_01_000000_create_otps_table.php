<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $tableName = config('filament-otp-auth.otp_table', 'otps');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index(); // Stores email or phone
            $table->string('code');
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $tableName = config('filament-otp-auth.otp_table', 'otps');
        Schema::dropIfExists($tableName);
    }
};
