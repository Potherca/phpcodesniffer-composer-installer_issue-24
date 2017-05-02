# `phpcodesniffer-composer-installer` issue #24

> _"Few things are harder to put up with than the annoyance of a good example."_
> ~ Mark Twain

Example to accompany [issue #24][1] of [phpcodesniffer-composer-installer][2].

## Introduction

On github, [jrfnl][3] asked how `phpcodesniffer-composer-installer` handles
installing multiple standards.

This repository is an example implementation to show how this works.

It is supposed to function fully standalone, no external dependencies are required.

Just add PHP.

## Original question

The original question was posed as follows:

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

## Expected behaviour

Given the scenario that a user has defined two sniffs as a dependency, the
_expected_ behaviour would be that the installer installs _both_ sniffs.

This should lead to the path of _both_ sniffs being present in the PHP Codesniffer
 configuration file at `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`.

## Steps to reproduce

1. Install the installer and two sniffs as composer dependency.
2. Validate the CLI output of composer
3. Validate the contents of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`

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

## Proof

This repository provides a test that automatically runs through the steps
described below. The test also contains assertions to validate the described
behaviour.

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


[1]: https://github.com/DealerDirect/phpcodesniffer-composer-installer/issues/24
[2]: https://github.com/DealerDirect/phpcodesniffer-composer-installer/
[3]: https://github.com/jrfnl
[4]: ./screenshot.png
