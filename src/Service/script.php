<?php

declare(strict_types=1);

include_once 'Transaction.php';

use Paycommission\CommissionTask\Service\Transaction;

if (isset($argc) && count($argv) > 1) {
    $file = $argv[1];
    // $file = '/Applications/MAMP/htdocs/detyra/input.csv';
    $transaction = new Transaction($file);
    $transaction->calculateCommissionFee();
} else {
    echo 'Please provide a csv file';
}
