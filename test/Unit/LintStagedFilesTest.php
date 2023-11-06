<?php

declare(strict_types=1);

namespace Unh3ck3dTest\PhpCsFixerGitHook\Unit;

use CaptainHook\App\Config;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Config\Action;
use SebastianFeldmann\Cli\Command\Result;
use SebastianFeldmann\Cli\Processor;
use SebastianFeldmann\Git\Repository;
use Unh3ck3d\PhpCsFixerGitHook\LintStagedFiles;
use Unh3ck3d\PhpCsFixerGitHook\LintStagedFilesFailed;

class LintStagedFilesTest extends TestCase
{
    use ProphecyTrait;

    private const STAGED_FILES = ['src/file1.php', 'test/file2.php', 'file3.php'];

    /** @var ObjectProphecy<Config> */
    private ObjectProphecy $config;

    /** @var ObjectProphecy<IO> */
    private ObjectProphecy $io;

    /** @var ObjectProphecy<Repository> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<Action> */
    private ObjectProphecy $action;

    /** @var ObjectProphecy<Processor> */
    private ObjectProphecy $processor;

    private LintStagedFiles $lintStagedFiles;

    public function setUp(): void
    {
        $this->config = $this->prophesize(Config::class);
        $this->io = $this->prophesize(IO::class);
        $this->repository = $this->prophesize(Repository::class);
        $this->action = $this->prophesize(Action::class);
        $this->processor = $this->prophesize(Processor::class);

        $this->lintStagedFiles = new LintStagedFiles($this->processor->reveal());
    }

    public function test_whenGettingStagedFilesFailed_thenThrowException(): void
    {
        $this->mockGetStagedFilesResult(exitCode: 127, stdOut: '', stdErr: 'command not found');

        $this->assertLintStagedFilesFailed('command not found');

        $this->executeListStagedFiles();

    }

    public function test_whenNoStagedFiles_thenDoNothing(): void
    {
        $this->mockGetStagedFilesResult(exitCode: 0, stdOut: '', stdErr: '');
        $this->mockGetOptions();

        $this->executeListStagedFiles();

        // Assert processor invoked only once for getting staged files
        $this->processor->run(Argument::any())->shouldHaveBeenCalledOnce();
    }

    public function test_whenExecutionOfPhpCsFixerProcessFailed_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions();
        $this->mockPhpCsFixerResult(exitCode: 1, stdErr: 'general error');

        $this->assertLintStagedFilesFailed('general error');

