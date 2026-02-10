<?php

namespace AwardWallet\MainBundle\Globals\ApiVersioning;

use Herrera\Version\Exception\InvalidStringRepresentationException;
use Herrera\Version\Parser;
use Herrera\Version\Version;

class ApiVersioningService
{
    /**
     * @var VersionsProviderInterface
     */
    private $versionsProvider;

    /**
     * @var Version
     */
    private $version;

    /**
     * @var array[string => string[]]
     */
    private $versionCache;

    /**
     * @return $this
     */
    public function setVersionsProvider(?VersionsProviderInterface $versions = null)
    {
        $this->versionsProvider = $versions;

        return $this;
    }

    /**
     * @return $this
     */
    public function setVersion(?Version $version = null)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string|int $feature
     * @return bool
     */
    public function supports($feature)
    {
        if (null === $this->version) {
            return false;
        }

        return $this->versionSupports($this->version, $feature);
    }

    public function notSupports(string $feature): bool
    {
        return !$this->supports($feature);
    }

    /**
     * @param string[] $features
     * @return bool
     */
    public function supportsAll(array $features)
    {
        foreach ($features as $feature) {
            if (!$this->supports($feature)) {
                return false;
            }
        }

        return true;
    }

    public function versionSupports(Version $targetVersion, $feature)
    {
        if (null === $this->versionsProvider) {
            return false;
        }

        if (!isset($this->versionCache)) {
            $this->versionCache = [];
        }

        $targetMajor = $targetVersion->getMajor();
        $targetMinor = $targetVersion->getMinor();
        $targetPatch = $targetVersion->getPatch();

        if (isset($this->versionCache[$normalizedVersionKey = "{$targetMajor}.{$targetMinor}.{$targetPatch}"])) {
            return isset($this->versionCache[$normalizedVersionKey][$feature]);
        }

        try {
            $versions = $this->versionsProvider->getVersions();
        } catch (InvalidStringRepresentationException $e) {
            $this->versionsProvider = null;

            return false;
        }

        $versionFound = false;

        for ($i = count($versions) - 1; $i >= 0; $i--) {
            /** @var Version $version */
            $version = $versions[$i][0];

            if ($version->getMajor() === $targetMajor
                && $version->getMinor() === $targetMinor
                && ($targetPatch >= $version->getPatch())
            ) {
                $versionFound = true;
                $features = array_fill_keys($versions[$i][1], true);
                $this->versionCache[$normalizedVersionKey] = $features;

                return isset($features[$feature]);
            }
        }

        if (!$versionFound) {
            $this->versionCache[$normalizedVersionKey] = false;
        }

        return false;
    }

    public function versionStringSupports($version, $feature)
    {
        try {
            return $this->versionSupports(Parser::toVersion($version), $feature);
        } catch (InvalidStringRepresentationException $e) {
            return false;
        }
    }
}
