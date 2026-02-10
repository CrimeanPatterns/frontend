<?php

namespace AwardWallet\MainBundle\Globals;

use Opis\Closure\ReflectionClosure;

class StaticClosureCodeExtractor extends ReflectionClosure
{
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var int
     */
    protected $startLine;
    /**
     * @var int
     */
    protected $endLine;
    /**
     * @var string
     */
    protected $classScope;
    /**
     * @var string
     */
    protected $namespace;

    /**
     * StaticClosureCodeExtractor constructor.
     *
     * @param string $fileName
     * @param int $startLine
     * @param int $endLine
     * @param string|null $classScope
     * @param string|null $namespace
     */
    public function __construct($fileName, $startLine, $endLine, $classScope = null, $namespace = null)
    {
        $this->fileName = $fileName;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->classScope = $classScope;
        $this->namespace = $namespace;

        parent::__construct(function () {}, null);
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getStartLine()
    {
        return $this->startLine;
    }

    public function getEndLine()
    {
        return $this->endLine;
    }

    public function getClosureScopeClass()
    {
        return isset($this->classScope) ?
            new \ReflectionClass($this->classScope) :
            null;
    }

    public function getNamespaceName()
    {
        return $this->namespace;
    }

    public function getStaticVariables()
    {
        return [];
    }
}
