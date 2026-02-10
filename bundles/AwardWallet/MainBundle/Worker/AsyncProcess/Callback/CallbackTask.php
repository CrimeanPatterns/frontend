<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess\Callback;

use AwardWallet\MainBundle\Globals\StackTraceUtils;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Opis\Closure\ReflectionClosure;
use Opis\Closure\SerializableClosure;

class CallbackTask extends Task
{
    /**
     * @var string|SerializableClosure
     */
    public $callable;
    /**
     * @var array
     */
    public $arguments;
    /**
     * @var array
     */
    public $callSite;
    /**
     * @var string
     */
    public $closureScopeClass;
    /**
     * @var string
     */
    public $namespace;
    /**
     * @var string
     */
    public $fileName;
    /**
     * @var int
     */
    public $startLine;
    /**
     * @var int
     */
    public $endLine;

    /**
     * CallbackTask constructor.
     *
     * @param \Closure|callable $callable
     */
    public function __construct($callable, array $arguments = [], $requestId = null)
    {
        if (!isset($requestId)) {
            $requestId = StringHandler::getPseudoRandomString(40) . '_' . time();
        }

        parent::__construct(CallbackTaskExecutor::class, $requestId);

        if (is_object($callable) && $callable instanceof \Closure) {
            $wrapper = new SerializableClosure($callable, false);
            self::prepareReflector($reflector = $wrapper->getReflector());
            $this->closureScopeClass = ($reflClass = $reflector->getClosureScopeClass()) ? $reflClass->getName() : null;
            $this->namespace = $reflector->getNamespaceName();
            $this->startLine = $reflector->getStartLine();
            $this->endLine = $reflector->getEndLine();
            $this->fileName = $reflector->getFileName();
            $this->callable = $wrapper;
        } else {
            $this->callable = $callable;
        }

        $this->callSite = ($trace = StackTraceUtils::getFilteredStackTrace(['file', 'line', 'function'])) ? $trace[0] : [];
        $this->arguments = $arguments;
    }

    public function getHash()
    {
        $data = [
            'callSite' => $this->callSite,
            'arguments' => $this->arguments,
            'requestId' => $this->requestId,
            'closureScopeClass' => $this->closureScopeClass,
        ];

        if (is_object($this->callable) && $this->callable instanceof SerializableClosure) {
            $reflector = $this->callable->getReflector();
            $data['code'] = $reflector->getCode();
            $data['useVars'] = $reflector->getUseVariables();
        } else {
            $data['code'] = $this->callable;
        }

        return sha1(serialize($data));
    }

    public static function prepareReflector(ReflectionClosure $reflectionClosure)
    {
        $reflectionClosure->getCode();

        // disable $this serialization, as we bind $this to executor
        foreach (['isBindingRequired', 'isScopeRequired'] as $property) {
            $reflProperty = new \ReflectionProperty(ReflectionClosure::class, $property);
            $reflProperty->setAccessible(true);
            $reflProperty->setValue($reflectionClosure, false);
            $reflProperty->setAccessible(false);
        }
    }
}
