<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Account;

/**
 * @NoDI()
 */
class AccountState
{
    public const SHARED_CONTEXT_KEY = '!';

    public const SHARED_STATE_ITINERARIES = 'itineraries';

    /**
     * @var Account
     */
    public $account;

    /**
     * Forced itinerary check.
     *
     * @var bool
     */
    public $checkIts;

    /**
     * @var array - ID of plugin to handle this account
     */
    private $plugins = [];

    /**
     * @var array
     */
    private $context = [];

    public function __construct(Account $account, $checkIts = false)
    {
        $this->account = $account;
        $this->checkIts = $checkIts;
    }

    public function getActivePlugin()
    {
        return count($this->plugins) ? $this->plugins[count($this->plugins) - 1] : null;
    }

    public function pushPlugin($plugin, $context = null)
    {
        $this->plugins[] = $plugin;

        if ($context) {
            $this->context[$plugin] = $context;
        }
    }

    public function popPlugin()
    {
        $plugin = array_pop($this->plugins);

        if ($plugin) {
            unset($this->context[$plugin]);
        }

        return $plugin;
    }

    public function setContext($state)
    {
        $activePlugin = $this->getActivePlugin();

        if ($activePlugin) {
            $this->context[$activePlugin] = $state;
        }
    }

    public function getContext()
    {
        $activePlugin = $this->getActivePlugin();

        if (empty($activePlugin)) {
            return null;
        }

        if (empty($this->context[$activePlugin])) {
            return null;
        } else {
            return $this->context[$activePlugin];
        }
    }

    public function getContextValue($key)
    {
        $context = $this->getContext();

        if ($context && is_array($context)) {
            if (array_key_exists($key, $context)) {
                return $context[$key];
            }
        }

        return null;
    }

    public function setContextValue($key, $value)
    {
        $context = $this->getContext();

        if ($context && is_array($context)) {
            $context[$key] = $value;
        } else {
            $context = [
                $key => $value,
            ];
        }
        $this->setContext($context);
    }

    public function getSharedValue($key)
    {
        if (empty($this->context[self::SHARED_CONTEXT_KEY])) {
            return null;
        }

        if (is_array($this->context[self::SHARED_CONTEXT_KEY])) {
            if (array_key_exists($key, $this->context[self::SHARED_CONTEXT_KEY])) {
                return $this->context[self::SHARED_CONTEXT_KEY][$key];
            }
        }

        return null;
    }

    public function setSharedValue($key, $value)
    {
        if (empty($this->context[self::SHARED_CONTEXT_KEY])) {
            $this->context[self::SHARED_CONTEXT_KEY] = [];
        }
        $this->context[self::SHARED_CONTEXT_KEY][$key] = $value;
    }

    public function saveState()
    {
        return [
            'plugins' => $this->plugins,
            'context' => \serialize($this->context),
            'checkIts' => $this->checkIts,
        ];
    }

    public function loadState($state)
    {
        $this->plugins = $state['plugins'];
        $this->context = \is_string($state['context']) ?
            \unserialize($state['context']) :
            $state['context'];
        $this->checkIts = $state['checkIts'];
    }
}
