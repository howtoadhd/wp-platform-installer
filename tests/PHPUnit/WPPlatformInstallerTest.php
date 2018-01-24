<?php

namespace Tests\HowToADHD\Composer\PHPUnit;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use HowToADHD\Composer\WPPlatformInstaller;

class WPPlatformInstallerTest extends TestCase
{

    public function testSupports()
    {
        $installer = new WPPlatformInstaller($this->io, $this->composer);

        $this->assertTrue($installer->supports('wp-platform'));
        $this->assertFalse($installer->supports('not-wp-platform'));
    }

    public function testDefaultInstallDirs()
    {
        $installer = new WPPlatformInstaller($this->io, $this->composer);
        $package   = new Package('howtoadhd/test-package', '1.0.0.0', '1.0.0');

        $this->assertEquals('wp-content/platform', $installer->getInstallPath($package));
        $this->assertEquals('wp-content', $installer->getContentPath());
    }

    public function testCustomContentDir()
    {
        $rootPackage = new RootPackage('test/root-package', '1.0.1.0', '1.0.1');
        $this->composer->setPackage($rootPackage);
        $rootPackage->setExtra(
            [
                'wp-content-dir' => 'not-content',
            ]
        );
        $installer = new WPPlatformInstaller($this->io, $this->composer);
        $package   = new Package('howtoadhd/test-package', '1.0.0.0', '1.0.0');

        $this->assertEquals('not-content/platform', $installer->getInstallPath($package));
        $this->assertEquals('not-content', $installer->getContentPath());
    }

    public function testSingleRootInstallDir()
    {
        $rootPackage = new RootPackage('test/root-package', '1.0.1.0', '1.0.1');
        $this->composer->setPackage($rootPackage);
        $installDir = 'tmp-platform-' . rand(0, 9);
        $rootPackage->setExtra(
            [
                'wp-platform-dir' => $installDir,
            ]
        );
        $installer = new WPPlatformInstaller($this->io, $this->composer);

        $this->assertEquals(
            $installDir,
            $installer->getInstallPath(
                new Package('not/important', '1.0.0.0', '1.0.0')
            )
        );
    }

    public function testArrayOfInstallDirs()
    {
        $rootPackage = new RootPackage('test/root-package', '1.0.1.0', '1.0.1');
        $this->composer->setPackage($rootPackage);
        $rootPackage->setExtra(
            [
                'wp-platform-dir' => [
                    'test/package-one' => 'install-dir/one',
                    'test/package-two' => 'install-dir/two',
                ],
            ]
        );
        $installer = new WPPlatformInstaller($this->io, $this->composer);

        $this->assertEquals(
            'install-dir/one',
            $installer->getInstallPath(
                new Package('test/package-one', '1.0.0.0', '1.0.0')
            )
        );

        $this->assertEquals(
            'install-dir/two',
            $installer->getInstallPath(
                new Package('test/package-two', '1.0.0.0', '1.0.0')
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Two packages (test/bazbat and test/foobar) cannot share the same directory!
     */
    public function testTwoPackagesCannotShareDirectory()
    {
        $installer = new WPPlatformInstaller($this->io, $this->composer);
        $package1  = new Package('test/foobar', '1.1.1.1', '1.1.1.1');
        $package2  = new Package('test/bazbat', '1.1.1.1', '1.1.1.1');

        $installer->getInstallPath($package1);
        $installer->getInstallPath($package2);
    }

    /**
     * @dataProvider                   dataProviderSensitiveDirectories
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Warning! .+? is an invalid install directory \(from test\/package\)!/
     */
    public function testSensitiveInstallDirectoriesNotAllowed($directory)
    {
        $rootPackage = new RootPackage('test/root-package', '1.0.1.0', '1.0.1');
        $this->composer->setPackage($rootPackage);
        $rootPackage->setExtra(['wp-platform-dir' => $directory]);
        $installer = new WPPlatformInstaller($this->io, $this->composer);
        $package   = new Package('test/package', '1.1.0.0', '1.1');
        $installer->getInstallPath($package);
    }

    public function dataProviderSensitiveDirectories()
    {
        return [
            ['.'],
            ['vendor'],
        ];
    }
}
