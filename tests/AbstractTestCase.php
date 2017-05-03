<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

if (class_exists('\PHPUnit_Framework_TestCase')) {
    /** @noinspection PhpUndefinedClassInspection */
    abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase {}
} elseif (class_exists('\PHPUnit\Framework\TestCase')) {
    /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
    /** @noinspection PhpUndefinedClassInspection */
    abstract class AbstractTestCase extends \PHPUnit\Framework\TestCase {}
} else {

    $message = vsprintf(
        'Could not run tests. Could not find either "%s" or "%s" class.', [
            '\PHPUnit_Framework_TestCase',
            '\PHPUnit\Framework\TestCase',
        ]
    );

    throw new \RuntimeException($message);
}

/*EOF*/
