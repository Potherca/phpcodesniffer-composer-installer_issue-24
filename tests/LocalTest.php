<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractTestCase.php';

class LocalTest extends AbstractTestCase
{
    /** @var array */
    private static $output = [];
    /** @var string  */
    private static $vendorDirectory;

    /**
     * @return string
     */
    final public static function getVendorDirectory()
    {
        if (self::$vendorDirectory === null) {
            self::$vendorDirectory = dirname(__DIR__).'/vendor';
        }

        return self::$vendorDirectory;
    }

    /**
     * 1. Install the installer and two sniffs as composer dependency.
     */
    final public static function setUpBeforeClass()
    {
        $vendorDirectory = self::getVendorDirectory();

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
            'PHP CodeSniffer Config installed_paths set to %1$s/drupal/coder/coder_sniffer,%1$s/frenck/php-compatibility',
            self::getVendorDirectory()
        );

        self::assertSame($expected, $actual, 'Composer output did not contain reference to both sniffs');
    }

    /**
     *  3. Validate the contents of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`
     */
    final public function testCodeSnifferConfigurationFileShouldMentionBothSniffs()
    {
        $configurationPath = self::getVendorDirectory() . '/squizlabs/php_codesniffer/CodeSniffer.conf';

        self::assertFileExists($configurationPath, 'CodeSniffer configuration file does not exist');

        $actual = file_get_contents($configurationPath);

        $expected = sprintf(<<<'TXT'
<?php
 $phpCodeSnifferConfig = array (
  'installed_paths' => '%1$s/drupal/coder/coder_sniffer,%1$s/frenck/php-compatibility',
)
?>
TXT
            ,
            self::getVendorDirectory()
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
