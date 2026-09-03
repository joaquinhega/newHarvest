<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_receipts', function (Blueprint $table) {
            // Total "NO REM" (no remunerativo). Convive con gross_amount (remunerativo)
            // y deductions_amount. net_amount = gross_amount + non_remunerative_amount - deductions_amount.
            $table->decimal('non_remunerative_amount', 15, 2)->default(0.00)->after('gross_amount');
        });
    }

    public function down(): void
    {
        Schema::table('salary_receipts', function (Blueprint $table) {
            $table->dropColumn('non_remunerative_amount');
        });
    }
};
