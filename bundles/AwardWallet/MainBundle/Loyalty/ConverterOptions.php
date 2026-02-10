<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 04.04.16
 * Time: 15:35.
 */

namespace AwardWallet\MainBundle\Loyalty;

class ConverterOptions
{
    private $parseHistory;
    private $parseItineraries;
    private $source;
    private bool $browserExtensionAllowed;

    public function __construct(?bool $parseHistory = null, ?bool $parseItineraries = null, ?int $source = null, bool $browserExtensionAllowed = false)
    {
        $this->parseHistory = $parseHistory;
        $this->parseItineraries = $parseItineraries;
        $this->source = $source;
        $this->browserExtensionAllowed = $browserExtensionAllowed;
    }

    /**
     * @return null
     */
    public function getParseHistory()
    {
        return $this->parseHistory;
    }

    /**
     * @return $this
     */
    public function setParseHistory($parseHistory)
    {
        $this->parseHistory = $parseHistory;

        return $this;
    }

    /**
     * @return null
     */
    public function getParseItineraries()
    {
        return $this->parseItineraries;
    }

    /**
     * @return $this
     */
    public function setParseItineraries($parseItineraries)
    {
        $this->parseItineraries = $parseItineraries;

        return $this;
    }

    /**
     * @param int $source one of UpdaterEngineInterface::SOURCE_ constants
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return int - one of UpdaterEngineInterface::SOURCE_ constants
     */
    public function getSource()
    {
        return $this->source;
    }

    public function isBrowserExtensionAllowed(): bool
    {
        return $this->browserExtensionAllowed;
    }

    public function setBrowserExtensionAllowed(bool $browserExtensionAllowed): self
    {
        $this->browserExtensionAllowed = $browserExtensionAllowed;

        return $this;
    }
}
