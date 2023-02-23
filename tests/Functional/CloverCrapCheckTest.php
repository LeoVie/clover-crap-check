<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Functional;

use Leovie\PhpunitCrapCheck\Command\CloverCrapCheckCommand;
use Leovie\PhpunitCrapCheck\Generator\BaselineOutputGenerator;
use Leovie\PhpunitCrapCheck\Parser\BaselineParser;
use Leovie\PhpunitCrapCheck\Parser\CloverParser;
use Leovie\PhpunitCrapCheck\Service\BaselineCompareService;
use Leovie\PhpunitCrapCheck\Service\BaselineOutputService;
use Leovie\PhpunitCrapCheck\Service\CrapCheckService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class CloverCrapCheckTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->deleteExistingBaselineFile();

        $this->commandTester = new CommandTester(new CloverCrapCheckCommand(
            new CrapCheckService(new CloverParser()),
            new BaselineCompareService(),
            new BaselineOutputService(new BaselineOutputGenerator()),
            new BaselineParser()
        ));
    }

    protected function tearDown(): void
    {
        $this->deleteExistingBaselineFile();
    }

    private function deleteExistingBaselineFile(): void
    {
        if (file_exists(__DIR__ . '/../_testdata/generated/baseline.json')) {
            unlink(__DIR__ . '/../_testdata/generated/baseline.json');
        }
    }

    /** @dataProvider commandFailsWithoutRequiredArgumentsProvider */
    public function testCommandFailsWithoutRequiredArguments(array $inputs): void
    {
        self::expectException(RuntimeException::class);

        $this->commandTester->execute($inputs);
    }

    public static function commandFailsWithoutRequiredArgumentsProvider(): array
    {
        return [
            'no clover report path and crap threshold' => [
                'inputs' => []
            ],
            'no clover report path' => [
                'inputs' => [
                    'crap-threshold' => 10,
                ]
            ],
            'no crap threshold' => [
                'inputs' => [
                    'clover-report-path' => 'var/foo/clover.xml',
                ]
            ],
        ];
    }

    /** @dataProvider commandFailsWithIllegalInputsProvider */
    public function testCommandFailsWithIllegalInputs(string $expectedError, array $inputs): void
    {
        $this->commandTester->execute($inputs);

        self::assertSame(2, $this->commandTester->getStatusCode());
        self::assertStringContainsString($expectedError, $this->commandTester->getDisplay(true));
    }

    public static function commandFailsWithIllegalInputsProvider(): array
    {
        return [
            'clover report path does not exist' => [
                'expectedError' => 'Clover report could not be found at "/does/not/exist/clover.xml".',
                'inputs' => [
                    'clover-report-path' => '/does/not/exist/clover.xml',
                    'crap-threshold' => 10,
                ]
            ],
            'crap threshold not an int' => [
                'expectedError' => 'crap-threshold is not an integer.',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 'abc',
                ]
            ],
            'baseline file does not exist' => [
                'expectedError' => 'Baseline could not be found at "/does/not/exist/baseline.json"',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 10,
                    '--baseline' => '/does/not/exist/baseline.json',
                ]
            ],
            'both baseline and generate baseline passed' => [
                'expectedError' => 'Only use baseline or generate baseline, not both.',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 10,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                    '--generate-baseline' => __DIR__ . '/../_testdata/generated/baseline.json'
                ]
            ],
        ];
    }

    /** @dataProvider generateBaselineProvider */
    public function testGenerateBaseline(string $expectedBaselinePath, string $expectedBaseline, array $inputs): void
    {
        $this->commandTester->execute($inputs);

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertFileExists($expectedBaselinePath);
        self::assertJsonStringEqualsJsonString($expectedBaseline, \Safe\file_get_contents($expectedBaselinePath));
    }

    public static function generateBaselineProvider(): array
    {
        return [
            'empty clover report' => [
                'expectedBaselinePath' => __DIR__ . '/../_testdata/generated/baseline.json',
                'expectedBaseline' => json_encode([]),
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover_empty.xml',
                    'crap-threshold' => 3,
                    '--generate-baseline' => __DIR__ . '/../_testdata/generated/baseline.json'
                ],
            ],
            'no too crappy methods' => [
                'expectedBaselinePath' => __DIR__ . '/../_testdata/generated/baseline.json',
                'expectedBaseline' => json_encode([]),
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 10,
                    '--generate-baseline' => __DIR__ . '/../_testdata/generated/baseline.json'
                ],
            ],
            'too crappy methods' => [
                'expectedBaselinePath' => __DIR__ . '/../_testdata/generated/baseline.json',
                'expectedBaseline' => json_encode([
                    [
                        'classFQN' => 'ClassA',
                        'name' => 'm1',
                        'crap' => 10,
                    ]
                ]),
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 5,
                    '--generate-baseline' => __DIR__ . '/../_testdata/generated/baseline.json'
                ],
            ],
            'too crappy methods, relative path' => [
                'expectedBaselinePath' => __DIR__ . '/../_testdata/generated/baseline.json',
                'expectedBaseline' => json_encode([
                    [
                        'classFQN' => 'ClassA',
                        'name' => 'm1',
                        'crap' => 10,
                    ]
                ]),
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 5,
                    '--generate-baseline' => \Safe\getcwd() . '/tests/_testdata/generated/baseline.json'
                ],
            ],
        ];
    }

    /** @dataProvider checkProvider */
    public function testCheck(int $expectedStatus, string $expectedOutput, array $inputs): void
    {
        $this->commandTester->execute($inputs);

        self::assertSame($expectedStatus, $this->commandTester->getStatusCode());

        $display = $this->normalizeOutput($this->commandTester->getDisplay(true));

        $expectedOutput = $this->normalizeOutput($expectedOutput);
        self::assertSame($expectedOutput, $display);
    }

    private function normalizeOutput(string $output): string
    {
        return join(
            '',
            array_map(
                fn(string $s): string => trim($s),
                explode("\n", $output)
            )
        );
    }

    public static function checkProvider(): array
    {
        return [
            'no too crappy methods' => [
                'expectedStatus' => 0,
                'expectedOutputs' => '',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 10,
                ],
            ],
            'no too crappy methods, (clover relative path)' => [
                'expectedStatus' => 0,
                'expectedOutputs' => '',
                'inputs' => [
                    'clover-report-path' => \Safe\getcwd() . '/tests/_testdata/clover.xml',
                    'crap-threshold' => 10,
                ],
            ],
            'all crappy methods covered by baseline' => [
                'expectedStatus' => 0,
                'expectedOutput' => '',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 1,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                ],
            ],
            'all crappy methods covered by baseline (baseline relative path)' => [
                'expectedStatus' => 0,
                'expectedOutput' => '',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 1,
                    '--baseline' => \Safe\getcwd() . '/tests/_testdata/baseline.json',
                ],
            ],
            'crappy methods, no baseline' => [
                'expectedStatus' => 1,
                'expectedOutput' =>
                    '[ERROR] The following methods are crappier than allowed 
                     -------- -------- ------ 
                      Class    method   CRAP  
                     -------- -------- ------ 
                      ClassA   m1       10    
                     -------- -------- ------',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 2,
                ],
            ],
            'method got crappier' => [
                'expectedStatus' => 1,
                'expectedOutput' =>
                    '[ERROR] The baseline is not up to date
                     [ERROR] The following methods got crappier 
                     -------- -------- ------ 
                      Class    method   CRAP  
                     -------- -------- ------ 
                      ClassA   m1       15    
                     -------- -------- ------',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover_methodGotCrappier.xml',
                    'crap-threshold' => 1,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                ],
            ],
            'method got less crappy, dont report less crappy' => [
                'expectedStatus' => 0,
                'expectedOutput' => '',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover_methodGotLessCrappy.xml',
                    'crap-threshold' => 1,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                ],
            ],
            'method got less crappy, report less crappy' => [
                'expectedStatus' => 1,
                'expectedOutput' =>
                    '[ERROR] The baseline is not up to date
                     [INFO] The following methods got less crappy 
                     -------- -------- ------ 
                      Class    method   CRAP  
                     -------- -------- ------ 
                      ClassA   m1       5    
                     -------- -------- ------',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover_methodGotLessCrappy.xml',
                    'crap-threshold' => 1,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                    '--report-less-crappy-methods' => true
                ],
            ],
            'method vanished, dont report vanished methods' => [
                'expectedStatus' => 0,
                'expectedOutput' => '',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 5,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                ],
            ],
            'method vanished, report vanished methods' => [
                'expectedStatus' => 1,
                'expectedOutput' =>
                    '[ERROR] The baseline is not up to date
                     [INFO] The following methods vanished 
                     ------------ -------- ------ 
                      Class        method   CRAP  
                     ------------ -------- ------ 
                      Foo\ClassB   m2       2    
                     ------------ -------- ------',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover.xml',
                    'crap-threshold' => 5,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                    '--report-vanished-methods' => true
                ],
            ],
            'method newly occurring' => [
                'expectedStatus' => 1,
                'expectedOutput' =>
                    '[ERROR] The baseline is not up to date
                     [ERROR] The following methods are newly occurring 
                     -------- -------- ------ 
                      Class    method   CRAP  
                     -------- -------- ------ 
                      ClassC   m3       5    
                     -------- -------- ------',
                'inputs' => [
                    'clover-report-path' => __DIR__ . '/../_testdata/clover_methodNewlyOccurring.xml',
                    'crap-threshold' => 1,
                    '--baseline' => __DIR__ . '/../_testdata/baseline.json',
                ],
            ],
        ];
    }
}