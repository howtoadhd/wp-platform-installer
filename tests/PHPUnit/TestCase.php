<?php

namespace Tests\HowToADHD\Composer\PHPUnit;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    protected $composer;
    protected $config;
    protected $rootDir;
    protected $vendorDir;
    protected $binDir;
    protected $dm;
    protected $io;
    protected $fs;

    protected function setUp()
    {
        $this->fs = new Filesystem;

        $this->composer = new Composer();
        $this->config   = new Config();
        $this->composer->setConfig($this->config);

        $this->rootDir   = $this->getUniqueTmpDirectory();
        $this->vendorDir = $this->rootDir . DIRECTORY_SEPARATOR . 'vendor';
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->binDir = $this->rootDir . DIRECTORY_SEPARATOR . 'bin';
        $this->ensureDirectoryExistsAndClear($this->binDir);

        $this->config->merge(array(
            'config' => array(
                'vendor-dir' => $this->vendorDir,
                'bin-dir'    => $this->binDir,
            ),
        ));

        $this->io = new NullIO();

        $this->resetInstallPaths();
    }

    protected function tearDown()
    {
        $this->fs->removeDirectory($this->rootDir);
        $this->resetInstallPaths();
    }

    public static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root     = sys_get_temp_dir();
        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));
            if (! file_exists($unique) && Silencer::call('mkdir', $unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);
        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    protected static function ensureDirectoryExistsAndClear($directory)
    {
        $fs = new Filesystem();
        if (is_dir($directory)) {
            $fs->removeDirectory($directory);
        }
        mkdir($directory, 0777, true);
    }

    private function resetInstallPaths()
    {
        $prop = new \ReflectionProperty('\HowToADHD\Composer\WPPlatformInstaller', 'installedPaths');
        $prop->setAccessible(true);
        $prop->setValue([]);
    }
}
