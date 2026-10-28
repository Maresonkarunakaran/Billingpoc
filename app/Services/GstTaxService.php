<?php

namespace App\Services;

class GstTaxService
{
    /**
     * Determine if transaction is inter-state supply
     */
    public function isInterstate(string $branchStateCode, ?string $customerStateCode): bool
    {
        if (empty($customerStateCode)) {
            return false; // Walk-in / intra-state supply by default
        }

        return trim($branchStateCode) !== trim($customerStateCode);
    }

    /**
     * Calculate item tax breakdown
     *
     * @param  float  $gstRate  e.g. 18.00
     * @param  string  $taxType  'exclusive' or 'inclusive'
     */
    public function calculateItemTaxes(
        float $unitPrice,
        float $quantity,
        float $itemDiscount,
        float $gstRate,
        string $taxType,
        bool $isInterstate
    ): array {
        $lineGross = (float) bcmul((string) $unitPrice, (string) $quantity, 4);
        $netLine = max(0.0, (float) bcsub((string) $lineGross, (string) $itemDiscount, 4));

        if ($gstRate <= 0.0) {
            return [
                'taxable_amount' => round($netLine, 2),
                'cgst_rate' => 0.0,
                'cgst_amount' => 0.0,
                'sgst_rate' => 0.0,
                'sgst_amount' => 0.0,
                'igst_rate' => 0.0,
                'igst_amount' => 0.0,
                'tax_amount' => 0.0,
                'total_amount' => round($netLine, 2),
            ];
        }

        if ($taxType === 'inclusive') {
            // Formula: Taxable = NetLine / (1 + (gstRate / 100))
            $taxMultiplier = bcadd('1', bcdiv((string) $gstRate, '100', 6), 6);
            $taxable = (float) bcdiv((string) $netLine, (string) $taxMultiplier, 4);
            $taxAmount = (float) bcsub((string) $netLine, (string) $taxable, 4);
            $totalAmount = $netLine;
        } else {
            // Formula: Tax = NetLine * (gstRate / 100)
            $taxable = $netLine;
            $taxAmount = (float) bcmul((string) $taxable, bcdiv((string) $gstRate, '100', 6), 4);
            $totalAmount = (float) bcadd((string) $taxable, (string) $taxAmount, 4);
        }

        if ($isInterstate) {
            $cgstRate = 0.0;
            $cgstAmount = 0.0;
            $sgstRate = 0.0;
            $sgstAmount = 0.0;
            $igstRate = $gstRate;
            $igstAmount = round($taxAmount, 2);
        } else {
            $halfRate = round($gstRate / 2, 2);
            $halfTax = round($taxAmount / 2, 2);

            $cgstRate = $halfRate;
            $cgstAmount = $halfTax;
            $sgstRate = $halfRate;
            $sgstAmount = $halfTax;
            $igstRate = 0.0;
            $igstAmount = 0.0;

            // Balance total tax to avoid penny rounding split
            $taxAmount = $cgstAmount + $sgstAmount;
        }

        return [
            'taxable_amount' => round($taxable, 2),
            'cgst_rate' => $cgstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_rate' => $sgstRate,
            'sgst_amount' => $sgstAmount,
            'igst_rate' => $igstRate,
            'igst_amount' => $igstAmount,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($taxable + $taxAmount, 2),
        ];
    }

    /**
     * Calculate Indian Rupee round-off
     * E.g. 102.40 => final: 102.00, round_off: -0.40
     *      102.60 => final: 103.00, round_off: +0.40
     */
    public function calculateRoundOff(float $amount): array
    {
        $rounded = round($amount);
        $diff = round($rounded - $amount, 2);

        return [
            'round_off' => $diff,
            'final_amount' => $rounded,
        ];
    }
}
