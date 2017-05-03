<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractTestCase.php';

/** @noinspection PhpUndefinedClassInspection
 *
 * Automated checks to validate the behaviour of the phpcodesniffer-composer-installer
 * when several different codesniffs are installed.
 *
 * Contains tests for both the all-at-once and the incremental install scenarios.
 */
abstract class AbstractInstallerTest  extends AbstractTestCase
{
    ////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /** @var string */
    private static $originalComposerJson;

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
        self::storeOriginalComposerJson();
    }

    final public function tearDown()
    {
        $this->restoreOriginalComposerJson();
        $this->removeFile(static::getComposerDirectory().'/composer.lock', 'composer lock');
    }

    /**
     * 1. Install the installer and two sniffs as composer dependency.
     */
    final public function testComposerShouldInstallBothSniffsWhenAskedTo()
    {
        $packages = [
            'dealerdirect/phpcodesniffer-composer-installer',
            'drupal/coder',
            'frenck/php-compatibility',
        ];

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

    /**
     * 4. Repeat test incrementally
     *
     * @param array $packages
     * @param string $subject
     */
    final public function testResultsShouldBeTheSameWhenPackagesAreInstalledIncrementally(array $packages = [], $subject = '')
    {
        if (count($packages) === 0) {
            $packages = [
                'dealerdirect/phpcodesniffer-composer-installer',
                'drupal/coder',
                'frenck/php-compatibility',
            ];
        }

        $this->removePreviousInstall($packages);

        $installed = [];

        array_walk($packages, function ($package) use (&$installed, $subject) {
            $output = $this->installPackages([$package]);

            if ($this->isSniff($package)) {

                if ($package === 'drupal/coder') {
                    $package .= '/coder_sniffer';
                }

                $installed[] = static::getVendorDirectory().'/'.$package;

                if ($subject === '' || $subject === $package) {
                    $this->testComposerOutputShouldMentionOfInstalledSniffsWhenSniffsHaveBeenInstalled([$output, $installed]);
                    $this->testCodeSnifferConfigurationFileShouldMentionBothSniffs($installed);
                }
            }
        });
    }

    /**
     * 5. Repeat incremental test with sniff installed before installer
     */
    final public function testIncrementalResultsShouldBeTheSameWhenPackagesAreInstalledInDifferentOrder()
    {
        $packages = [
            'drupal/coder',
            'dealerdirect/phpcodesniffer-composer-installer',
            'frenck/php-compatibility',
        ];

        $this->testResultsShouldBeTheSameWhenPackagesAreInstalledIncrementally($packages, 'frenck/php-compatibility');
    }

    ////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    private function assertSniffsNotInComposerJson($packages)
    {
        $output = $this->executeComposerCommand('show');

        array_walk($output, function ($line) use ($packages) {
            array_walk($packages, function($package) use ($line) {
                if ($this->isSniff($package)) {
                    $position = strpos($line, $package);
                    self::assertFalse($position, 'Test can not run as package is already installed: '.$package);
                }
            });
        });
    }

    private function assertSniffsNotInstalled(array $packages)
    {
        $json = json_decode(self::$originalComposerJson, true);

        if (array_key_exists('require', $json) && is_array($json['require'])) {
            array_walk($json['require'], function ($version, $requirePackage) use ($packages) {
                array_walk($packages, function($package) use ($requirePackage) {
                    if ($this->isSniff($package)) {
                        self::assertNotEquals($requirePackage, $package, 'Test can not run as package is declared in composer.json: '.$package);
                    }
                });
            });
        }

    }

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
     * @param string $package
     *
     * @return bool
     */
    private function isSniff($package)
    {
        return $package !== 'dealerdirect/phpcodesniffer-composer-installer';
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
    private function removeFile($file, $fileType = '')
    {
        if (is_file($file)) {
            $errorMessage = 'Could not remove '.$fileType.' file';

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

        $this->restoreOriginalComposerJson();
        $this->removeFile(static::getComposerDirectory().'/composer.lock', 'composer lock');
        $this->removeFile($this->getCodesnifferConfigurationPath(), 'Codesniffer configuration');

        $this->assertSniffsNotInstalled($packages);
        $this->assertSniffsNotInComposerJson($packages);
    }

    private function restoreOriginalComposerJson()
    {
        $composerFile = static::getComposerDirectory() . '/composer.json';
        $contents = self::$originalComposerJson;

        if ($contents === null) {
            $this->removeFile($composerFile, 'composer.json');
        } else {
            self::assertFileExists($composerFile, 'Composer file does not exist');

            $actual = file_put_contents($composerFile, $contents);

            self::assertNotFalse($actual, 'The composer.json file could not be restored');
        }
    }

    private static function storeOriginalComposerJson()
    {
        $composerFile = static::getComposerDirectory() . '/composer.json';

        if (is_file($composerFile)) {
            self::$originalComposerJson = file_get_contents($composerFile);
        }
    }
}

/*EOF*/
