<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\FrameworkExtension\Twig;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Twig\Error\SyntaxError;

/**
 * @group frontend-functional
 */
class TemplateCacheWarmerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testTemplateError(\TestSymfonyGuy $I)
    {
        require_once __DIR__ . '/../../../../../app/AppKernel.php';
        $kernel = new \AppKernel("twig_syntax_error", false);
        $app = new Application($kernel);
        $command = $app->find('cache:warmup');
        $commandTester = new CommandTester($command);
        $I->expectThrowable(SyntaxError::class, function () use ($command, $commandTester) {
            $commandTester->execute(['command' => $command->getName()]);
        });
    }
}
