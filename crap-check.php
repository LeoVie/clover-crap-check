<?php

declare(strict_types=1);

// Inspired by: https://github.com/richardregeer/phpunit-coverage-check

const XPATH_METHODS = "//line[@type='method']";
const STATUS_OK = 0;
const STATUS_ERROR = 1;

/** @return array<SimpleXMLElement> */
function loadLines(string $file): array
{
    $xml = new SimpleXMLElement(file_get_contents($file));

    return $xml->xpath(XPATH_METHODS);
}

function printStatus(string $msg, int $exitCode = STATUS_OK): never
{
    echo $msg . PHP_EOL;
    exit($exitCode);
}

if (!isset($argv[1]) || !file_exists($argv[1])) {
    printStatus("Invalid input file {$argv[1]} provided.", STATUS_ERROR);
}

if (!isset($argv[2])) {
    printStatus(
        'An integer crap threshold must be given as second parameter.',
        STATUS_ERROR
    );
}

$inputFile = $argv[1];
$maxCrapThreshold = max(1, $argv[2]);

$methodCrapsMapping = [];
foreach (loadLines($inputFile) as $method) {
    $class = (string)$method->xpath("..")[0]->class['name'];
    $methodName = (string)$method['name'];
    $crap = (int)$method['crap'];

    $methodCrapsMapping[$class . '::' . $methodName] = $crap;
}

$tooCrappyMethods = array_filter(
    $methodCrapsMapping,
    fn (int $crap): bool => $crap > $maxCrapThreshold
);

if (count($tooCrappyMethods) === 0) {
    printStatus('All methods are less crappy than threshold - OK!');
}

$message = '';
foreach ($tooCrappyMethods as $method => $crap) {
    $message .= sprintf("%s has CRAP index of %d. That's too high.%s", $method, $crap, PHP_EOL);
}
printStatus($message, STATUS_ERROR);
