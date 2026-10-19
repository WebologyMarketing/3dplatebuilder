<?php

if (! function_exists('money')) {
    /** £ from pence: money(9900) is "£99", money(3499) is "£34.99". */
    function money(?int $pence): string
    {
        $pence = (int) $pence;

        return '£'.($pence % 100 === 0 ? number_format($pence / 100) : number_format($pence / 100, 2));
    }
}