        $this->executeListStagedFiles();
    }

    public function test_whenStagingFilesWithGitFailed_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions();
        $this->mockPhpCsFixerResult();
        $this->mockGitAddResult(1, 'git add error');

        $this->assertLintStagedFilesFailed('git add error');

        $this->executeListStagedFiles();
    }

    public function test_whenPhpCsFixerPathOptionValueNotString_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions([
            LintStagedFiles::OPTION_PHP_CS_FIXER_PATH => ['invalid'],
        ]);

        $this->assertLintStagedFilesFailed("Invalid option '" . LintStagedFiles::OPTION_PHP_CS_FIXER_PATH . "': should be a string");

        $this->executeListStagedFiles();
    }

    public function test_whenConfigOptionValueNotString_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions([
            LintStagedFiles::OPTION_CONFIG_PATH => 123123,
        ]);

        $this->assertLintStagedFilesFailed("Invalid option '" . LintStagedFiles::OPTION_CONFIG_PATH . "': should be a string");

        $this->executeListStagedFiles();
    }

    public function test_whenPathModeOptionValueNotString_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions([
            LintStagedFiles::OPTION_PATH_MODE => ['invalid'],
        ]);

        $this->assertLintStagedFilesFailed("Invalid option '" . LintStagedFiles::OPTION_PATH_MODE . "': should be a string");

        $this->executeListStagedFiles();
    }

    public function test_whenAdditionalArgsOptionValueNotString_thenThrowException(): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions([
            LintStagedFiles::OPTION_ADDITIONAL_ARGS => ['arg1', 'arg2'],
        ]);

        $this->assertLintStagedFilesFailed("Invalid option '" . LintStagedFiles::OPTION_ADDITIONAL_ARGS . "': should be a string");

        $this->executeListStagedFiles();
    }

    /**
     * @dataProvider correctOptionsDataProvider
     * @param array<string, string> $options
     */
    public function test_whenCorrectOptionsPassed_thenRunPhpCsFixerAndReStageFiles(array $options): void
    {
        $this->mockGetStageFilesReturnsMultipleFilenames();
        $this->mockGetOptions($options);
        $this->mockPhpCsFixerResult(
            stdOut: 'PHP Cs Fixer output',
            phpCsFixerPath: $options[LintStagedFiles::OPTION_PHP_CS_FIXER_PATH] ?? LintStagedFiles::DEFAULT_PHP_CS_FIXER_PATH,
            configPath: $options[LintStagedFiles::OPTION_CONFIG_PATH] ?? LintStagedFiles::DEFAULT_CONFIG_PATH,
            pathMode: $options[LintStagedFiles::OPTION_PATH_MODE] ?? LintStagedFiles::DEFAULT_PATH_MODE,
            additionalArgs: $options[LintStagedFiles::OPTION_ADDITIONAL_ARGS] ?? '',
        );
        $this->mockGitAddResult();

        $this->executeListStagedFiles();

        $this->io->write('PHP Cs Fixer output')->shouldHaveBeenCalled();
    }

    /** @return array<string, mixed> */
    public function correctOptionsDataProvider(): array
    {
        return [
            'default options' => [[]],
            'passed path to PHP Cs Fixer executable' => [[LintStagedFiles::OPTION_PHP_CS_FIXER_PATH => '/path/to/php-cs-fixer']],
            'passed path PHP Cs Fixer config' => [[LintStagedFiles::OPTION_CONFIG_PATH => '/path/to/config']],
            'passed pathMode option' => [[LintStagedFiles::OPTION_PATH_MODE => 'overwrite']],
            'passed PHP Cs Fixer additional args' => [[LintStagedFiles::OPTION_ADDITIONAL_ARGS => '-v --diff']],
            'passed multiple options' => [[
                LintStagedFiles::OPTION_PHP_CS_FIXER_PATH => '/path/to/php-cs-fixer',
                LintStagedFiles::OPTION_CONFIG_PATH => '/path/to/config',
                LintStagedFiles::OPTION_PATH_MODE => 'overwrite',
                LintStagedFiles::OPTION_ADDITIONAL_ARGS => '-v --diff',
            ]],
        ];
    }

    private function executeListStagedFiles(): void
    {
        $this->lintStagedFiles->execute(
            $this->config->reveal(),
            $this->io->reveal(),
            $this->repository->reveal(),
            $this->action->reveal(),
        );
    }

    /** @param array<string, mixed> $options */
    private function mockGetOptions(array $options = []): void
    {
        $this->action->getOptions()->willReturn(new Config\Options($options));
    }

    private function mockPhpCsFixerResult(
        int $exitCode = 0,
        string $stdErr = '',
        string $stdOut = '',
        string $phpCsFixerPath = LintStagedFiles::DEFAULT_PHP_CS_FIXER_PATH,
        string $configPath = LintStagedFiles::DEFAULT_CONFIG_PATH,
        string $pathMode = LintStagedFiles::DEFAULT_PATH_MODE,
        string $additionalArgs = '',
    ): void {
        $files = implode(' ', self::STAGED_FILES);
        $command = "{$phpCsFixerPath} fix --config={$configPath} --path-mode={$pathMode} " . ltrim("{$additionalArgs} {$files}");
        $this->processor->run($command)
            ->shouldBeCalled()->willReturn(new Result($command, $exitCode, $stdOut, $stdErr));
    }

    private function mockGetStageFilesReturnsMultipleFilenames(): void
    {
        $this->mockGetStagedFilesResult(0, implode(PHP_EOL, self::STAGED_FILES), '');
    }

    private function mockGetStagedFilesResult(int $exitCode, string $stdOut, string $stdErr): void
    {
        $this->processor->run('git diff --name-only --cached --diff-filter=ACMRTUXB')
            ->shouldBeCalled()
            ->willReturn(
                new Result(
                    'git diff --name-only --cached --diff-filter=ACMRTUXB',
                    $exitCode,
                    $stdOut,
                    $stdErr,
                ),
            );
    }

    private function mockGitAddResult(int $exitCode = 0, string $stdErr = ''): void
    {
        $stagedFiles = implode(' ', self::STAGED_FILES);
        $command = "git add {$stagedFiles}";
        $this->processor->run($command)
            ->shouldBeCalled()
            ->willReturn(new Result($command, $exitCode, '', $stdErr));
    }

    private function assertLintStagedFilesFailed(string $expectedErrorMessage): void
    {
        $this->expectExceptionMessage('LintStagedFiles failed' . PHP_EOL . $expectedErrorMessage);
        $this->expectException(LintStagedFilesFailed::class);
    }
}
