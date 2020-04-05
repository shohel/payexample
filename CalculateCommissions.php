<?php

if ( ! defined('APP_FROM_ROOT')){
    exit();
}

class CalculateCommissions{
    /**
     * @var $transactions
     *
     * It will hold all transaction, either from a text file or From DB.
     */
    private $transactions;

    protected $exchange_rates_api = 'https://api.exchangeratesapi.io/latest';
    protected $binlist_base_api = 'https://lookup.binlist.net/';

    /**
     * @var false|string
     *
     * It will contain latest exchange rates
     * We will fetch current rate once, not required to run it within the Loop
     */

    private $exchange_rates;

    public function __construct(){
        $exchange_rates = file_get_contents($this->exchange_rates_api);
        $this->exchange_rates = @json_decode($exchange_rates, true);
    }

    public function loadTransactions(){
        global $argv;

        if ( ! empty($argv[1])){
            $input_results = file_get_contents($argv[1]);
            $this->transactions = explode("\n", $input_results);
        }

        return $this;
    }


    public function calculate(){
        if ( ! $this->isArray($this->transactions)) {
            die('No transactions');
        }

        foreach ($this->transactions as $item) {
            if ( empty($item)){
                continue;
            }

            $transaction = json_decode($item);
            $binResults = file_get_contents( $this->binlist_base_api.$transaction->bin);

            if (empty($binResults)){
                die('error');
            }

            $r = json_decode($binResults);
            $isEu = $this->isEu($r->country->alpha2);

            $rate = ($transaction->currency == 'EUR') ? 0 : $this->exchange_rates['rates'][$transaction->currency];

            if ($transaction->currency == 'EUR' or $rate == 0) {
                $amntFixed = $transaction->amount;
            }
            if ($transaction->currency != 'EUR' or $rate > 0) {
                $amntFixed = $transaction->amount / $rate;
            }

            $commission_rate = $amntFixed * ($isEu == 'yes' ? 0.01 : 0.02);
            echo $this->ceil_by_cents($commission_rate, 2);
            print "\n";
        }

    }


    /**
     * @param $res
     * @return bool
     *
     * Determine if a variable containing array,
     * supports for php @7.1 count issue on array
     */

    public function isArray($res){
        if (is_array($res) && count($res)){
            return true;
        }
        return false;
    }

    /**
     * @param $c
     * @return string
     *
     * Determine if EU by country alpha 2 code
     */
    public function isEu($c) {
        $result = 'no';

        switch($c) {
            case 'AT':
            case 'BE':
            case 'BG':
            case 'CY':
            case 'CZ':
            case 'DE':
            case 'DK':
            case 'EE':
            case 'ES':
            case 'FI':
            case 'FR':
            case 'GR':
            case 'HR':
            case 'HU':
            case 'IE':
            case 'IT':
            case 'LT':
            case 'LU':
            case 'LV':
            case 'MT':
            case 'NL':
            case 'PO':
            case 'PT':
            case 'RO':
            case 'SE':
            case 'SI':
            case 'SK':
                $result = 'yes';
        }

        return $result;
    }


    function ceil_by_cents ($value, $places=0) {
        if ($places < 0) { $places = 0; }
        $mult = pow(10, $places);
        return ceil($value * $mult) / $mult;
    }


}