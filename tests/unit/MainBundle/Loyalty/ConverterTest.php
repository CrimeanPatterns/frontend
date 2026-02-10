<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Saver;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Loyalty\HistoryState\HistoryStateBuilder;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Loyalty\Converter
 */
class ConverterTest extends Unit
{
    use ProphecyTrait;
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var Converter
     */
    private $converter;

    public function testPrepareCheckConfirmationRequest()
    {
        $request = $this->converter->prepareCheckConfirmationRequest(
            'testprovider',
            ['ConfNo' => 'TESTVALUE', 'LastName' => 'TESTNAME'],
            100
        );
        $this->assertInstanceOf(CheckConfirmationRequest::class, $request);
        $this->assertSame(100, $request->getUserId());
        $this->assertCount(2, $request->getFields());
        $this->assertSame('ConfNo', $request->getFields()[0]->getCode());
        $this->assertSame('TESTVALUE', $request->getFields()[0]->getValue());
        $this->assertSame('LastName', $request->getFields()[1]->getCode());
        $this->assertSame('TESTNAME', $request->getFields()[1]->getValue());
    }

    public function testPrepareCheckAccountRequest()
    {
        $converter = $this->getConverter();
        $request = $converter->prepareCheckAccountRequest($this->getAccount(), (new ConverterOptions())->setParseItineraries(true));

        $this->assertInstanceOf(CheckAccountRequest::class, $request);
        $this->assertTrue($request->isParsePastItineraries());
        $this->assertInstanceOf(UserData::class, $request->getUserdata());
        $this->assertTrue($request->getUserdata()->isCheckPastIts());

        $request = $converter->prepareCheckAccountRequest($this->getAccount(new \DateTime()), (new ConverterOptions())->setParseItineraries(true));
        $this->assertTrue($request->getParseitineraries());
        $this->assertFalse($request->isParsePastItineraries());
        $this->assertInstanceOf(UserData::class, $request->getUserdata());
        $this->assertFalse($request->getUserdata()->isCheckPastIts());

        $request = $converter->prepareCheckAccountRequest($this->getAccount(new \DateTime('-4 month')), (new ConverterOptions())->setParseItineraries(true));
        $this->assertTrue($request->isParsePastItineraries());
        $this->assertInstanceOf(UserData::class, $request->getUserdata());
        $this->assertTrue($request->getUserdata()->isCheckPastIts());

        $request = $converter->prepareCheckAccountRequest($this->getAccount(new \DateTime('-4 month')), (new ConverterOptions())->setParseItineraries(false));
        $this->assertFalse($request->isParsePastItineraries());
        $this->assertInstanceOf(UserData::class, $request->getUserdata());
        $this->assertFalse($request->getUserdata()->isCheckPastIts());
    }

    public function testPrepareCheckExtensionSupportPackageRequest()
    {
        $converter = $this->getConverter();
        /** @var Account[] $accounts */
        $accounts =
            (function () {
                $account1 = $this->prophesize(Account::class);
                $account1
                    ->getId()
                    ->willReturn(100500);
                $account1
                    ->getLogin()
                    ->willReturn('login_1');
                $account1
                    ->getLogin2()
                    ->willReturn('login2_1');
                $account1
                    ->getLogin3()
                    ->willReturn('login3_1');
                $account1
                    ->getProviderid()
                    ->willReturn((new Provider())->setCode('provider_1'));

                $account2 = $this->prophesize(Account::class);
                $account2
                    ->getId()
                    ->willReturn(100501);
                $account2
                    ->getLogin()
                    ->willReturn('login_2');
                $account2
                    ->getLogin2()
                    ->willReturn('login2_2');
                $account2
                    ->getLogin3()
                    ->willReturn('login3_2');
                $account2
                    ->getProviderid()
                    ->willReturn((new Provider())->setCode('provider_2'));

                return [$account1->reveal(), $account2->reveal()];
            })();
        $request = $converter->prepareExtensionCheckSupportPackageRequest($accounts, true, false);
        $this->assertEquals(2, \count($request->getPackage()));
        $this->assertTrue(
            it($request->getPackage())
            ->all(fn ($subRequest) => $subRequest instanceof CheckExtensionSupportRequest),
        );
        $this->assertEquals(
            [
                [
                    'id' => 100500,
                    'login' => 'login_1',
                    'login2' => 'login2_1',
                    'login3' => 'login3_1',
                    'provider' => 'provider_1',
                    'isMobile' => true,
                    'includeReadyProviders' => false,
                ],
                [
                    'id' => 100501,
                    'login' => 'login_2',
                    'login2' => 'login2_2',
                    'login3' => 'login3_2',
                    'provider' => 'provider_2',
                    'isMobile' => true,
                    'includeReadyProviders' => false,
                ],
            ],
            it($request->getPackage())
            ->map(fn (CheckExtensionSupportRequest $subRequest) => [
                'id' => $subRequest->getId(),
                'login' => $subRequest->getLogin(),
                'login2' => $subRequest->getLogin2(),
                'login3' => $subRequest->getLogin3(),
                'provider' => $subRequest->getProvider(),
                'isMobile' => $subRequest->isMobile(),
                'includeReadyProviders' => $subRequest->isIncludeReadyProviders(),
            ])
            ->toArray()
        );
    }

