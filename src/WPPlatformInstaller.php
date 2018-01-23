<?php

namespace HowToADHD\Composer;

use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class WPPlatformInstaller extends LibraryInstaller {

	const TYPE = 'wp-platform';

	const MESSAGE_CONFLICT = 'Two packages (%s and %s) cannot share the same directory!';
	const MESSAGE_SENSITIVE = 'Warning! %s is an invalid WP Platform install directory (from %s)!';

	private static $_installedPaths = array();

	private $sensitiveDirectories = array( '.' );

	/**
	 * {@inheritDoc}
	 */
	public function getInstallPath( PackageInterface $package ) {
		$installationDir = false;
		$prettyName      = $package->getPrettyName();
		if ( $this->composer->getPackage() ) {
			$topExtra = $this->composer->getPackage()->getExtra();
			if ( ! empty( $topExtra['wp-platform-dir'] ) ) {
				$installationDir = $topExtra['wp-platform-dir'];
				if ( is_array( $installationDir ) ) {
					$installationDir = empty( $installationDir[ $prettyName ] ) ? false : $installationDir[ $prettyName ];
				}
			}
		}
		$extra = $package->getExtra();
		if ( ! $installationDir && ! empty( $extra['wp-platform-dir'] ) ) {
			$installationDir = $extra['wp-platform-dir'];
		}
		if ( ! $installationDir ) {
			$installationDir = $this->getContentPath() . '/platform';
		}
		$vendorDir = $this->composer->getConfig()->get( 'vendor-dir', Config::RELATIVE_PATHS ) ?: 'vendor';
		if (
			in_array( $installationDir, $this->sensitiveDirectories ) ||
			( $installationDir === $vendorDir )
		) {
			throw new \InvalidArgumentException( $this->getSensitiveDirectoryMessage( $installationDir, $prettyName ) );
		}
		if (
			! empty( self::$_installedPaths[ $installationDir ] ) &&
			$prettyName !== self::$_installedPaths[ $installationDir ]
		) {
			$conflict_message = $this->getConflictMessage( $prettyName, self::$_installedPaths[ $installationDir ] );
			throw new \InvalidArgumentException( $conflict_message );
		}
		self::$_installedPaths[ $installationDir ] = $prettyName;

		return rtrim( $installationDir, '/\\' );
	}

	public function getContentPath() {
		if ( $this->composer->getPackage() ) {
			$topExtra = $this->composer->getPackage()->getExtra();
			if ( ! empty( $topExtra['wp-content-dir'] ) ) {
				return rtrim( $topExtra['wp-content-dir'], '/\\' );
			}
		}

		return 'wp-content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function install( InstalledRepositoryInterface $repo, PackageInterface $package ) {
		parent::install( $repo, $package );
		$this->linkDropins( $package );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target ) {
		$this->unlinkDropins( $initial );
		parent::update( $repo, $initial, $target );
		$this->linkDropins( $target );
	}

	/**
	 * {@inheritDoc}
	 */
	public function uninstall( InstalledRepositoryInterface $repo, PackageInterface $package ) {
		$this->unlinkDropins( $package );
		parent::uninstall( $repo, $package );
	}

	/**
	 * Link dropins to wp-content.
	 *
	 * @param PackageInterface $package
	 */
	public function linkDropins( PackageInterface $package ) {
		$content_dir = realpath( $this->getContentPath() );
		$dropins_dir = realpath( $this->getInstallPath( $package ) . '/dropins/' );

		// Link dropins not yet added.
		foreach ( new \DirectoryIterator( $dropins_dir ) as $dropin ) {
			if ( $dropin->isDot() ) {
				continue;
			}

			$src  = $content_dir . '/' . $dropin->getFilename();
			$dest = $dropins_dir . '/' . $dropin->getFilename();

			if ( file_exists( $src ) ) {
				$this->io->write( '<info>Unlinking ' . $this->filesystem->findShortestPath( $src, $dest ) . '</info>' );
				unlink( $src );
			}

			$this->io->write( '<info>Linking ' . $this->filesystem->findShortestPath( $src, $dest ) . '</info>' );
			$this->filesystem->relativeSymlink( $dest, $src );
		}
	}

	/**
	 * Unlink dropins from wp-content.
	 *
	 * @param PackageInterface $package
	 */
	public function unlinkDropins( PackageInterface $package ) {
		$content_dir = realpath( $this->getContentPath() );
		$dropins_dir = realpath( $this->getInstallPath( $package ) . '/dropins/' );

		// Link dropins not yet added.
		foreach ( new \DirectoryIterator( $dropins_dir ) as $dropin ) {
			if ( $dropin->isDot() ) {
				continue;
			}

			$src  = $content_dir . '/' . $dropin->getFilename();
			$dest = $dropins_dir . '/' . $dropin->getFilename();

			if ( file_exists( $src ) ) {
				$this->io->write( '<info>Unlinking ' . $this->filesystem->findShortestPath( $src, $dest ) . '</info>' );
				unlink( $src );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports( $packageType ) {
		return self::TYPE === $packageType;
	}

	/**
	 * Get the exception message with conflicting packages
	 *
	 * @param string $attempted
	 * @param string $alreadyExists
	 *
	 * @return string
	 */
	private function getConflictMessage( $attempted, $alreadyExists ) {
		return sprintf( self::MESSAGE_CONFLICT, $attempted, $alreadyExists );
	}

	/**
	 * Get the exception message for attempted sensitive directories
	 *
	 * @param string $attempted
	 * @param string $packageName
	 *
	 * @return string
	 */
	private function getSensitiveDirectoryMessage( $attempted, $packageName ) {
		return sprintf( self::MESSAGE_SENSITIVE, $attempted, $packageName );
	}

}
