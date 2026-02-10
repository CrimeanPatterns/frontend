<?php

namespace AwardWallet\MainBundle\DependencyInjection {
    use AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass\Id;
    use AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass\Prefix;
    use AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass\Regexp;
    use AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass\SearchInterface;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Definition;

    use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

    class PublicServiceCompilerPass implements CompilerPassInterface
    {
        /**
         * @var SearchInterface[]
         */
        private $queries = [];

        public function __construct()
        {
            $this->queries = [
                new Prefix('monolog.logger.'), // all controllers etc.
                new Id(LoggerInterface::class), // all controllers etc.
                new Id('mailer'), // all controllers etc.
                new Id('logger'), // all controllers etc.
                new Id('router'),
                new Id('request_stack'),
                new Id('http_kernel'),
                new Id('serializer'),
                new Id('session'),
                new Id('security.authorization_checker'),
                new Id('twig'),
                new Id('doctrine'),
                new Id('form.factory'),
                new Id('security.token_storage'),
                new Id('security.csrf.token_manager'),
            ];
        }

        public function process(ContainerBuilder $container)
        {
            [$ids, $pattern] = $this->prepareRegexp($this->queries);

            /** @var Id $id */
            foreach ($ids as $id) {
                $id = $id->getId();

                if ($container->hasAlias($id)) {
                    $container->getAlias($id)->setPublic(true);
                }

                if ($container->hasDefinition($id)) {
                    $container->getDefinition($id)->setPublic(true);
                }
            }

            if (isset($pattern)) {
                $this->makePublicByPattern($container, $pattern);
            }
        }

        private function makePublicByPattern(ContainerBuilder $container, string $pattern): void
        {
            /** @var Definition $definition */
            foreach (
                it($container->getAliases())
                ->chain($container->getDefinitions())
                ->filterIndexed(function ($_, string $name) use ($pattern) { return \preg_match($pattern, $name); }) as $definition
            ) {
                $definition->setPublic(true);
            }
        }

        /**
         * @param SearchInterface[] $queries
         */
        private function prepareRegexp(array $queries): array
        {
            [$ids, $matches] =
                it($queries)
                ->partition(function (SearchInterface $search) { return $search instanceof Id; });

            $matchPattern =
                it($matches)
                ->map(function (SearchInterface $search) {
                    if ($search instanceof Prefix) {
                        return '^' . \preg_quote($search->getPrefix(), '/');
                    } elseif ($search instanceof Regexp) {
                        return $search->getRegexp();
                    }

                    throw new \LogicException('Unknown class ' . \get_class($search));
                })
                ->joinToString('|');

            return [$ids->toArray(), ('' === $matchPattern) ? null : "/(?:{$matchPattern})/ims"];
        }
    }
}

namespace AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass {
    interface SearchInterface
    {
    }

    abstract class AbstractSearch implements SearchInterface
    {
    }

    class Prefix extends AbstractSearch
    {
        /**
         * @var string
         */
        private $prefix;

        public function __construct(string $prefix)
        {
            $this->prefix = $prefix;
        }

        public function getPrefix(): string
        {
            return $this->prefix;
        }
    }

    class Id extends AbstractSearch
    {
        /**
         * @var string
         */
        private $id;

        public function __construct(string $id)
        {
            $this->id = $id;
        }

        public function getId(): string
        {
            return $this->id;
        }
    }

    class Regexp extends AbstractSearch
    {
        /**
         * @var string
         */
        private $regexp;

        public function __construct(string $regexp)
        {
            $this->regexp = $regexp;
        }

        public function getRegexp(): string
        {
            return $this->regexp;
        }
    }
}
