<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        $hasTicketTiersQuantity = Schema::hasColumn('ticket_tiers', 'quantity');
        $hasTicketTiersSalesStartDate = Schema::hasColumn('ticket_tiers', 'sales_start_date');
        $hasTicketTiersSalesEndDate = Schema::hasColumn('ticket_tiers', 'sales_end_date');
        Schema::table('ticket_tiers', function (Blueprint $table) use ($hasTicketTiersQuantity, $hasTicketTiersSalesStartDate, $hasTicketTiersSalesEndDate) {
            if (!$hasTicketTiersQuantity) {
                $table->unsignedInteger('quantity')->nullable(false)->after('price');
            }
            if (!$hasTicketTiersSalesStartDate) {
                $table->dateTime('sales_start_date')->nullable()->after('quantity');
            }
            if (!$hasTicketTiersSalesEndDate) {
                $table->dateTime('sales_end_date')->nullable()->after('sales_start_date');
            }
        });
    }

    public function down(): void
    {
        $hasTicketTiersQuantity = Schema::hasColumn('ticket_tiers', 'quantity');
        $hasTicketTiersSalesStartDate = Schema::hasColumn('ticket_tiers', 'sales_start_date');
        $hasTicketTiersSalesEndDate = Schema::hasColumn('ticket_tiers', 'sales_end_date');
        Schema::table('ticket_tiers', function (Blueprint $table) use ($hasTicketTiersQuantity, $hasTicketTiersSalesStartDate, $hasTicketTiersSalesEndDate) {
            $columns = [];
            if ($hasTicketTiersQuantity) {
                $columns[] = 'quantity';
            }
            if ($hasTicketTiersSalesStartDate) {
                $columns[] = 'sales_start_date';
            }
            if ($hasTicketTiersSalesEndDate) {
                $columns[] = 'sales_end_date';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
