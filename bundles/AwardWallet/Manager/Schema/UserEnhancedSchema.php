<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Scanner\MailboxFinder;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\PageRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserEnhancedSchema implements ActionInterface
{
    private MailboxFinder $mailboxFinder;

    private UsrRepository $userRep;

    public function __construct(MailboxFinder $mailboxFinder, UsrRepository $userRep)
    {
        $this->mailboxFinder = $mailboxFinder;
        $this->userRep = $userRep;
    }

    public static function getSchema(): string
    {
        return 'UserAdmin';
    }

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response
    {
        switch ($actionName) {
            case 'mailbox-list':
                $userId = $request->get('userId');

                if (empty($userId) || !is_numeric($userId)) {
                    throw new BadRequestHttpException('userId is required');
                }

                $user = $this->userRep->find($userId);

                if (!$user) {
                    throw new NotFoundHttpException('User not found');
                }

                return new JsonResponse([
                    'mailboxes' => $this->mailboxFinder->findAllEmailAddressesByUser($user),
                ]);

            default:
                throw new NotFoundHttpException();
        }
    }
}
