<?php

namespace App\Modules\AccountsPayable\Application;

/**
 * Convert a numeric amount to words for check printing (e.g. "One thousand and 00/100").
 */
class AmountToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * Convert amount to words with fraction, e.g. "One thousand and 00/100" or "No and 00/100" for zero.
     */
    public static function forCheck(float $amount, string $fractionDenom = '100'): string
    {
        $amount = round($amount, 2);
        $intPart = (int) floor($amount);
        $fracPart = (int) round(($amount - $intPart) * 100);

        $words = $intPart === 0 ? 'Zero' : self::intToWords($intPart);
        $frac = sprintf('%02d', min(99, max(0, $fracPart)));

        return $words . ' and ' . $frac . '/' . $fractionDenom;
    }

    private static function intToWords(int $n): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n < 20) {
            return self::ONES[$n];
        }
        if ($n < 100) {
            $t = (int) floor($n / 10);
            $o = $n % 10;
            return self::TENS[$t] . ($o > 0 ? '-' . self::ONES[$o] : '');
        }
        if ($n < 1000) {
            $h = (int) floor($n / 100);
            $r = $n % 100;
            return self::ONES[$h] . ' Hundred' . ($r > 0 ? ' ' . self::intToWords($r) : '');
        }
        if ($n < 1_000_000) {
            $thou = (int) floor($n / 1000);
            $r = $n % 1000;
            return self::intToWords($thou) . ' Thousand' . ($r > 0 ? ' ' . self::intToWords($r) : '');
        }
        if ($n < 1_000_000_000) {
            $mil = (int) floor($n / 1_000_000);
            $r = $n % 1_000_000;
            return self::intToWords($mil) . ' Million' . ($r > 0 ? ' ' . self::intToWords($r) : '');
        }
        $bil = (int) floor($n / 1_000_000_000);
        $r = $n % 1_000_000_000;
        return self::intToWords($bil) . ' Billion' . ($r > 0 ? ' ' . self::intToWords($r) : '');
    }
}
