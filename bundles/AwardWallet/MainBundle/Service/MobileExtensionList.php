<?php

namespace AwardWallet\MainBundle\Service;

class MobileExtensionList
{
    private \Memcached $memcached;
    private string $projectDir;

    public function __construct(\Memcached $memcached, string $projectDir)
    {
        $this->memcached = $memcached;
        $this->projectDir = $projectDir;
    }

    public function getMobileExtensionsList(): array
    {
        $extensions = $this->memcached->get('extensionMobile');

        if ($extensions === false) {
            $extensions = [];

            $files = glob(realpath($this->projectDir) . '/engine/*/extensionMobile.js');

            foreach ($files as $filename) {
                $provider = basename(dirname($filename));
                $content = file_get_contents($filename);

                if (preg_match('/autologin\s*:/U', $content)) {
                    $extensions[] = $provider;
                }
            }

            $this->memcached->set('extensionMobile', $extensions, 2 * 60);
        }

        return $extensions;
    }
}
