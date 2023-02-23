#!/usr/bin/env php
<?php

declare(strict_types=1);

use Leovie\PhpunitCrapCheck\Command\CloverCrapCheckCommand;
use Leovie\PhpunitCrapCheck\Generator\BaselineOutputGenerator;
use Leovie\PhpunitCrapCheck\Parser\BaselineParser;
use Leovie\PhpunitCrapCheck\Parser\CloverParser;
use Leovie\PhpunitCrapCheck\Service\BaselineCompareService;
use Leovie\PhpunitCrapCheck\Service\BaselineOutputService;
use Leovie\PhpunitCrapCheck\Service\CrapCheckService;
use Symfony\Component\Console\Application;

if (in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) === false) {
    echo PHP_EOL . 'clover-crap-check may only be invoked from a command line, got "' . PHP_SAPI . '"' . PHP_EOL;

    exit(1);
}

(static function (): void {
    if (file_exists($autoload = __DIR__ . '/../../autoload.php')) {
        // Is installed via Composer
        include_once $autoload;

        return;
    }

    if (file_exists($autoload = __DIR__ . '/vendor/autoload.php')) {
        // Is installed locally
        include_once $autoload;

        return;
    }

    fwrite(
        STDERR,
        <<<'ERROR'
You need to set up the project dependencies using Composer:
    $ composer install
See https://getcomposer.org/.
ERROR
    );

    throw new RuntimeException('Unable to find the Composer autoloader.');
})();

// Project (third-party) autoloading
(static function (): void {
    if (file_exists($autoload = getcwd() . '/vendor/autoload.php')) {
        include_once $autoload;
    }
})();

$application = new Application('clover-crap-check');
$command = new CloverCrapCheckCommand(
    new CrapCheckService(new CloverParser()),
    new BaselineCompareService(),
    new BaselineOutputService(new BaselineOutputGenerator()),
    new BaselineParser()
);

$application->add($command);

/** @var string $commandName */
$commandName = $command->getName();
$application->setDefaultCommand($commandName, true);
$application->run();