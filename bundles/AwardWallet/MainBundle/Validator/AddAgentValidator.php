<?php

namespace AwardWallet\MainBundle\Validator;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\AddAgentModel;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddAgentValidator implements TranslationContainerInterface
{
    private UseragentRepository $repository;
    private TranslatorInterface $translator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private PropertyAccessorInterface $propertyAccessor;
    private RouterInterface $router;

    public function __construct(
        UseragentRepository $repository,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        PropertyAccessorInterface $propertyAccessor,
        RouterInterface $router
    ) {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyAccessor = $propertyAccessor;
        $this->router = $router;
    }

    public function checkAgentExists(AddAgentModel $data): ?string
    {
        if (!$data->getFirstname() && !$data->getLastname()) {
            return null;
        }

        if (ConnectionManager::isEmailFromRestrictedDomain($data->getEmail())) {
            $fullName = (new Useragent())
                ->setFirstname($data->getFirstname())
                ->setLastname($data->getLastname())
                ->getFullName();

            return $this->translator->trans('user.already.registered', ['%name%' => $fullName], 'validators');
        }

        if ($this->repository->checkAgentExist($data->getInviter(), $data)) {
            // TODO: OMG! need another way to code reuse
            $fullName = (new Useragent())
                ->setFirstname($data->getFirstname())
                ->setLastname($data->getLastname())
                ->getFullName();

            /**
             * @Desc("User %name% already registered in your profile")
             */
            return $this->translator->trans('user.already.registered', ['%name%' => $fullName], 'validators');
        }

        return null;
    }

    public function checkAgentCount(AddAgentModel $data): ?string
    {
        global $eliteUsers;

        if (
            in_array($data->getInviter()->getUserid(), $eliteUsers, true)
            || $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
        ) {
            return null;
        }

        if (($count = $this->repository->getConnectedAgentsCount($data->getInviter()->getUserid())) >= PERSONAL_INTERFACE_MAX_USERS) {
            return $this->translator->trans('looks.business', ['%users%' => $count], 'validators');
        }

        return null;
    }

    public function validateEmail(AddAgentModel $data): ?string
    {
        if (
            !$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
            && $data->isInvite()
            && StringUtils::isEmpty($data->getEmail())
        ) {
            return $this->translator->trans('error.email-required', [], 'validators');
        }

        return null;
    }

    public function validateNamePart(AddAgentModel $data, ExecutionContextInterface $_context, string $errorPath): ?string
    {
        $origin = $this->propertyAccessor->getValue($data, $errorPath);
        $value = Useragent::cleanName($origin);

        if (empty($value)) {
            return $this->translator->trans(empty($origin) ? 'notblank' : 'pattern', [], 'validators');
        }

        return null;
    }

    public function checkInviterEmailVerified(AddAgentModel $data): ?string
    {
        $inviter = $data->getInviter();

        return ($inviter->getEmailverified() !== \EMAIL_VERIFIED) ?
            $this->translator->trans(
                'email.not_verified',
                $data->getPlatform() === AddAgentModel::PLATFORM_MOBILE ?
                    [
                        '%link_on%' => '',
                        '%link_off%' => '',
                    ] :
                    [
                        '%link_on%' => '<a target="_blank" href="' . $this->router->generate('aw_profile_overview') . '">',
                        '%link_off%' => '</a>',
                    ],
                'validators'
            ) :
            null;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('looks.business', 'validators'))->setDesc('You are using a personal AwardWallet interface for managing loyalty programs and it is not intended for business use. You can add up to %users% users to your profile.'),
        ];
    }
}
