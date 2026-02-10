<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Timeline\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MembersDropdownController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/members/dropdown/timeline", name="aw_business_members_dropdown_timeline", options={"expose"=true})
     */
    public function getMembersTimelineAction(Request $request, Manager $manager, OwnerRepository $ownerRepository)
    {
        $user = $this->tokenStorage->getBusinessUser();
        $query = preg_replace('/[^A-Za-zА-Яа-я0-9]/', '', $request->query->get('q'));
        $query = trim(filter_var($query, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $designation = $request->query->getBoolean('add') ? OwnerRepository::FOR_ITINERARY_ASSIGNMENT : OwnerRepository::FOR_ITINERARY_VIEW;
        $owners = $ownerRepository->findAvailableOwners($designation, $user, $query);
        $additionalFields = [
            'trips' => function (Owner $owner) use ($manager) {
                return $manager->getSegmentCount($owner->getUser(), $owner->getFamilyMember());
            },
        ];
        $formattedMembers = $this->formatMembers($owners, $additionalFields);

        return $this->json($formattedMembers);
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/members/dropdown/accounts", name="aw_business_members_dropdown_accounts", options={"expose"=true})
     * @Route("/members/dropdown/spent-analysis", name="aw_business_members_dropdown_spent_analysis", options={"expose"=true})
     */
    public function getMembersAccountsAction(Request $request, OwnerRepository $ownerRepository, Counter $counter)
    {
        $user = $this->tokenStorage->getBusinessUser();
        $query = trim(filter_var($request->query->get('q'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $designation = $request->query->getBoolean('add') ? OwnerRepository::FOR_ACCOUNT_ASSIGNMENT : OwnerRepository::FOR_ACCOUNT_VIEW;
        $owners = $ownerRepository->findAvailableOwners($designation, $user, $query);
        $additionalFields = [
            'trips' => function (Owner $owner) use ($counter, $user) {
                $connection = $user->getConnectionWith($owner->getUser());

                if ($owner->isFamilyMember()) {
                    return $counter->getTotalAccounts($user->getId(), $owner->getFamilyMember()->getUseragentid());
                } elseif (null !== $connection) {
                    return $counter->getTotalAccounts($user->getId(), $connection->getUseragentid());
                } else {
                    return $counter->getTotalAccounts($user->getId());
                }
            },
            'forwardEmail' => function (Owner $owner) {
                return $owner->getItineraryForwardingEmail();
            },
        ];
        $formattedMembers = $this->formatMembers($owners, $additionalFields);

        return $this->json($formattedMembers);
    }

    /**
     * @param Owner[] $owners
     */
    private function formatMembers(array $owners, array $additionalFields): array
    {
        $user = $this->tokenStorage->getBusinessUser();

        $result = [];

        /** @var Owner $owner */
        foreach ($owners as $owner) {
            $connection = $user->getConnectionWith($owner->getUser());
            $formattedMember = [
                'value' => $owner->isFamilyMember() ? $owner->getFamilyMember()->getId() : (null === $connection ? 'my' : $connection->getId()),
                'label' => $owner->getFullName(),
                'extra' => $owner->isFamilyMember() ? $owner->getUser()->getFullName() : null,
                'email' => $owner->getEmail(),
            ];

            foreach ($additionalFields as $field => $closure) {
                $formattedMember[$field] = $closure($owner);
            }
            $result[] = $formattedMember;
        }

        return $result;
    }
}
