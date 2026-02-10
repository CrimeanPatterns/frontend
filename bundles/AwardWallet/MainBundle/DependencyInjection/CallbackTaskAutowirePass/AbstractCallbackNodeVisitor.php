<?php

namespace AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass;

use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Parameter;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Service;
use Doctrine\Common\Annotations\DocParser;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

abstract class AbstractCallbackNodeVisitor extends NodeVisitorAbstract
{
    protected DocParser $docParser;
    protected \ArrayObject $candidates;

    public function __construct(DocParser $docParser, \ArrayObject $candidates)
    {
        $this->docParser = $docParser;
        $this->candidates = $candidates;
    }

    protected function prepareCandidate(Node\FunctionLike $callback): void
    {
        if (isset($this->candidates[$callback->getStartLine()])) {
            return;
        }

        $callbackParams = $callback->getParams();
        $paramsTypes = [];

        foreach ($callbackParams as $param) {
            $paramType = null;

            if ($param->type) {
                $paramType = $param->type->toString();
            }

            $docBlock = $param->getDocComment();
            $docBlockType = null;

            if ($docBlock) {
                $annotations = $this->docParser->parse($docBlock->getText());

                foreach ($annotations as $annotation) {
                    if (
                        $annotation instanceof Service
                        || $annotation instanceof Parameter
                    ) {
                        $docBlockType = $annotation;

                        break;
                    }
                }
            }

            $paramsTypes[$param->var->name] = [
                'docBlockType' => $docBlockType,
                'type' => $paramType,
            ];
        }

        if ($paramsTypes) {
            $this->candidates[$callback->getStartLine()] = [
                'callbackStartLine' => $callback->getStartLine(),
                'params' => $paramsTypes,
            ];
        }
    }
}
