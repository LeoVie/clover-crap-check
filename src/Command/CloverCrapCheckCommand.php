<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Command;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\BaselineDiffersResult;
use Leovie\PhpunitCrapCheck\DTO\BaselineEqualsResult;
use Leovie\PhpunitCrapCheck\DTO\CrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\Parser\BaselineParser;
use Leovie\PhpunitCrapCheck\Service\BaselineCompareService;
use Leovie\PhpunitCrapCheck\Service\BaselineOutputService;
use Leovie\PhpunitCrapCheck\Service\CrapCheckService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'clover-crap-check')]
final class CloverCrapCheckCommand extends Command
{
    private const string ARG_CLOVER_REPORT_PATH = 'clover-report-path';
    private const string ARG_CRAP_THRESHOLD = 'crap-threshold';
    private const string OPT_BASELINE = 'baseline';
    private const string OPT_GENERATE_BASELINE = 'generate-baseline';
    private const string OPT_REPORT_LESS_CRAPPY_METHODS = 'report-less-crappy-methods';
    private const string OPT_REPORT_VANISHED_METHODS = 'report-vanished-methods';

    public function __construct(
        private readonly CrapCheckService       $crapCheckService,
        private readonly BaselineCompareService $baselineCompareService,
        private readonly BaselineOutputService  $baselineOutputService,
        private readonly BaselineParser         $baselineParser,
    )
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: self::ARG_CLOVER_REPORT_PATH,
            mode: InputArgument::REQUIRED,
            description: 'Absolute path to clover report file',
        )->addArgument(
            name: self::ARG_CRAP_THRESHOLD,
            mode: InputArgument::REQUIRED,
            description: 'Max allowed crap index',
        )->addOption(
            name: self::OPT_BASELINE,
            shortcut: 'b',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Absolute path to your baseline file',
        )->addOption(
            name: self::OPT_GENERATE_BASELINE,
            shortcut: 'g',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Absolute path to the baseline file that will get generated',
        )->addOption(
            name: self::OPT_REPORT_LESS_CRAPPY_METHODS,
            shortcut: 'l',
            mode: InputOption::VALUE_NONE,
            description: 'Report methods that are less crappy than in baseline',
        )->addOption(
            name: self::OPT_REPORT_VANISHED_METHODS,
            shortcut: 'd',
            mode: InputOption::VALUE_NONE,
            description: 'Report methods that are in your baseline but not occurring anymore',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = \Safe\getcwd();

        $io = new SymfonyStyle($input, $output);

        try {
            $cloverReportPath = $this->getCloverReportPath($input, $cwd);
            $crapThreshold = $this->getCrapThreshold($input);
            $baselinePath = $this->getBaselinePath($input, $cwd);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $generateBaselinePath = $this->getGenerateBaselinePath($input, $cwd);
        $reportLessCrappyMethods = $this->getReportLessCrappyMethods($input);
        $reportVanishedMethods = $this->getReportVanishedMethods($input);

        if ($baselinePath !== null && $generateBaselinePath !== null) {
            $io->error('Only use baseline or generate baseline, not both.');
            return Command::INVALID;
        }

        $cloverReportContent = \Safe\file_get_contents($cloverReportPath);
        $crapCheckResult = $this->crapCheckService->check($cloverReportContent, $crapThreshold);

        if ($generateBaselinePath !== null) {
            return $this->generateBaseline($io, $generateBaselinePath, $crapCheckResult);
        }

        if ($baselinePath !== null) {
            return $this->compareWithBaseline($io, $baselinePath, $crapCheckResult, $reportLessCrappyMethods, $reportVanishedMethods);
        }

        if ($crapCheckResult instanceof EmptyCrapCheckResult) {
            if ($io->isVerbose()) {
                $io->info('No crappy methods detected.');
            }

            return Command::SUCCESS;
        }

        /** @var NonEmptyCrapCheckResult $crapCheckResult */

        $io->error('The following methods are crappier than allowed');
        $this->outputMethodsTable($io, $crapCheckResult->tooCrappyMethods);

        return Command::FAILURE;
    }

    private function getCloverReportPath(InputInterface $input, string $cwd): string
    {
        /** @var string $cloverReportPath */
        $cloverReportPath = $input->getArgument(self::ARG_CLOVER_REPORT_PATH);
        $cloverReportPath = Path::makeAbsolute($cloverReportPath, $cwd);

        if (!file_exists($cloverReportPath)) {
            throw new \InvalidArgumentException(sprintf('Clover report could not be found at "%s".', $cloverReportPath));
        }

        return $cloverReportPath;
    }

    private function getCrapThreshold(InputInterface $input): int
    {
        $crapThreshold = $input->getArgument(self::ARG_CRAP_THRESHOLD);

        if (!is_numeric($crapThreshold)) {
            throw new \InvalidArgumentException(sprintf('%s is not an integer.', self::ARG_CRAP_THRESHOLD));
        }

        return (int)$crapThreshold;
    }

    private function getBaselinePath(InputInterface $input, string $cwd): ?string
    {
        try {
            /** @var ?string $baselinePath */
            $baselinePath = $input->getOption(self::OPT_BASELINE);

            if ($baselinePath === null) {
                return null;
            }

            Path::makeAbsolute($baselinePath, $cwd);

            if (!file_exists($baselinePath)) {
                throw new \InvalidArgumentException(
                    sprintf('Baseline could not be found at "%s".', $baselinePath)
                );
            }

            return $baselinePath;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function getGenerateBaselinePath(InputInterface $input, string $cwd): ?string
    {
        try {
            /** @var ?string $generateBaselinePath */
            $generateBaselinePath = $input->getOption(self::OPT_GENERATE_BASELINE);

            if ($generateBaselinePath === null) {
                return null;
            }

            return Path::makeAbsolute($generateBaselinePath, $cwd);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function getReportLessCrappyMethods(InputInterface $input): bool
    {
        return (bool)$input->getOption(self::OPT_REPORT_LESS_CRAPPY_METHODS);
    }

    private function getReportVanishedMethods(InputInterface $input): bool
    {
        return (bool)$input->getOption(self::OPT_REPORT_VANISHED_METHODS);
    }

    private function generateBaseline(SymfonyStyle $io, string $generateBaselinePath, CrapCheckResult $crapCheckResult): int
    {
        if ($io->isVerbose()) {
            $io->info(sprintf('Generating baseline at "%s".', $generateBaselinePath));
        }

        $this->baselineOutputService->save(
            new Baseline($crapCheckResult),
            $generateBaselinePath
        );

        return Command::SUCCESS;
    }

    private function compareWithBaseline(
        SymfonyStyle    $io,
        string          $baselinePath,
        CrapCheckResult $crapCheckResult,
        bool            $reportLessCrappyMethods,
        bool            $reportVanishedMethods,
    ): int
    {
        if ($io->isVerbose()) {
            $io->info(sprintf('Using baseline at "%s".', $baselinePath));
        }

        $baseline = $this->baselineParser->parse(
            \Safe\file_get_contents($baselinePath)
        );

        $baselineCompareResult = $this->baselineCompareService->compare(
            $crapCheckResult, $baseline
        );

        if ($baselineCompareResult instanceof BaselineEqualsResult) {
            if ($io->isVerbose()) {
                $io->info('Crappy methods are matching baseline.');
            }

            return Command::SUCCESS;
        }

        /** @var BaselineDiffersResult $baselineCompareResult */

        $hasMethodsNewlyOccurring = count($baselineCompareResult->methodsNewlyOccurring) > 0;
        $hasMethodsGotCrappier = count($baselineCompareResult->methodsGotCrappier) > 0;
        $hasVanishedMethods = count($baselineCompareResult->methodsNotOccurringAnymore) > 0;
        $hasMethodsGotLessCrappy = count($baselineCompareResult->methodsGotLessCrappy) > 0;

        $hasOnlyLessCrappyOrVanishedMethods = !$hasMethodsNewlyOccurring
            && !$hasMethodsGotCrappier;

        if (!$reportLessCrappyMethods && !$reportVanishedMethods && $hasOnlyLessCrappyOrVanishedMethods) {
            return Command::SUCCESS;
        }

        $io->error('The baseline is not up to date');
        if ($hasMethodsNewlyOccurring) {
            $io->error('The following methods are newly occurring');
            $this->outputMethodsTable($io, $baselineCompareResult->methodsNewlyOccurring);
        }
        if ($hasMethodsGotCrappier) {
            $io->error('The following methods got crappier');
            $this->outputMethodsTable($io, $baselineCompareResult->methodsGotCrappier);
        }
        if ($reportVanishedMethods) {
            if ($hasVanishedMethods) {
                $io->info('The following methods vanished');
                $this->outputMethodsTable($io, $baselineCompareResult->methodsNotOccurringAnymore);
            }
        }
        if ($reportLessCrappyMethods) {
            if ($hasMethodsGotLessCrappy) {
                $io->info('The following methods got less crappy');
                $this->outputMethodsTable($io, $baselineCompareResult->methodsGotLessCrappy);
            }
        }

        return Command::FAILURE;
    }

    /** @param array<Method> $methods */
    private function outputMethodsTable(SymfonyStyle $io, array $methods): void
    {
        $io->table(
            ['Class', 'method', 'CRAP'],
            array_map(
                fn(Method $method): array => [$method->classFQN, $method->name, $method->crap],
                $methods
            )
        );
    }
}