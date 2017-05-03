<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractInstallerTest.php';

class LocalTest extends AbstractInstallerTest
{
    /**
     * @return string
     */
    final public function getComposerCommand()
    {
        return 'php composer.phar';
    }

    /**
     * @return string
     */
    final public static function getComposerDirectory()
    {
        return dirname(__DIR__);
    }
}

/*EOF*/
