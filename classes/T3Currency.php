<?php
class T3Currency {

    static public function convertCurrency($amount, $from, $to) {
        $url = "https://www.google.com/finance/converter?a={$amount}&from={$from}&to={$to}";
        $data = file_get_contents($url);
        if (!$data)
            return false;

        preg_match("/<span class=bld>(.*)<\/span>/", $data, $converted);
        if (!isset($converted[1]) || !$converted[1])
            return false;

        $converted = preg_replace("/[^0-9.]/", "", $converted[1]);
        return round($converted, 3);
    }

}

