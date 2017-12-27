<?php

namespace HowToADHD\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class WPPlatformPlugin implements PluginInterface {

	/**
	 * Apply plugin modifications to composer
	 *
	 * @param Composer $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$installer = new WPPlatformInstaller( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
		$installer = new WordPressCoreInstaller( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
	}

}