    protected function _before(): void
    {
        $logger = $this->makeEmpty(LoggerInterface::class);
        $serializer = $this->makeEmpty(SerializerInterface::class);
        $entityManager = $this->makeEmpty(EntityManagerInterface::class);
        $userRepository = $this->makeEmpty(UsrRepository::class);
        $userAgentRepository = $this->makeEmpty(UseragentRepository::class);
        $accountRepository = $this->makeEmpty(AccountRepository::class);
        $providerRepository = $this->makeEmpty(ProviderRepository::class);
        $accountProcessor = $this->makeEmpty(AccountProcessor::class);
        $itinerariesProcessor = $this->makeEmpty(ItinerariesProcessor::class);
        $tracker = $this->makeEmpty(ItineraryTracker::class);
        $scannerApi = $this->makeEmpty(EmailScannerApi::class);
        $dispatcher = $this->makeEmpty(EventDispatcherInterface::class);
        $this->converter = new Converter(
            $logger,
            $serializer,
            $entityManager,
            $userRepository,
            $userAgentRepository,
            $accountRepository,
            $providerRepository,
            $accountProcessor,
            $itinerariesProcessor,
            $tracker,
            $scannerApi,
            $dispatcher,
            '',
            '',
            '',
            $this->makeEmpty(HistoryStateBuilder::class),
            $this->makeEmpty(Saver::class),
            $this->makeEmpty(\Memcached::class)
        );
    }

    private function getConverter(): Converter
    {
        $logger = $this->makeEmpty(LoggerInterface::class);
        $serializer = $this->makeEmpty(SerializerInterface::class);
        $entityManager = $this->makeEmpty(EntityManagerInterface::class);
        $userRepository = $this->makeEmpty(UsrRepository::class);
        $userAgentRepository = $this->makeEmpty(UseragentRepository::class);
        $accountRepository = $this->makeEmpty(AccountRepository::class);
        $providerRepository = $this->makeEmpty(ProviderRepository::class);
        $accountProcessor = $this->makeEmpty(AccountProcessor::class);
        $itinerariesProcessor = $this->makeEmpty(ItinerariesProcessor::class);
        $tracker = $this->makeEmpty(ItineraryTracker::class);
        $scannerApi = $this->makeEmpty(EmailScannerApi::class);
        $dispatcher = $this->makeEmpty(EventDispatcherInterface::class);
        $memcached = $this->makeEmpty(\Memcached::class);

        return new Converter(
            $logger,
            $serializer,
            $entityManager,
            $userRepository,
            $userAgentRepository,
            $accountRepository,
            $providerRepository,
            $accountProcessor,
            $itinerariesProcessor,
            $tracker,
            $scannerApi,
            $dispatcher,
            '',
            '',
            '',
            $this->makeEmpty(HistoryStateBuilder::class),
            $this->makeEmpty(Saver::class),
            $memcached
        );
    }

    private function getAccount(?\DateTime $lastCheckPastItsDate = null): Account
    {
        $provider = $this->makeEmpty(Provider::class, [
            'getCacheVersion' => 1,
        ]);

        return $this->makeEmpty(Account::class, [
            'getAccountid' => 100,
            'getProviderid' => $provider,
            'getUser' => $this->makeEmpty(Usr::class),
            'getAnswers' => [],
            'getLastCheckPastItsDate' => $lastCheckPastItsDate ?? null,
        ]);
    }
}
