<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Service;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\Generator\BaselineOutputGeneratorInterface;
use Leovie\PhpunitCrapCheck\Service\BaselineOutputService;
use PHPUnit\Framework\TestCase;

class BaselineOutputServiceTest extends TestCase
{
    private const GENERATED_BASELINE_FILE = __DIR__ . '/../../_testdata/generated/baseline.json';

    protected function setUp(): void
    {
        $this->deleteExistingBaselineFile();
    }

    protected function tearDown(): void
    {
        $this->deleteExistingBaselineFile();
    }

    private function deleteExistingBaselineFile(): void
    {
        if (file_exists(self::GENERATED_BASELINE_FILE)) {
            unlink(self::GENERATED_BASELINE_FILE);
        }
    }

    public function testSave(): void
    {
        $baselineOutputGenerator = $this->createMock(BaselineOutputGeneratorInterface::class);
        $baselineOutputGenerator
            ->method('generate')
            ->willReturn('baseline');

        (new BaselineOutputService($baselineOutputGenerator))
            ->save(
                new Baseline(new NonEmptyCrapCheckResult([])),
                self::GENERATED_BASELINE_FILE
            );

        self::assertSame('baseline', \Safe\file_get_contents(self::GENERATED_BASELINE_FILE));
    }
}