<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Worker\LoyaltyCallbackWorker;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestLoyatySavingCommand extends Command
{
    protected static $defaultName = 'aw:test:loyalty-saving';

    private LoyaltyCallbackWorker $worker;

    public function __construct(
        LoyaltyCallbackWorker $worker
    ) {
        parent::__construct();
        $this->worker = $worker;
    }

    public function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'file with json data from loyalty, use https://loyalty.awardwallet.com/admin/account/xxx to get one')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $json = file_get_contents($input->getArgument('file'));

        if ($json === false) {
            throw new \Exception("failed to load " . $input->getArgument("file"));
        }

        $data = json_decode($json, true);
        $data = [
            'method' => 'account',
            'response' => [$data["response"]],
        ];
        $message = new AMQPMessage(json_encode($data));

        $channelMock = new class() {
            public function basic_ack()
            {
            }
        };
        $message->delivery_info['channel'] = $channelMock;
        $message->delivery_info['delivery_tag'] = 'xxx';

        $this->worker->execute($message);

        return 0;
    }
}
