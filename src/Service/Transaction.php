<?php

declare(strict_types=1);

namespace Paycommission\CommissionTask\Service;

class Transaction
{
    const USER_NATURAL = 'natural';
    const USER_LEGAL = 'legal';
    const CASH_IN_OPERATION = 'cash_in';
    const CASH_OUT_OPERATION = 'cash_out';
    const CASH_IN_FEE = 0.03;
    const CASH_OUT_FEE = 0.3;
    const DEFAULT_CURRENCY = 'EUR';
    const USD = 1.1497;
    const JPY = 129.53;

    private $file;
    private $completedOperation;

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->completedOperation = [];
    }

    public function calculateCommissionFee()
    {
        $data = $this->csvToArray();
        foreach ($data as $row) {
            $amount = $row['amount'];
            $currency = $row['currency'];
            $userType = $row['userType'];
            $user = $row['user'];
            $date = $row['date'];
            $operType = $row['operationType'];

            if ($currency !== self::DEFAULT_CURRENCY) {
                $amount = $this->convertToEuro($amount, $currency);
            }

            switch ($operType) {
                case self::CASH_IN_OPERATION:
                    $commisionFee = $this->cashIn($amount, $currency);
                    echo $commisionFee."\n";
                    break;
                case self::CASH_OUT_OPERATION:
                    if ($userType === self::USER_NATURAL) {
                        $commisionFee = $this->naturalCashOut($user, $userType, $amount, $currency, $date);
                        echo $commisionFee."\n";
                    } elseif ($userType === self::USER_LEGAL) {
                        $commisionFee = $this->legalCashOut($amount, $currency);
                        echo $commisionFee."\n";
                    }
                    break;
            }
        }
    }

    public function cashIn($amount, $currency): float
    {
        $fee = (self::CASH_IN_FEE * $amount) / 100;
        if ($fee > 5) {
            $fee = 5.00;
        }
        $fee = $this->convertCurrency($fee, $currency);

        return $fee;
    }

    public function legalCashOut($amount, $currency): float
    {
        $fee = self::CASH_OUT_FEE * $amount / 100;
        if ($fee < 0.5) {
            $fee = 0.5;
        }
        $fee = $this->convertCurrency($fee, $currency);

        return $fee;
    }

    public function naturalCashOut($user, $userType, $amount, $currency, $date): float
    {
        if (empty($this->completedOperation)) {
            list($fee, $freeAmount) = $this->calculateFee($amount, $currency);
            $this->insertCompletedOperation($user, 1, $date, $amount, $freeAmount);

            return $fee;
        } else {
            $key = array_search($user, array_column($this->completedOperation, 'user'), true);
            if ($key !== false) {
                $previousOperationDate = $this->completedOperation[$key]['date'];
                if ($this->isSameWeek($previousOperationDate, $date)) {
                    $allAmount = $this->completedOperation[$key]['freeAmount'] + $amount;
                    $nrOperation = ++$this->completedOperation[$key]['nrOperations'];
                    list($fee, $freeAmount) = $this->calculateFee($allAmount, $currency, $nrOperation);
                    $this->completedOperation[$key]['freeAmount'] = $freeAmount;
                    $this->completedOperation[$key]['date'] = $date;
                } else {
                    list($fee, $freeAmount) = $this->calculateFee($amount, $currency);
                    --$this->completedOperation[$key]['nrOperations'];
                }

                return $fee;
            } else {
                list($fee, $freeAmount) = $this->calculateFee($amount, $currency);
                $this->insertCompletedOperation($user, 1, $date, $amount, $freeAmount);

                return $fee;
            }
        }
    }

    public function calculateFee($amount, $currency, $nrOperations = 1): array
    {
        $diff = $amount - 1000;
        if ($diff <= 0 && $nrOperations <= 3) {
            $fee = 0;
            $freeAmount = $amount;
        } elseif ($diff <= 0 && $nrOperations > 3) {
            $fee = (self::CASH_OUT_FEE * $amount) / 100;
            $fee = $this->convertCurrency($fee, $currency);
            $freeAmount = 1000;
        } else {
            $fee = (self::CASH_OUT_FEE * $diff) / 100;
            $fee = $this->convertCurrency($fee, $currency);
            $freeAmount = $amount - $diff;
        }

        return [$fee, $freeAmount];
    }

    public function csvToArray(): array
    {
        $i = 0;
        if (($handle = fopen($this->file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $operation[$i]['date'] = $row[0];
                $operation[$i]['user'] = $row[1];
                $operation[$i]['userType'] = $row[2];
                $operation[$i]['operationType'] = $row[3];
                $operation[$i]['amount'] = $row[4];
                $operation[$i]['currency'] = $row[5];
                ++$i;
            }
            fclose($handle);
        }

        return $operation;
    }

    public function insertCompletedOperation($user, $nrOperations, $date, $amount, $freeAmount)
    {
        array_push(
            $this->completedOperation,
            [
                'user' => $user,
                'nrOperations' => $nrOperations,
                'date' => $date,
                'amount' => $amount,
                'freeAmount' => $freeAmount,
            ]);
    }

    public function convertCurrency(float $amount, string $currency): float
    {
        if ($currency === 'USD') {
            return $amount * self::USD;
        } elseif ($currency === 'JPY') {
            return $amount * self::JPY;
        } else {
            return $amount;
        }
    }

    public function convertToEuro(string $amount, string $currency): float
    {
        if ($currency === 'USD') {
            return $amount / self::USD;
        } elseif ($currency === 'JPY') {
            return $amount / self::JPY;
        }
    }

    public function isSameWeek(string $firstDate, string $secondDate): int
    {
        $firstDate = strtotime($firstDate);
        $secondDate = strtotime($secondDate);

        $weekOfFirstDate = date('oW', $firstDate);
        $weekOfSecondDate = date('oW', $secondDate);
        $yearOfFirstDate = date('Y', $firstDate);
        $yearOfSecondDate = date('Y', $secondDate);

        $firstDate = $yearOfFirstDate + $weekOfFirstDate;
        $secondDate = $yearOfSecondDate + $weekOfSecondDate;
        $result = $firstDate - $secondDate;

        return $result === 0 || $result === -1 ? 1 : 0;
    }
}
