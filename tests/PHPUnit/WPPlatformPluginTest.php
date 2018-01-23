<?php

namespace Tests\HowToADHD\Composer\PHPUnit;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\NullIO;
use HowToADHD\Composer\WPPlatformPlugin;
use PHPUnit\Framework\TestCase;

class WPPlatformPluginTest extends TestCase
{

    public function testActivate()
    {
        $composer            = new Composer();
        $installationManager = new InstallationManager();
        $composer->setInstallationManager($installationManager);
        $composer->setConfig(new Config());

        $plugin = new WPPlatformPlugin();
        $plugin->activate($composer, new NullIO());

        $installer = $installationManager->getInstaller('wp-platform');

        $this->assertInstanceOf('\HowToADHD\Composer\WPPlatformInstaller', $installer);

        $installer = $installationManager->getInstaller('wordpress-core');

        $this->assertInstanceOf('\HowToADHD\Composer\WordPressCoreInstaller', $installer);
    }
}
