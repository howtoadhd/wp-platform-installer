<?php

namespace HowToADHD\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

abstract class BaseInstaller extends LibraryInstaller
{

    const MESSAGE_CONFLICT  = 'Two packages (%s and %s) cannot share the same directory!';
    const MESSAGE_SENSITIVE = 'Warning! %s is an invalid install directory (from %s)!';

    protected static $installedPaths = [];

    protected $sensitiveDirectories = [ '.' ];

    private $extraKey;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        $type = 'library',
        Filesystem $filesystem = null,
        BinaryInstaller $binaryInstaller = null
    ) {
        $this->extraKey = static::TYPE . '-dir';
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installationDir = false;
        $prettyName      = $package->getPrettyName();
        if ($this->composer->getPackage()) {
            $topExtra = $this->composer->getPackage()->getExtra();
            if (! empty($topExtra[$this->extraKey])) {
                $installationDir = $topExtra[$this->extraKey];
                if (is_array($installationDir)) {
                    $installationDir = empty($installationDir[ $prettyName ]) ? false : $installationDir[ $prettyName ];
                }
            }
        }
        if (! $installationDir) {
            $installationDir = $this->getDefaultInstallDir();
        }
        $vendorDir = $this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS) ?: 'vendor';
        if (in_array($installationDir, $this->sensitiveDirectories) ||
            ( $installationDir === $vendorDir )
        ) {
            throw new \InvalidArgumentException($this->getSensitiveDirectoryMessage($installationDir, $prettyName));
        }
        if (! empty(self::$installedPaths[ $installationDir ]) &&
            $prettyName !== self::$installedPaths[ $installationDir ]
        ) {
            $conflict_message = $this->getConflictMessage($prettyName, self::$installedPaths[ $installationDir ]);
            throw new \InvalidArgumentException($conflict_message);
        }
        self::$installedPaths[ $installationDir ] = $prettyName;

        return $installationDir;
    }

    /**
     * Get the default directory to install into
     *
     * @return string
     */
    abstract protected function getDefaultInstallDir();

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return static::TYPE === $packageType;
    }

    /**
     * Get the exception message with conflicting packages
     *
     * @param string $attempted
     * @param string $alreadyExists
     *
     * @return string
     */
    protected function getConflictMessage($attempted, $alreadyExists)
    {
        return sprintf(self::MESSAGE_CONFLICT, $attempted, $alreadyExists);
    }

    /**
     * Get the exception message for attempted sensitive directories
     *
     * @param string $attempted
     * @param string $packageName
     *
     * @return string
     */
    protected function getSensitiveDirectoryMessage($attempted, $packageName)
    {
        return sprintf(self::MESSAGE_SENSITIVE, $attempted, $packageName);
    }
}
