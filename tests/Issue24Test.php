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

class Issue24Test extends AbstractTestCase
{
    /** @var array */
    private static $output = [];
    /** @var string  */
    private static $rootDirectory = __DIR__;

    /**
     * 1. Install the installer and two sniffs as composer dependency.
     */
    final public static function setUpBeforeClass()
    {
        $vendorDirectory = self::$rootDirectory . '/vendor/';

        // Remove any previously installed vendors
        if (is_dir($vendorDirectory)) {
            $removed = self::removeDirectory($vendorDirectory);

            self::assertTrue($removed, 'Could not remove "vendor" directory');
        }

        // Install the installer and sniffs
        $command = 'php composer.phar require --sort-packages "dealerdirect/phpcodesniffer-composer-installer" "frenck/php-compatibility" "drupal/coder" 2>&1';

        exec($command, self::$output, $exitCode);

        self::assertSame(0, $exitCode, 'The "composer require" command did not run successfully.');
    }


    /**
     * 2. Validate the CLI output of composer
     */
    final public function testComposerOutputShouldMentionBothSniffsWhenAskedToInstallSniffs()
    {
        $actual = array_pop(self::$output);

        $expected = sprintf(
            'PHP CodeSniffer Config installed_paths set to %1$s/vendor/drupal/coder/coder_sniffer,%1$s/vendor/frenck/php-compatibility',
            self::$rootDirectory
        );

        self::assertSame($expected, $actual, 'Composer output did not contain reference to both sniffs');
    }

    /**
     *  3. Validate the contents of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`
     */
    final public function testCodeSnifferConfigurationFileShouldMentionBothSniffs()
    {
        $configurationPath = self::$rootDirectory . '/vendor/squizlabs/php_codesniffer/CodeSniffer.conf';

        self::assertFileExists($configurationPath, 'CodeSniffer configuration file does not exist');

        $actual = file_get_contents($configurationPath);

        $expected = sprintf(<<<'TXT'
<?php
 $phpCodeSnifferConfig = array (
  'installed_paths' => '%1$s/vendor/drupal/coder/coder_sniffer,%1$s/vendor/frenck/php-compatibility',
)
?>
TXT
            ,
            self::$rootDirectory
        );

        self::assertSame($expected, $actual, 'Codesniff configuration file did not contain reference to both sniffs');
    }

    /**
     * The code to recursively delete a directory is taken from
     * http://andy-carter.com/blog/recursively-remove-a-directory-in-php
     *
     * @param string $path
     * @param bool $success
     *
     * @return bool
     */
    private static function removeDirectory($path, $success = true)
    {
        $files = glob($path . '/*');

        foreach ($files as $file) {
            if (is_dir($file)) {
                $success = self::removeDirectory($file, $success) && $success;
            } else {
                $success = unlink($file) && $success;
            }
        }

        return $success;
    }
}

/*EOF*/
