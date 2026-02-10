<?php

namespace AwardWallet\Tests\Unit\Booking;

use AwardWallet\MainBundle\Email\BookerMailImporter;
use AwardWallet\Tests\Unit\BaseTest;
use Codeception\Util\Stub;

/**
 * @group frontend-unit
 */
class BookerMailImporterTest extends BaseTest
{
    /**
     * @var \AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository
     */
    protected $repo;
    /**
     * @var \AwardWallet\MainBundle\Email\BookerMailImporter
     */
    protected $importer;
    /**
     * @var \AwardWallet\MainBundle\Entity\AbRequest
     */
    protected $request;

    public function _before()
    {
        /** @var \Codeception\Module\Symfony2 $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $symfony2->_getContainer();
        $this->importer = $container->get(BookerMailImporter::class);
        $em = $container->get('doctrine.orm.entity_manager');
        $this->repo = $em->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class);
        /** @var \Codeception\Module\Aw $aw */
        $aw = $this->getModule('Aw');
        $this->request = $em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($aw->createAbRequest());
    }

    public function testValid()
    {
        $email = $this->request->getMainContactEmail();
        $email = str_replace('@', '+plus@', $email);

        $parser = $this->loadEmail(
            'thanks',
            [
                '4934' => $this->request->getAbRequestID(),
                'vsilantyev@gmail.com' => $email,
                '22 May 2014' => date("d M Y", time() - SECONDS_PER_DAY),
            ]
        );

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_IMPORTED, $this->importer->importMessage($parser));
        $searchCriteria = ['RequestID' => $this->request, 'ImapMessageID' => $parser->getHeader('Message-ID')];
        $message = $this->repo->findOneBy($searchCriteria);
        $this->assertEquals("Спасибо, отлично<br />\n<br />\nThanks,<br />\nVladimir", $message->getPost());

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_ALREADY_IMPORTED, $this->importer->importMessage($parser));
    }

    public function testMultiLine()
    {
        $email = $this->request->getMainContactEmail();
        $parser = $this->loadEmail(
            'multiLine',
            [
                '5798' => $this->request->getAbRequestID(),
                'veresch@gmail.com' => $email,
                '5 Aug 2014' => date("d M Y", time() - SECONDS_PER_DAY),
            ]
        );

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_IMPORTED, $this->importer->importMessage($parser));
        $searchCriteria = ['RequestID' => $this->request, 'ImapMessageID' => $parser->getHeader('Message-ID')];
        $message = $this->repo->findOneBy($searchCriteria);
        $this->assertEquals("Test reply from email...<br />\n<br />\n-Alexi", $message->getPost());
    }

    public function testMultiEmails()
    {
        $this->request->setContactEmail('test@gmail.com, test2@gmail.com');
        $email = 'test2@gmail.com';
        $parser = $this->loadEmail(
            'multiLine',
            [
                '5798' => $this->request->getAbRequestID(),
                'veresch@gmail.com' => $email,
                '5 Aug 2014' => date("d M Y", time() - SECONDS_PER_DAY),
            ]
        );

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_IMPORTED, $this->importer->importMessage($parser));
        $searchCriteria = ['RequestID' => $this->request, 'ImapMessageID' => $parser->getHeader('Message-ID')];
        $message = $this->repo->findOneBy($searchCriteria);
        $this->assertEquals("Test reply from email...<br />\n<br />\n-Alexi", $message->getPost());
    }

    public function testMultiLine2()
    {
        $email = $this->request->getMainContactEmail();
        $parser = $this->loadEmail(
            'multiLine2',
            [
                '5798' => $this->request->getAbRequestID(),
                'veresch@gmail.com' => $email,
                '11 Aug 2014' => date("d M Y", time() - SECONDS_PER_DAY),
            ]
        );

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_IMPORTED, $this->importer->importMessage($parser));
        $searchCriteria = ['RequestID' => $this->request, 'ImapMessageID' => $parser->getHeader('Message-ID')];
        $message = $this->repo->findOneBy($searchCriteria);
        $this->assertEquals("this is a test<br />\n<br />\nnew line<br />\n<br />\nnew line<br />\n<br />\n-Alexi", $message->getPost());
    }

    public function testQuote()
    {
        $email = $this->request->getMainContactEmail();
        $parser = $this->loadEmail(
            'quote',
            [
                '5798' => $this->request->getAbRequestID(),
                'veresch@gmail.com' => $email,
                '5 Aug 2014' => date("d M Y", time() - SECONDS_PER_DAY),
            ]
        );

        $this->assertEquals(\AwardWallet\MainBundle\Email\BookerMailImporter::RESULT_IMPORTED, $this->importer->importMessage($parser));
        $searchCriteria = ['RequestID' => $this->request, 'ImapMessageID' => $parser->getHeader('Message-ID')];
        $message = $this->repo->findOneBy($searchCriteria);
        $this->assertEquals("Thanks. Ready to book 2 tix. 2-5pm ET is good.", $message->getPost());
    }

    protected function _after()
    {
        $this->request =
        $this->repo =
        $this->importer = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }

    protected function loadEmail($name, array $replaces)
    {
        $email = file_get_contents(__DIR__ . '/../../_data/bookerEmails/' . $name . '.eml');

        foreach ($replaces as $search => $replace) {
            $email = str_replace($search, $replace, $email);
        }
        $parser = new \PlancakeEmailParser($email);

        return $parser;
    }
}
