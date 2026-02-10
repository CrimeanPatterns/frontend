<?php

namespace AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass;

use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Autowire;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTaskExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Parameter;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Service;
use Doctrine\Common\Annotations\DocParser;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\ParserFactory;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CallbackTaskAutowirePass implements CompilerPassInterface
{
    private const CALLBACK_TASK_CREATION = '/\b(?:CallbackTask|Autowire)\b/ims';

    public function process(ContainerBuilder $builder)
    {
        $docParser = new DocParser();
        $docParser->setIgnoreNotImportedAnnotations(true);
        $docParser->setImports([
            'service' => Service::class,
            'parameter' => Parameter::class,
            'autowire' => Autowire::class,
        ]);
        $lexer = new Lexer();
        $factory = new ParserFactory();
        $parser = $factory->create(ParserFactory::PREFER_PHP7, $lexer);
        $projectDir = $builder->getParameter('kernel.project_dir');
        $dirs =
            it($builder->getParameter('callback_task.scan_dirs'))
            ->map(fn ($dir) => $projectDir . '/' . $dir)
            ->toArray();

        foreach ($this->allPhpFilesIter($dirs) as $phpFile) {
            $fileName = $phpFile->getPathname();
            // TODO: use cache here to avoid reading same php files over and over
            $phpFileCode = \file_get_contents($fileName);

            if (!\preg_match(self::CALLBACK_TASK_CREATION, $phpFileCode)) {
                continue;
            }

            $candidates = new \ArrayObject();
            $nodeTraverser = new NodeTraverser();
            $nameResolver = new NodeVisitor\NameResolver();
            $nodeTraverser->addVisitor($nameResolver);
            $ast = $parser->parse($phpFileCode);
            $nodeTraverser->traverse($ast);
            $nodeTraverser->removeVisitor($nameResolver);
            // find new CallbackTask(function() { ... }) calls
            $nodeTraverser->addVisitor(new class($docParser, $candidates) extends AbstractCallbackNodeVisitor {
                public function enterNode(Node $node)
                {
                    if (!$node instanceof Node\Expr\New_) {
                        return;
                    }

                    $class = $node->class;

                    if (!$node->args) {
                        return;
                    }

                    $name = null;

                    if (
                        $class instanceof Node\Stmt\ClassLike
                        && (null !== $class->name)
                    ) {
                        $name = $class->namespacedNam;
                    } elseif ($class instanceof Node\Name) {
                        $name = $class->toString();
                    }

                    if (CallbackTask::class !== $name) {
                        return;
                    }

                    $callback = $node->args[0]->value;

                    if (!$callback instanceof Node\FunctionLike) {
                        return;
                    }

                    $this->prepareCandidate($callback);
                }
            });
            // find /** @Autowire */ function() { ... }) definitions
            $nodeTraverser->addVisitor(new class($docParser, $candidates) extends AbstractCallbackNodeVisitor {
                public function enterNode(Node $node)
                {
                    if (!$node instanceof Node\FunctionLike) {
                        return;
                    }

                    $docBlock = $node->getDocComment();

                    if (!$docBlock) {
                        return;
                    }

                    $hasAutowire = false;
                    $annotations = $this->docParser->parse($docBlock->getText());

                    foreach ($annotations as $annotation) {
                        if ($annotation instanceof Autowire) {
                            $hasAutowire = true;

                            break;
                        }
                    }

                    if (!$hasAutowire) {
                        return;
                    }

                    $this->prepareCandidate($node);
                }
            });
            $nodeTraverser->traverse($ast);

            if (!$candidates->count()) {
                continue;
            }

            foreach ($candidates as $candidate) {
                $definition = new Definition(CallbackTaskServiceLocatorHolder::class);
                $serviceLocatorMap = [];
                $parametersMap = [];

                foreach ($candidate['params'] as $paramName => $param) {
                    if ($param['docBlockType'] instanceof Service) {
                        $serviceLocatorMap[$paramName] = new Reference($param['docBlockType']->getName());
                    } elseif ($param['docBlockType'] instanceof Parameter) {
                        $parametersMap[$paramName] = $builder->getParameter($param['docBlockType']->getName());
                    } elseif (null !== $param['type']) {
                        $serviceLocatorMap[$paramName] = new Reference($param['type']);
                    }
                }

                $callerId = self::createServiceId($fileName, $candidate['callbackStartLine']);
                $locatorRef = self::register($builder, $serviceLocatorMap, $callerId);
                $definition->addMethodCall('setParametersMap', [$parametersMap]);
                $definition->addTag('container.service_subscriber.locator', ['id' => (string) $locatorRef]);
                $definition->setPublic(true);
                $definition->setAutowired(true);
                $builder->setDefinition($callerId, $definition);
            }

            $definition->setBindings([
                ContainerInterface::class => new BoundArgument($locatorRef, false),
                PsrContainerInterface::class => new BoundArgument($locatorRef, false),
                ServiceProviderInterface::class => new BoundArgument($locatorRef, false),
            ] + $definition->getBindings());
        }
    }

    public static function createServiceId(string $filename, int $line): string
    {
        $normalizedFilename = \preg_replace('/[^a-z0-9_]+/i', '_', $filename);

        return "callback_task.autowire.{$normalizedFilename}.line_{$line}";
    }

    private static function register(ContainerBuilder $container, array $refMap, ?string $callerId = null): Reference
    {
        foreach ($refMap as $id => $ref) {
            if (!$ref instanceof Reference) {
                throw new InvalidArgumentException(sprintf('Invalid service locator definition: only services can be referenced, "%s" found for key "%s". Inject parameter values using constructors instead.', \is_object($ref) ? \get_class($ref) : \gettype($ref), $id));
            }
            $refMap[$id] = new ServiceClosureArgument($ref);
        }
        ksort($refMap);

        $locator = (new Definition(ServiceLocator::class))
            ->addArgument($refMap)
            ->setPublic(false)
            ->addTag('container.service_locator');

        if (null !== $callerId && $container->hasDefinition($callerId)) {
            $locator->setBindings($container->getDefinition($callerId)->getBindings());
        }

        if (!$container->hasDefinition($id = '.service_locator.' . ContainerBuilder::hash($locator) . "_" . $callerId)) {
            $container->setDefinition($id, $locator);
        }

        if (null !== $callerId) {
            $locatorId = $id;
            // Locators are shared when they hold the exact same list of factories;
            // to have them specialized per consumer service, we use a cloning factory
            // to derivate customized instances from the prototype one.
            $container->register($id .= '.' . $callerId, ServiceLocator::class)
                ->setPublic(false)
                ->setFactory([new Reference($locatorId), 'withContext'])
                ->addTag('container.service_locator_context', ['id' => $callerId])
                ->addArgument($callerId)
                ->addArgument(new Reference('service_container'));
        }

        return new Reference($id);
    }

    /**
     * @return iterable<\SplFileInfo>
     */
    private function allPhpFilesIter(array $phpFilesDirs): iterable
    {
        $finder = new Finder();
        $excludedFiles = [
            $this->getClassPath(CallbackTaskExecutor::class),
            $this->getClassPath(CallbackTask::class),
        ];

        return it(
            $finder->files()
            ->in($phpFilesDirs)
            ->name('*.php')
        )
            ->filter(static fn (\SplFileInfo $file) =>
                !\in_array($file->getPathname(), $excludedFiles, true)
            );
    }

    /**
     * @param class-string $className
     */
    private function getClassPath(string $className): string
    {
        return (new \ReflectionClass($className))->getFileName();
    }
}
