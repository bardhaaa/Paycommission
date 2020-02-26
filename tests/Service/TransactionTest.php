<?php

declare(strict_types=1);

namespace Paycommission\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Paycommission\CommissionTask\Service\Transaction;

class TransactionTest extends TestCase
{
    /**
     * @var Transaction
     */
    private $transaction;
    private $completedOperation;
    private $file;

    public function setUp()
    {
        $this->file = '/Applications/MAMP/htdocs/detyra/test.csv';
        $this->transaction = new Transaction($this->file);
        $this->completedOperation = [];
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param string $expectation
     *
     * @dataProvider dataProviderForConvertCurrencyTesting
     */
    public function testConvertCurrency(float $amount, string $currency, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->convertCurrency($amount, $currency)
        );
    }

    public function dataProviderForConvertCurrencyTesting(): array
    {
        return [
            'convert EUR to USD' => [100.00, 'USD', '114.97'],
            'convert EUR to JPY' => [40000, 'JPY', '5181200.0']
        ];
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param string $expectation
     *
     * @dataProvider dataProviderForConvertToEuroTesting
     */
    public function testConvertToEuro(string $amount, string $currency, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->convertToEuro($amount, $currency)
        );
    }

    public function dataProviderForConvertToEuroTesting(): array
    {
        return [
            'convert USD to EUR' => [100.00, 'USD', '86.97921196833957'],
            'convert JPY to EUR' => [40000, 'JPY', '308.8087701690728']
        ];
    }

    /**
     * @param string $expectation
     *
     * @dataProvider dataProviderForCsvToArray
     */
    public function testCsvToArray($expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->csvToArray()
        );
    }

    public function dataProviderForCsvToArray(): array
    {
        return [
            'read from the csv file' => [
                [
                    [
                        "date"              => "2014-12-31",
                        "user"              => "4",
                        "userType"         => "natural",
                        "operationType"    => "cash_out",
                        "amount"            => "1200.00",
                        "currency"          => "EUR"
                    ],
                    [
                        "date"              => "2015-01-01",
                        "user"              => "4",
                        "userType"         => "natural",
                        "operationType"    => "cash_out",
                        "amount"            => "1000.00",
                        "currency"          => "EUR"
                    ],
                    [
                        "date"              => "2016-01-05",
                        "user"              => "4",
                        "userType"         => "natural",
                        "operationType"    => "cash_out",
                        "amount"            => "1000.00",
                        "currency"          => "EUR"
                    ]
                ]
            ],
        ];
    }

    /**
     * @param string $expectation
     *
     * @dataProvider dataProviderCalculateCommissionFee
     */
    public function testCalculateCommissionFee(string $expectation)
    {
        $this->expectOutputString($expectation);
        $this->transaction->calculateCommissionFee();
    }

    public function dataProviderCalculateCommissionFee(): array
    {
        return [
            // use this commented line if you are providing the same input.csv file in the tests
            // 'calculate commission fee for users read from csv' => ["0.6\n3\n0\n0.06\n0.9\n0\n0.69481973288041\n0.3\n0.3\n5\n0\n0\n8611.41\n"],
            'calculate commission fee for users read from csv' => ["0.6\n3\n0\n"],
        ];
    }

    /**
     * @param string $user
     * @param string $userType
     * @param string $amount
     * @param string $currency
     * @param string $date
     * @param string $expectation
     *
     * @dataProvider dataProviderForNaturalCashOut
     */
    public function testNaturalCashOut($user, $userType, $amount, $currency, $date, $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->naturalCashOut($user, $userType, $amount, $currency, $date)
        );
    }

    public function dataProviderForNaturalCashOut(): array
    {
        return [
            'calculate commission fee' => ['4', 'natural', '1200.00', 'EUR', '2014-12-31', '0.60'],
        ];
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param string $nrOperations
     * @param array $expectation
     *
     * @dataProvider dataProviderForCalculateFee
     */
    public function testCalculateFee($amount, $currency, $nrOperations, array $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->calculateFee($amount, $currency, $nrOperations)
        );
    }

    public function dataProviderForCalculateFee(): array
    {
        return [
            'calculate commission fee' => ['100.00', 'EUR', '4', ['0.3', '1000']]
        ];
    }

    /**
     * @param string $firstDate
     * @param string $secondDate
     * @param int $expectation
     *
     * @dataProvider dataProviderForIsSameWeek
     */
    public function testIsSameWeek(string $firstDate, string $secondDate, $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->isSameWeek($firstDate, $secondDate)
        );
    }

    public function dataProviderForIsSameWeek(): array
    {
        return [
            'check if these two dates are on the same week' => ['2014-12-31', '2015-01-01', 1]
        ];
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param int $expectation
     *
     * @dataProvider dataProviderForCashIn
     */
    public function testCashIn(string $amount, string $currency, $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->cashIn($amount, $currency)
        );
    }

    public function dataProviderForCashIn(): array
    {
        return [
            'calculate fee for cash in operation' => ['200.00', 'EUR', 0.06]
        ];
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param int $expectation
     *
     * @dataProvider dataProviderForLegalCashOut
     */
    public function testLegalCashOut(string $amount, string $currency, $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->transaction->legalCashOut($amount, $currency)
        );
    }

    public function dataProviderForLegalCashOut(): array
    {
        return [
            'calculate fee for legal cash out operation' => ['300.00', 'EUR', 0.90]
        ];
    }

    /**
     * @param string $user
     * @param string $nrOperations
     * @param string $date
     * @param string $amount
     * @param string $freeAmount
     * @param int $expectation
     *
     * @dataProvider dataProviderForInsertOperation
     */
    public function testInsertOperation($user, $nrOperations, $date, $amount, $freeAmount, $expectation)
    {
        $this->transaction->insertCompletedOperation($user, $nrOperations, $date, $amount, $freeAmount);
        $key = array_search($user, array_column($this->completedOperation, 'user'));
            
        $this->assertEquals(
            $expectation,
            $key
        );
    }

    public function dataProviderForInsertOperation(): array
    {
        return [
            'insert new row in the completed operation array' => ['1', '1', '2016-01-05', '200.00', 'EUR', 0]
        ];
    }
}
