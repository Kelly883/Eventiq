<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email')->unique()->index();
                $table->string('passwordHash');
                $table->string('role')->default('attendee');
                $table->boolean('emailVerified')->default(false);
                $table->timestamps();
                $table->timestamp('lastLoginAt')->nullable();
            });
        } catch (QueryException $e) {
            // If table already exists, check if it has the primary key
            if ($e->getCode() === '42P07' || str_contains($e->getMessage(), 'already exists')) {
                $this->ensurePrimaryKey();
                return;
            }
            throw $e;
        }
    }

    private function ensurePrimaryKey(): void
    {
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("
            SELECT 1 FROM information_schema.table_constraints
            WHERE table_name = 'users' AND table_schema = 'public'
            AND constraint_type = 'PRIMARY KEY'
            LIMIT 1
        ");
        $hasPrimaryKey = $stmt && $stmt->fetch();

        if (!$hasPrimaryKey) {
            Schema::dropIfExists('users');
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email')->unique()->index();
                $table->string('passwordHash');
                $table->string('role')->default('attendee');
                $table->boolean('emailVerified')->default(false);
                $table->timestamps();
                $table->timestamp('lastLoginAt')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
