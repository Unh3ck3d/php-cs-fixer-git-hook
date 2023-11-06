<?php

declare(strict_types=1);

namespace Unh3ck3d\PhpCsFixerGitHook;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Hook\Action;
use SebastianFeldmann\Cli\Command\Result;
use SebastianFeldmann\Cli\Processor;
use SebastianFeldmann\Cli\Processor\ProcOpen;
use SebastianFeldmann\Git\Repository;

class LintStagedFiles implements Action
{
    public const OPTION_PHP_CS_FIXER_PATH = 'phpCsFixerPath';
    public const OPTION_PATH_MODE = 'pathMode';
    public const OPTION_CONFIG_PATH = 'config';
    public const OPTION_ADDITIONAL_ARGS = 'additionalArgs';

    public const DEFAULT_PHP_CS_FIXER_PATH = './vendor/bin/php-cs-fixer';
    public const DEFAULT_PATH_MODE = 'intersection';
    public const DEFAULT_CONFIG_PATH = '.php-cs-fixer.dist.php';

    private Processor $processor;

    public function __construct(Processor $processor = null)
    {
        $this->processor = $processor ?? new ProcOpen();
    }

    public function execute(Config $config, IO $io, Repository $repository, Config\Action $action): void
    {
        $stagedFiles = $this->getStagedFiles();
        // Do nothing if there are no staged files to lint
        if (!$stagedFiles) {
            return;
        }

        $result = $this->executeCsFixer($action->getOptions(), $stagedFiles);

        // We need to re-stage files because they could be modified by PHP Cs Fixer
        $this->stageFiles($stagedFiles);

        $io->write($result->getStdOut());
    }

    /**
     * @param string[] $stagedFiles
     */
    private function executeCsFixer(Config\Options $options, array $stagedFiles): Result
    {
        $result = $this->processor->run($this->buildShellCommand($options, $stagedFiles));
        if (!$result->isSuccessful()) {
            throw LintStagedFilesFailed::fromProcessResult($result);
        }

        return $result;
    }

    /**
     * @param string[] $stagedFiles
     */
    private function buildShellCommand(Config\Options $options, array $stagedFiles): string
    {
        return implode(
            ' ',
            array_filter([
                $this->getPhpCsFixerPathOption($options),
                'fix',
                $this->getConfigOption($options),
                $this->getPathModeOption($options),
                $this->getAdditionalCsFixerArgsOption($options),
                implode(' ', $stagedFiles),
            ], fn ($x) => (bool)strlen($x)),
        );
    }

    /**
     * @return string[]
     */
    private function getStagedFiles(): array
    {
        $result = $this->processor->run('git diff --name-only --cached --diff-filter=ACMRTUXB');
        if (!$result->isSuccessful()) {
            throw LintStagedFilesFailed::fromProcessResult($result);
        }

        return $result->getStdOut() ? explode(PHP_EOL, $result->getStdOut()) : [];
    }

    /**
     * @param string[] $files
     */
    private function stageFiles(array $files): void
    {
        $result = $this->processor->run('git add ' . implode(' ', $files));
        if (!$result->isSuccessful()) {
            throw LintStagedFilesFailed::fromProcessResult($result);
        }
    }

    private function getPhpCsFixerPathOption(Config\Options $options): string
    {
        return $this->getStringOption($options, 'phpCsFixerPath', self::DEFAULT_PHP_CS_FIXER_PATH);
    }

    private function getConfigOption(Config\Options $options): string
    {
        return '--config=' . $this->getStringOption($options, 'config', self::DEFAULT_CONFIG_PATH);
    }

    private function getPathModeOption(Config\Options $options): string
    {
        // By default, we run PHP Cs Fixer on intersection of passed files and allowed files defined in config
        return '--path-mode=' . $this->getStringOption($options, 'pathMode', self::DEFAULT_PATH_MODE);
    }

    private function getAdditionalCsFixerArgsOption(Config\Options $options): string
    {
        return $this->getStringOption($options, 'additionalArgs', '');
    }

    private function getStringOption(Config\Options $options, string $optionName, string $defaultValue): string
    {
        $value = $options->get($optionName, $defaultValue);
        if (!is_string($value)) {
            throw LintStagedFilesFailed::fromInvalidOption($optionName, 'should be a string');
        }

        return $value;
    }
}
