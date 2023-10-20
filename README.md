<a name="readme-top"></a>

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/Unh3ck3d/php-cs-fixer-git-hook/blob/main/LICENSE)

<h3 align="center">php-cs-fixer-git-hook</h3>
<p align="center">
 Git hook running PHP CS Fixer on staged files using CaptainHook
<br />
<a href="https://github.com/Unh3ck3d/php-cs-fixer-git-hook/issues">Report Bug</a>
·
<a href="https://github.com/Unh3ck3d/php-cs-fixer-git-hook/issues">Request Feature</a>
</p>

## About The Project

Git hook that with each `git commit` command runs Php Cs Fixer
on staged files to automatically fix them and re-stage before committing.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Requirements

* [PHP](https://www.php.net/) >= 8.0
* [CaptainHook](http://captainhook.info/) >= 5.0
* [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Installation

1. Install package as a dev dependency using composer
    ```
    composer require --dev unh3ck3d/php-cs-fixer-git-hook
    ```
2. Add the following code to your `captainhook.json` configuration file
    ```
    {
      "pre-commit": {
        "enabled": true,
        "actions": [
          {
            "action": "\\Unh3ck3d\\PhpCsFixerGitHook\\LintStagedFiles"
          }
        ]
      }
    }
    ```
3. Install newly added hook by following [CaptainHook docs](http://captainhook.info/install.html)

That's it. From now on after running `git commit` files that were staged will be
automatically fixed by Php Cs Fixer.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Configuration

You can customize the behaviour how git hook is run by changing following options

| Option         | Description                                                                 |
|----------------|-----------------------------------------------------------------------------|
| phpCsFixerPath | Path to Php Cs Fixer executable. Defaults to `./vendor/bin/php-cs-fixer`.   |
| pathMode       | `path-mode` cli option of Php Cs Fixer. Defaults to `intersection`.         |
| config         | `config` cli option of Php Cs Fixer. Defaults to `.php-cs-fixer.dist.php`.  |
| additionalArgs | String of additional arguments that will be passed to Php Cs Fixer process. |

e.g.
```
{
  "pre-commit": {
    "enabled": true,
    "actions": [
      {
        "action": "\\Unh3ck3d\\PhpCsFixerGitHook\\LintStagedFiles",
        "options": {
            "phpCsFixerPath": "php-cs-fixer.phar",
            "pathMode": "overwrite",
            "config": ".php-cs-fixer.php",
            "additionalArgs": "-v --dry-run --diff"
        }
      }
    ]
  }
}
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## License

Distributed under the MIT License. See `LICENSE` for more information.

<p align="right">(<a href="#readme-top">back to top</a>)</p>
