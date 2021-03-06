# SilverStripe Gearman module

Adds a basic level of support for the gearmand php job queue

## Installation

* Install gearmand 
* Change its config to use a persistent queue; something like
```
  PARAMS="--listen=127.0.0.1 -q libsqlite3 --libsqlite3-db /var/run/gearmandb"
```
* This uses the Net\_Gearman library from publero/net\_gearman for 
  communicating with gearman; composer should manage this for you

To test the installation is correct, there's a test job you can execute

* Open two terminal windows in the SS root directory
* In the first, run php gearman/gearman\_runner.php
* In the second, run php gearman/gearman\_test\_client.php
* You should see some information output to the first console window,
  indicating the job was picked up and processed as expected. 

## Usage

* Define a class that implements GearmanHandler
* The 'getName' method should return the name of a method on the class that
  will handle the processing of the job (exampleMethod)
* Start a worker by calling php gearman/gearman\_runner.php
* Trigger the job by calling `$this->gearmanService->exampleMethod();`
* Any params passed through to exampleMethod are passed on to the worker
* Note: This only supports 'background' jobs at the moment, so there are NO
  return values

## Reflected worker

The 'gearman\_runner' script is bound to a single instance of SilverStripe; 
this means that any job spawned against the gearman server is handled and 
executed by that instance of SilverStripe. In situations where you have 
multiple instances of SS running, you will need to use the 
`reflected_runner.php` script instead of gearman\_runner.php. This handler will
include the path of the SS instance that triggered the gearman job to be 
executed, and will spawn a separate process to  execute that gearman job within

 

