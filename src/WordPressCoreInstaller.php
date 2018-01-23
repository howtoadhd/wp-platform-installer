<?php

namespace HowToADHD\Composer;

class WordPressCoreInstaller extends BaseInstaller
{

    const TYPE = 'wordpress-core';

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInstallDir()
    {
        return 'wordpress';
    }
}
