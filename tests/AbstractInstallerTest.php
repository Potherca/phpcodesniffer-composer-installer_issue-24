<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractTestCase.php';

/** @noinspection PhpUndefinedClassInspection */
abstract class AbstractInstallerTest  extends AbstractTestCase
{
    ////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /** @var array */
    private static $packages = [
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'frenck/php-compatibility',
    ];

    //////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\
    private function getVendorDirectory()
    {
        return static::getComposerDirectory() . '/vendor';
    }

    /**
     * @return string
     */
    final public function getCodesnifferConfigurationPath()
    {
        return $this->getVendorDirectory() . '/squizlabs/php_codesniffer/CodeSniffer.conf';
    }

    //////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    final public static function setUpBeforeClass()
    {
    }

    final public function tearDown()
    {
        $this->removeFile(static::getComposerDirectory().'/composer.lock', 'composer lock file');
    }

    /**
     * 1. Install the installer and two sniffs as composer dependency.
     */
    final public function testComposerShouldInstallBothSniffsWhenAskedTo()
    {
        $packages = self::$packages;

        $this->removePreviousInstall($packages);

        $output = $this->installPackages($packages);
        $vendorDirectory = $this->getVendorDirectory();

        return [
            $output,
            [
                $vendorDirectory .'/drupal/coder/coder_sniffer',
                $vendorDirectory .'/frenck/php-compatibility',
            ],
        ];
    }

    /**
     * 2. Validate the CLI output of composer
     *
     * @depends testComposerShouldInstallBothSniffsWhenAskedTo
     *
     * @param array[] $params
     *
     * @return array
     */
    final public function testComposerOutputShouldMentionOfInstalledSniffsWhenSniffsHaveBeenInstalled(array $params)
    {
        list($output, $installed) = $params;

        $actual = array_pop($output);

        $expected = sprintf(
            'PHP CodeSniffer Config installed_paths set to %s',
            implode(',', $installed)
        );

        self::assertSame($expected, $actual, 'Composer output did not contain reference to installed sniff(s)');

        return $installed;
    }

    /**
     *  3. Validate the contents of `vendor/squizlabs/php_codesniffer/CodeSniffer.conf`
     *
     * @depends testComposerOutputShouldMentionOfInstalledSniffsWhenSniffsHaveBeenInstalled
     *
     * @param array $installed
     */
    final public function testCodeSnifferConfigurationFileShouldMentionBothSniffs(array $installed)
    {
        $configurationPath = $this->getCodesnifferConfigurationPath();

        self::assertFileExists($configurationPath, 'CodeSniffer configuration file does not exist');

        $actual = file_get_contents($configurationPath);

        $expected = sprintf(<<<'TXT'
<?php
 $phpCodeSnifferConfig = array (
  'installed_paths' => '%s',
)
?>
TXT
            ,
            implode(',', $installed)
        );

        self::assertSame($expected, $actual, 'Codesniff configuration file did not contain reference to installed sniff(s)');
    }

    ////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /**
     * @param string $command
     *
     * @return array
     */
    private function executeComposerCommand($command)
    {
        $command = $this->getComposerCommand() . ' '. $command . ' 2>&1';
        $output = [];

        exec($command, $output, $exitCode);

        $message = vsprintf(
            'The command "%s" did not run successfully. %s',
            [
                $command,
                implode("\n", $output),
            ]
        );

        self::assertSame(0, $exitCode, $message);

        return $output;
    }

    /**
     * @param $packages
     *
     * @return array
     */
    private function installPackages($packages)
    {
        $command = 'require --sort-packages "' . implode('" "', $packages) . '"';

        return $this->executeComposerCommand($command);
    }

    /**
     * The code to recursively delete a directory is taken from
     * http://andy-carter.com/blog/recursively-remove-a-directory-in-php
     *
     * The excellent `glob` solutions has been taken from
     * http://stackoverflow.com/a/33059445/153049
     *
     * @param string $path
     * @param bool $success
     *
     * @return bool
     */
    private function removeDirectory($path, $success = true)
    {
        $files = glob($path . '/{,.}[!.,!..]*',GLOB_MARK|GLOB_BRACE);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $success = $this->removeDirectory($file, $success) && $success;
            } else {
                $success = unlink($file) && $success;
            }
        }

        $success = rmdir($path) && $success;

        return $success;
    }

    /**
     * @param string $file
     * @param string $fileType
     */
    private function removeFile($file, $fileType = 'file')
    {
        if (is_file($file)) {
            $errorMessage = 'Could not remove '.$fileType;

            $removed = unlink($file);

            self::assertTrue($removed, $errorMessage);
        }
    }

    /**
     * @param array $packages
     */
    private function removePreviousInstall(array $packages)
    {
        /*/ Remove any previously installed vendors /*/
        array_walk($packages, function ($package) {
            $path = static::getVendorDirectory() . '/' . $package;
            if (is_dir($path)) {
                $removed = self::removeDirectory($path);

                $message = sprintf('Could not remove "%s" vendor directory', $package);
                self::assertTrue($removed, $message);
            }
        });

        $this->removeFile(static::getComposerDirectory().'/composer.lock', 'composer lock file');
        $this->removeFile($this->getCodesnifferConfigurationPath(), 'Codesniffer configuration file');
    }
}

/*EOF*/
