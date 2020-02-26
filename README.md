#How to run the program 
    - first you should run the command `composer install`
    - than run `cd src/Service`
    - for the system to run, it needs the path of the csv file that contains the data
    - after you are in the Service directory you should run `php script.php {csv_path}` to initiate the system
    - in the directory src/Service there is the file input.csv, to run the system with those data run `php script.php input.csv`

#How to initiate system's tests
    - to run the tests the system needs a path of the csv file, it can be changed in the function setUp of TransactionTest
    - for testing the file test.csv is used, and the data provided for the testing are depended from this file, If you change the file than the data in the providers dataProviderForCsvToArray and dataProviderCalculateCommissionFee should change too
    - than run the command `composer run test`
