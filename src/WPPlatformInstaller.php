<?php

namespace HowToADHD\Composer;

use Composer\Config;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class WPPlatformInstaller extends BaseInstaller
{

    const TYPE = 'wp-platform';

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInstallDir()
    {
        return $this->getContentPath() . '/platform';
    }

    /**
     * Calculate path to wp-content
     *
     * @return string
     */
    public function getContentPath()
    {
        if ($this->composer->getPackage()) {
            $topExtra = $this->composer->getPackage()->getExtra();
            if (! empty($topExtra['wp-content-dir'])) {
                return rtrim($topExtra['wp-content-dir'], '/\\');
            }
        }

        return 'wp-content';
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->linkDropins($package);
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->unlinkDropins($initial);
        parent::update($repo, $initial, $target);
        $this->linkDropins($target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->unlinkDropins($package);
        parent::uninstall($repo, $package);
    }

    /**
     * Link dropins to wp-content.
     *
     * @param PackageInterface $package
     */
    public function linkDropins(PackageInterface $package)
    {
        $this->filesystem->ensureDirectoryExists($this->getContentPath());

        $content_dir = realpath($this->getContentPath());
        $dropins_dir = realpath($this->getInstallPath($package) . '/dropins/');

        // Link dropins not yet added.
        foreach (new \DirectoryIterator($dropins_dir) as $dropin) {
            if ($dropin->isDot()) {
                continue;
            }

            $src  = $content_dir . '/' . $dropin->getFilename();
            $dest = $dropins_dir . '/' . $dropin->getFilename();

            if (file_exists($src)) {
                $this->io->write('<info>Unlinking ' . $this->filesystem->findShortestPath($src, $dest) . '</info>');
                unlink($src);
            }

            $this->io->write('<info>Linking ' . $this->filesystem->findShortestPath($src, $dest) . '</info>');
            $this->filesystem->relativeSymlink($dest, $src);
        }
    }

    /**
     * Unlink dropins from wp-content.
     *
     * @param PackageInterface $package
     */
    public function unlinkDropins(PackageInterface $package)
    {
        $content_dir = realpath($this->getContentPath());
        $dropins_dir = realpath($this->getInstallPath($package) . '/dropins/');

        // Link dropins not yet added.
        foreach (new \DirectoryIterator($dropins_dir) as $dropin) {
            if ($dropin->isDot()) {
                continue;
            }

            $src  = $content_dir . '/' . $dropin->getFilename();
            $dest = $dropins_dir . '/' . $dropin->getFilename();

            if (file_exists($src)) {
                $this->io->write('<info>Unlinking ' . $this->filesystem->findShortestPath($src, $dest) . '</info>');
                unlink($src);
            }
        }
    }
}
