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
        // Check table state using a fresh PDO connection (outside Laravel's transaction)
        $config = DB::connection()->getConfig();
        $driver = $config['driver'] ?? 'sqlite';
        
        // For SQLite, use Laravel's Schema facade directly since we can't easily
        // create a separate PDO connection outside the transaction
        if ($driver === 'sqlite') {
            if (Schema::hasTable('users')) {
                // Check if table has primary key
                $hasPrimaryKey = DB::connection()->getSchemaBuilder()
                    ->getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableDetails('users')
                    ->getPrimaryKey() !== null;
                
                if ($hasPrimaryKey) {
                    return; // Already migrated
                }
                
                // Table exists but incomplete - drop and recreate
                Schema::dropIfExists('users');
            }
            
            // Create the table fresh
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
            return;
        }
        
        // Handle DATABASE_URL format (used in production on Render)
        if (!empty($config['url'])) {
            $dsn = $config['url'];
            // Convert postgres:// to pgsql: for PDO
            if (str_starts_with($dsn, 'postgres://') || str_starts_with($dsn, 'postgresql://')) {
                $dsn = 'pgsql:' . substr($dsn, strpos($dsn, '://') + 3);
            }
            $pdo = new \PDO($dsn, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } else {
            // Ensure all required config keys exist
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 5432;
            $database = $config['database'] ?? 'eventiq';
            $username = $config['username'] ?? 'eventiq';
            $password = $config['password'] ?? '';
            
            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        }

        // Check if users table exists and has primary key
        $stmt = $pdo->query("
            SELECT 1 FROM information_schema.table_constraints
            WHERE table_name = 'users' AND table_schema = 'public'
            AND constraint_type = 'PRIMARY KEY'
            LIMIT 1
        ");
        $hasPrimaryKey = $stmt && $stmt->fetch();

        if ($hasPrimaryKey) {
            // Table exists with primary key - migration already applied
            return;
        }

        // Check if table exists at all (but may be incomplete)
        $stmt = $pdo->query("
            SELECT 1 FROM information_schema.tables
            WHERE table_name = 'users' AND table_schema = 'public'
            LIMIT 1
        ");
        $tableExists = $stmt && $stmt->fetch();

        if ($tableExists) {
            // Table exists but is incomplete - drop it
            Schema::dropIfExists('users');
        }

        // Create the table fresh
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

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
