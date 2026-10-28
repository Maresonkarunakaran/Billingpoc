<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InvoiceSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Get Indian Financial Year string (e.g. 2026-27 for Oct 2026)
     */
    public static function getFinancialYear(?Carbon $date = null): string
    {
        $date = $date ?: now();
        $year = $date->year;
        // In India, FY starts April 1st
        if ($date->month < 4) {
            $startYear = $year - 1;
            $endYear = $year;
        } else {
            $startYear = $year;
            $endYear = $year + 1;
        }

        return $startYear.'-'.substr((string) $endYear, -2);
    }

    /**
     * Generate the next sequential reference number with row-level database locking
     */
    public function getNextNumber(
        int $businessId,
        int $branchId,
        string $seriesType,
        ?string $customPrefix = null,
        ?Carbon $date = null
    ): string {
        $fy = self::getFinancialYear($date);

        $defaultPrefixes = [
            'sale' => 'INV',
            'sale_return' => 'CN', // Credit Note
            'purchase' => 'PO',
            'purchase_return' => 'DN', // Debit Note
            'transfer' => 'TRF',
            'expense' => 'EXP',
            'payment' => 'RCPT',
        ];

        $prefix = $customPrefix ?: ($defaultPrefixes[$seriesType] ?? strtoupper(substr($seriesType, 0, 3)));
        $branch = Branch::find($branchId);
        $branchCode = $branch ? $branch->code : 'BR';

        return DB::transaction(function () use ($businessId, $branchId, $seriesType, $prefix, $branchCode, $fy) {
            $sequence = InvoiceSequence::where('branch_id', $branchId)
                ->where('series_type', $seriesType)
                ->where('financial_year', $fy)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = InvoiceSequence::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'series_type' => $seriesType,
                    'prefix' => $prefix,
                    'financial_year' => $fy,
                    'last_sequence' => 0,
                ]);
            }

            $sequence->last_sequence += 1;
            $sequence->save();

            $padLength = 5;
            $num = str_pad((string) $sequence->last_sequence, $padLength, '0', STR_PAD_LEFT);

            return "{$branchCode}/{$prefix}/{$fy}/{$num}";
        });
    }
}
