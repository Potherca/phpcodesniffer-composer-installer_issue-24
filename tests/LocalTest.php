<?php

namespace Dealerdirect\Composer\Plugin\Installers\PHPCodeSniffer;

require_once __DIR__ . '/AbstractInstallerTest.php';

class LocalTest extends AbstractInstallerTest
{
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
}

/*EOF*/
