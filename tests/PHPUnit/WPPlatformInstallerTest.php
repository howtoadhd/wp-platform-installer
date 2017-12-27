<?php

namespace Tests\HowToADHD\Composer\PHPUnit;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use HowToADHD\Composer\WPPlatformInstaller;
use PHPUnit\Framework\TestCase;

class WPPlatformInstallerTest extends TestCase {

	protected function setUp() {
		$this->resetInstallPaths();
	}

	protected function tearDown() {
		$this->resetInstallPaths();
	}

	public function testSupports() {
		$installer = new WPPlatformInstaller( new NullIO(), $this->createComposer() );

		$this->assertTrue( $installer->supports( 'wp-platform' ) );
		$this->assertFalse( $installer->supports( 'not-wp-platform' ) );
	}

	public function testDefaultInstallDir() {
		$installer = new WPPlatformInstaller( new NullIO(), $this->createComposer() );
		$package   = new Package( 'howtoadhd/test-package', '1.0.0.0', '1.0.0' );

		$this->assertEquals( 'wp-content/platform', $installer->getInstallPath( $package ) );
	}

	public function testSingleRootInstallDir() {
		$composer    = $this->createComposer();
		$rootPackage = new RootPackage( 'test/root-package', '1.0.1.0', '1.0.1' );
		$composer->setPackage( $rootPackage );
		$installDir = 'tmp-wp-' . rand( 0, 9 );
		$rootPackage->setExtra( array(
			'wp-platform-install-dir' => $installDir,
		) );
		$installer = new WPPlatformInstaller( new NullIO(), $composer );

		$this->assertEquals(
			$installDir,
			$installer->getInstallPath(
				new Package( 'not/important', '1.0.0.0', '1.0.0' )
			)
		);
	}

	public function testArrayOfInstallDirs() {
		$composer    = $this->createComposer();
		$rootPackage = new RootPackage( 'test/root-package', '1.0.1.0', '1.0.1' );
		$composer->setPackage( $rootPackage );
		$rootPackage->setExtra( array(
			'wp-platform-install-dir' => array(
				'test/package-one' => 'install-dir/one',
				'test/package-two' => 'install-dir/two',
			),
		) );
		$installer = new WPPlatformInstaller( new NullIO(), $composer );

		$this->assertEquals(
			'install-dir/one',
			$installer->getInstallPath(
				new Package( 'test/package-one', '1.0.0.0', '1.0.0' )
			)
		);

		$this->assertEquals(
			'install-dir/two',
			$installer->getInstallPath(
				new Package( 'test/package-two', '1.0.0.0', '1.0.0' )
			)
		);
	}

	public function testCorePackageCanDefineInstallDirectory() {
		$installer = new WPPlatformInstaller( new NullIO(), $this->createComposer() );
		$package   = new Package( 'test/has-default-install-dir', '0.1.0.0', '0.1' );
		$package->setExtra( array(
			'wp-platform-install-dir' => 'not-wordpress',
		) );

		$this->assertEquals( 'not-wordpress', $installer->getInstallPath( $package ) );
	}

	public function testCorePackageDefaultDoesNotOverrideRootDirectoryDefinition() {
		$composer = $this->createComposer();
		$composer->setPackage( new RootPackage( 'test/root-package', '0.1.0.0', '0.1' ) );
		$composer->getPackage()->setExtra( array(
			'wp-platform-install-dir' => 'wp',
		) );
		$installer = new WPPlatformInstaller( new NullIO(), $composer );
		$package   = new Package( 'test/has-default-install-dir', '0.1.0.0', '0.1' );
		$package->setExtra( array(
			'wp-platform-install-dir' => 'not-wordpress',
		) );

		$this->assertEquals( 'wp', $installer->getInstallPath( $package ) );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Two packages (test/bazbat and test/foobar) cannot share the same directory!
	 */
	public function testTwoPackagesCannotShareDirectory() {
		$composer  = $this->createComposer();
		$installer = new WPPlatformInstaller( new NullIO(), $composer );
		$package1  = new Package( 'test/foobar', '1.1.1.1', '1.1.1.1' );
		$package2  = new Package( 'test/bazbat', '1.1.1.1', '1.1.1.1' );

		$installer->getInstallPath( $package1 );
		$installer->getInstallPath( $package2 );
	}

	/**
	 * @dataProvider                   dataProviderSensitiveDirectories
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /Warning! .+? is an invalid WP Platform install directory \(from test\/package\)!/
	 */
	public function testSensitiveInstallDirectoriesNotAllowed( $directory ) {
		$composer  = $this->createComposer();
		$installer = new WPPlatformInstaller( new NullIO(), $composer );
		$package   = new Package( 'test/package', '1.1.0.0', '1.1' );
		$package->setExtra( array( 'wp-platform-install-dir' => $directory ) );
		$installer->getInstallPath( $package );
	}

	public function dataProviderSensitiveDirectories() {
		return array(
			array( '.' ),
			array( 'vendor' ),
		);
	}

	private function resetInstallPaths() {
		$prop = new \ReflectionProperty( '\HowToADHD\Composer\WPPlatformInstaller', '_installedPaths' );
		$prop->setAccessible( true );
		$prop->setValue( array() );
	}

	/**
	 * @return Composer
	 */
	private function createComposer() {
		$composer = new Composer();
		$composer->setConfig( new Config() );

		return $composer;
	}

}
