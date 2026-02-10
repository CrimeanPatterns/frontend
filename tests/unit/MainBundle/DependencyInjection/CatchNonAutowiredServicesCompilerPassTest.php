<?php

namespace AwardWallet\Tests\Unit\MainBundle\DependencyInjection {
    use AwardWallet\MainBundle\DependencyInjection\CatchNonAutowiredServicesCompilerPass;
    use AwardWallet\Tests\Unit\BaseTest;
    use AwardWallet\Tests\Unit\MainBundle\DependencyInjection\Fixtures\Foo;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Definition;

    /**
     * @covers \AwardWallet\MainBundle\DependencyInjection\CatchNonAutowiredServicesCompilerPass
     * @group frontend-unit
     */
    class CatchNonAutowiredServicesCompilerPassTest extends BaseTest
    {
        /**
         * This test should throw on non-autowired service Foo that has required
         * argument (Bar) in its class constructor but this argument does not exists in definition.
         */
        public function testProcessWillThrow()
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Service "foo" has 1 required arguments in its constructor, but only 0 argument(s) provided');

            $containerBuilder = $this->createMock(ContainerBuilder::class);
            $definitions = $this->createMock(Definition::class);
            $definitions->expects($this->once())
                ->method('isAutowired')
                ->willReturn(false);
            $definitions->expects($this->once())
                ->method('getClass')
                ->willReturn(Foo::class);
            $definitions->expects($this->once())
                ->method('getArguments')
                ->willReturn([]);
            $containerBuilder->expects($this->once())
                ->method('getDefinitions')
                ->willReturn(['foo' => $definitions]);

            $compilerPass = new CatchNonAutowiredServicesCompilerPass();
            $compilerPass->process($containerBuilder);
        }
    }
}

namespace AwardWallet\Tests\Unit\MainBundle\DependencyInjection\Fixtures {
    class Foo
    {
        public function __construct(Bar $bar)
        {
        }
    }

    class Bar
    {
        public function __construct()
        {
        }
    }
}
