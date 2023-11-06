<?php

declare(strict_types=1);

namespace Unh3ck3d\PhpCsFixerGitHook;

use CaptainHook\App\Exception\ActionFailed;
use SebastianFeldmann\Cli\Command\Result;

class LintStagedFilesFailed extends ActionFailed
{
    public static function fromInvalidOption(string $optionName, string $message): self
    {
        return new self("Invalid option '{$optionName}': {$message}");
    }

    public static function fromProcessResult(Result $result): self
    {
        return new self($result->getStdErr() ?: $result->getStdOut());
    }

    public function __construct(string $message)
    {
        parent::__construct('LintStagedFiles failed' . PHP_EOL . $message);
    }
}
