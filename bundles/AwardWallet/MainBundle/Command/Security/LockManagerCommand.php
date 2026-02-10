<?php

namespace AwardWallet\MainBundle\Command\Security;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ManagerThrottlerListener\ManagerLocker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LockManagerCommand extends Command
{
    protected static $defaultName = 'aw:lock-manager';
    private UsrRepository $usrRepository;
    private ManagerLocker $managerLocker;

    public function __construct(
        UsrRepository $usrRepository,
        ManagerLocker $managerLocker
    ) {
        parent::__construct();
        $this->usrRepository = $usrRepository;
        $this->managerLocker = $managerLocker;
    }

    public function configure()
    {
        $this->setDescription('Lock manager');
        $this->addArgument('user', InputArgument::REQUIRED, 'userid or username');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userQuery = $input->getArgument('user');
        $user = $this->usrRepository->find($userQuery);

        if (!$user) {
            $user = $this->usrRepository->findOneBy(['login' => $userQuery]);
        }

        if (!$user) {
            throw new \RuntimeException("User $userQuery not found");
        }

        $this->managerLocker->lock($user->getId());
        $output->writeln(\sprintf("User %s (%d) was locked", $user->getLogin(), $user->getId()));

        return 0;
    }
}
