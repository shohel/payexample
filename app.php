<?php

define('APP_FROM_ROOT', true);

if ( ! class_exists('CalculateCommissions')){
    include 'CalculateCommissions.php';
}

$calculate = new CalculateCommissions();
$calculate->loadTransactions()->calculate(); //Run The Application
