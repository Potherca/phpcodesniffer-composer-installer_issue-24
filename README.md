# `phpcodesniffer-composer-installer` issue #24

[![Build Status][build-shield]][build-page]
[![License][license-shield]][license-page]

> _"Few things are harder to put up with than the annoyance of a good example."_
> ~ Mark Twain

Example to accompany [issue #24][1] of [phpcodesniffer-composer-installer][2].

## Introduction

On github, [jrfnl][3] asked how `phpcodesniffer-composer-installer` handles
installing multiple standards.

This repository is an example implementation to show how this works.

It is supposed to function fully standalone, no external dependencies are required.

Just add PHP.

The result of the accompanying tests can be seen online at [this projects Travis-CI page][build-page].

Tests are present for both "local" (project-specific) and "global" (system-wide) scenario's.

## Original question

The following questions were posed:

> ## Question
>
> Say you have a project in which you require both a project specific coding standard, such as the Joomla or WordPress Coding Standards, as well as the PHPCompatibility standard to check for cross-version compatibility issues.
>
> Assume that both these PHPCS standards require this library and have their `type` set to `phpcodesniffer-standard`,
>
> How does this installer handle that ?
>
> PHPCS by default overwrites a previously registered `installed_path` when the command is run again.
>
> Does this library collect all the paths and run the command once at the end ? Or would each subsequent CS dependency overwrite the path like PHPCS does ?
>
> Related: https://github.com/squizlabs/PHP_CodeSniffer/issues/1436
>

and

> What about if the user installs the sniff library as "global" using Composer and they already have one or more other standards installed globally ? (which they're not updating at the same time)

## Expected behaviour

Given the scenario that a user has defined two sniffs as a dependency, the
_expected_ behaviour would be that the installer installs _both_ sniffs.

This should lead to the path of _both_ sniffs being present in the PHP
Codesniffer configuration file at `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`.

Whether the sniffs are installed one-by-one or altogether, and whether the
sniffs are installed locally or globally also should not make a difference for
the end result.

There is a slightly different scenario when a sniff is already installed _before_
the installer is installed.

In such a case, the installer does not do anything (as
the sniff is already installed and no installation is needed).

However, when another sniff is installed, _both_ sniffs will be installed by the
installer.

## Steps to reproduce

1. Install the installer and two sniffs as composer dependency.
2. Validate the CLI output of composer
3. Validate the contents of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`
4. Repeat test incrementally
5. Repeat incremental test with sniff installed before installer

### 1. Install the installer and sniffs

The following command should be run to require the installer and two separate sniffs:

```bash
php composer.phar require --sort-packages 'dealerdirect/phpcodesniffer-composer-installer' 'frenck/php-compatibility' 'drupal/coder'
```

### 2. Validate CLI output

This should give output similar to the following:

![screenshot of CLI output][4]

Please note the following line in the output:

```bash
PHP CodeSniffer Config installed_paths set to
  /path/to/phpcodesniffer-composer-installer_issue-24/vendor/drupal/coder/coder_sniffer,
  /path/to/phpcodesniffer-composer-installer_issue-24/vendor/frenck/php-compatibility
```

This indicates that the path to _both_ sniffs should be set in the `CodeSniffer.conf` file.

### 3. Validate `CodeSniffer.conf`

To make sure this is correct, the content of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`
needs to be examined.

It _should_ contain something similar to the following:

```php
<?php
 $phpCodeSnifferConfig = array (
  'installed_paths' => '/path/to/phpcodesniffer-composer-installer_issue-24/vendor/drupal/coder/coder_sniffer,/path/to/phpcodesniffer-composer-installer_issue-24/vendor/frenck/php-compatibility',
)
?>
```

### 4. Repeat test incrementally

The result of the previous checks should not be different, whether or not all of
the sniffs are installed together or apart.

To verify this, the packages are iterated over and the previous tests are called
individually for each package.

The results should be the same and the re-used tests should all pass.

### 5. Repeat incremental test with sniff installed before installer

What happens if a sniff is already installed and the installer is installed
afterwards?

If a new sniff is installed, will the "previous" sniff also be installed?

In order to validate this, the order in which the packages are installed is
changed.

Instead of installing the installer first and the sniffs after that, first a
sniff is installed, then the installer, then another sniff.

Re-using the previous test (which in turn re-uses the other tests) it can be
validated that both sniffs are present after the last sniff is installed.

## Proof

This repository provides tests that automatically runs through the steps
described above. The test also contains assertions to validate the described
behaviour.

Tests are present for both "local" (project-specific) and "global" (system-wide)
scenario's.

Various versions of PHPUnit are shipped with this repository. This enables users
to run the test against the PHP version of their choice:

| PHP Version       | PHPUnit Version |
| ----------------: | --------------- |
| PHP 5.3 – 5.6     | PHPUnit 4.8     |
| PHP 5.6, 7.0/7.1. | PHPUnit 5.7     |
| PHP 7.0/7.1.      | PHPUnit 6.1     |

For example, to run the test for PHP 5.6, one of the following commands should
be used:

```bash
php phpunit-4.8.35.phar
```

or

```bash
php phpunit-5.7.19.phar
```

This will run the test to validate the documented behaviour.

The result of these tests can be seen online at [this projects Travis-CI page][build-page]

[1]: https://github.com/DealerDirect/phpcodesniffer-composer-installer/issues/24
[2]: https://github.com/DealerDirect/phpcodesniffer-composer-installer/
[3]: https://github.com/jrfnl
[4]: ./screenshot.png
[build-page]: https://travis-ci.org/Potherca/phpcodesniffer-composer-installer_issue-24
[build-shield]: https://travis-ci.org/Potherca/phpcodesniffer-composer-installer_issue-24.svg
[license-page]: LICENSE
[license-shield]: https://img.shields.io/github/license/Potherca/phpcodesniffer-composer-installer_issue-24.svg
