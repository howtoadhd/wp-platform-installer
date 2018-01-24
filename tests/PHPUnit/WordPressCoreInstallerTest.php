<?php

namespace Tests\HowToADHD\Composer\PHPUnit;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use HowToADHD\Composer\WordPressCoreInstaller;

class WordPressCoreInstallerTest extends TestCase
{

    public function testSupports()
    {
        $installer = new WordPressCoreInstaller($this->io, $this->composer);

        $this->assertTrue($installer->supports('wordpress-core'));
        $this->assertFalse($installer->supports('not-wordpress-core'));
    }

    public function testDefaultInstallDir()
    {
        $installer = new WordPressCoreInstaller($this->io, $this->composer);
        $package   = new Package('howtoadhd/test-package', '1.0.0.0', '1.0.0');

        $this->assertEquals('wordpress', $installer->getInstallPath($package));
    }

    public function testSingleRootInstallDir()
    {
        $rootPackage = new RootPackage('test/root-package', '1.0.1.0', '1.0.1');
        $this->composer->setPackage($rootPackage);
        $installDir = 'tmp-wp-' . rand(0, 9);
        $rootPackage->setExtra(
            [
                'wordpress-core-dir' => $installDir,
            ]
        );
        $installer = new WordPressCoreInstaller($this->io, $this->composer);

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
                'wordpress-core-dir' => [
                    'test/package-one' => 'install-dir/one',
                    'test/package-two' => 'install-dir/two',
                ],
            ]
        );
        $installer = new WordPressCoreInstaller($this->io, $this->composer);

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
        $installer = new WordPressCoreInstaller($this->io, $this->composer);
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
        $rootPackage->setExtra(['wordpress-core-dir' => $directory]);
        $installer = new WordPressCoreInstaller($this->io, $this->composer);
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
