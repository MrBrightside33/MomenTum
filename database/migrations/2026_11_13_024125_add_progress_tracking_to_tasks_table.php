<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedTinyInteger('progress_before_complete')->nullable();
            $table->timestamp('progress_updated_at')->nullable();
            $table->foreignId('progress_updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('tasks')->where('status', 'Completed')->update(['progress' => 100]);
        DB::table('tasks')->where('status', 'In Progress')->update(['progress' => 25]);
        DB::table('tasks')->where('status', 'Open')->update(['progress' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('progress_updated_by_id');
            $table->dropConstrainedForeignId('completed_by_id');
            $table->dropColumn([
                'progress',
                'progress_before_complete',
                'progress_updated_at',
            ]);
        });
    }
};
