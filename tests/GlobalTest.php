<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractInstallerTest.php';

class GlobalTest extends AbstractInstallerTest
{
    /**
     * @return string
     */
    final public function getComposerCommand()
    {
        return 'php composer.phar global';
    }

    /**
     * @return string
     */
    final public static function getComposerDirectory()
    {
        static $vendorDirectory;

        if ($vendorDirectory === null) {

            $command = 'php composer.phar config --global home 2>&1';
            $output = [];
            exec($command, $output, $exitCode);

            self::assertSame(0, $exitCode, 'Could not retrieve composer global directory.');

            $vendorDirectory = array_pop($output);
        }

        return $vendorDirectory;
    }
}

/*EOF*/
