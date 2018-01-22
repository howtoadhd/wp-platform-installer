<?php

namespace HowToADHD\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;

class WPPlatformPlugin implements PluginInterface {

	/**
	 * Composer Filesystem
	 *
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * Apply plugin modifications to composer
	 *
	 * @param Composer $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$this->io         = $io;
		$this->filesystem = new Filesystem();

		$installer = new WPPlatformInstaller( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
		$installer = new WordPressCoreInstaller( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
	}
}
