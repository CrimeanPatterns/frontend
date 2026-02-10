<?php

namespace AwardWallet\MainBundle\Globals\AccountList;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MapperContext;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MapperInterface;

/**
 * @NoDI
 */
class AccountList implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var array список аккаунтов пользователя до форматирования
     */
    private array $rawAccounts;
    /**
     * @var ?MapperInterface
     */
    private $mapper;
    /**
     * @var MapperContext
     */
    private $mapperContext;

    public function __construct(MapperContext $mapperContext, ?MapperInterface $mapper = null)
    {
        $this->rawAccounts = $mapperContext->loaderContext->accounts;
        $this->mapperContext = $mapperContext;
        $this->mapper = $mapper;
    }

    public function offsetExists($offset)
    {
        return isset($this->mapperContext->loaderContext->accounts[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        } else {
            return $this->mapperContext->loaderContext->accounts[$offset] = $this->callback($offset, $this->mapperContext->loaderContext->accounts[$offset]);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->mapperContext->loaderContext->accounts[] = $value;
        } else {
            $this->mapperContext->loaderContext->accounts[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->mapperContext->loaderContext->accounts[$offset]);
    }

    public function count()
    {
        return \count($this->mapperContext->loaderContext->accounts);
    }

    public function getAccountsCount()
    {
        return $this->mapperContext->loaderContext->accountsCount;
    }

    public function getTotals()
    {
        $result = [
            'Details' => $this->mapperContext->loaderContext->totals,
            'All' => [
                "Accounts" => 0,
                "Points" => 0,
            ],
        ];

        foreach ($this->mapperContext->loaderContext->totals as $kind => $total) {
            $result['All']['Accounts'] += $total['Accounts'];
            $result['All']['Points'] += $total['Points'];
        }

        return $result;
    }

    public function getAccounts()
    {
        $options = $this->mapperContext->options;
        $skipOnFailure = $options->get(Options::OPTION_SKIP_ON_FAILURE, false);
        /** @var SafeExecutorFactory? $safeExecFactory */
        $safeExecFactory = $options->get(Options::OPTION_SAFE_EXECUTOR_FACTORY);
        $accounts = [];

        if ($skipOnFailure && ($safeExecFactory instanceof SafeExecutorFactory)) {
            foreach ($this->mapperContext->loaderContext->accounts as $id => $account) {
                $safeExecFactory(function () use ($account, $id, &$accounts) {
                    $accounts[$id] = $this->callback($id, $account);
                })
                ->run();
            }
        } else {
            foreach ($this->mapperContext->loaderContext->accounts as $id => $account) {
                $accounts[$id] = $this->callback($id, $account);
            }
        }

        $indexByHid = (bool) $options->get(Options::OPTION_INDEXED_BY_HID, false);
        $asObject = $options->get(Options::OPTION_AS_OBJECT, false);

        if (!$asObject && $indexByHid) {
            return $accounts;
        } elseif ($asObject || $indexByHid) {
            $newAccounts = [];

            foreach ($accounts as $idx => $account) {
                $newElement = $asObject ? (object) $account : $account;

                if ($indexByHid) {
                    $newAccounts[$idx] = $newElement;
                } else {
                    $newAccounts[] = $newElement;
                }
            }

            return $newAccounts;
        } else {
            return $accounts;
        }
    }

    public function getAccountsWithTotal()
    {
    }

    public function getMapperContext(): MapperContext
    {
        return $this->mapperContext;
    }

    public function getRawAccounts(): array
    {
        return $this->rawAccounts;
    }

    public function current()
    {
        $account = current($this->mapperContext->loaderContext->accounts);

        if ($account === false) {
            return false;
        }

        return $this->mapperContext->loaderContext->accounts[key($this->mapperContext->loaderContext->accounts)] = $this->callback(key($this->mapperContext->loaderContext->accounts), $account);
    }

    public function next()
    {
        next($this->mapperContext->loaderContext->accounts);
    }

    public function key()
    {
        return key($this->mapperContext->loaderContext->accounts);
    }

    public function valid()
    {
        return current($this->mapperContext->loaderContext->accounts) !== false;
    }

    public function rewind()
    {
        reset($this->mapperContext->loaderContext->accounts);
    }

    protected function callback($id, $account)
    {
        if (!$this->mapper) {
            return $account;
        }

        if ($account['_'] ?? false) {
            return $account;
        }

        $account['_'] = true;
        $account = $this->mapper->map(
            $this->mapperContext,
            $id,
            $account,
            [
                'accounts' => $this->mapperContext->loaderContext->accountsIds,
                'coupons' => $this->mapperContext->loaderContext->couponsIds,
            ]
        );

        return $account;
    }
}
