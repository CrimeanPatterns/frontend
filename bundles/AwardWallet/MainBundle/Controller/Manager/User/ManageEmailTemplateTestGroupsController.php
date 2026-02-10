<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ManageEmailTemplateTestGroupsController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_LIMITED_EDIT_USER')")
     * @Route("/manager/email-template-test-groups/{userid}", name="aw_manager_email_template_test_groups", requirements={"userid": "\d+"})
     * @ParamConverter("user", class="AwardWalletMainBundle:Usr")
     */
    public function emailTemplateTestGroupsAction(
        Request $request,
        Usr $user,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $form = $this->makeForm($user, $request);
        $form->handleRequest($request);
        $validGroupsList =
            it($form->get('test_groups')->getConfig()->getOption('choices'))
            ->values()
            ->toArray();

        if ($form->isSubmitted() && $form->isValid() && $form->isSynchronized()) {
            $groupsIdsMap = $connection->fetchAllKeyValue("select GroupName, SiteGroupID from SiteGroup where GroupName in (?)", [$validGroupsList], [Connection::PARAM_STR_ARRAY]);
            $data = $form->getData();
            $testGroups = $data['test_groups'] ?? [];

            foreach ($validGroupsList as $group) {
                if (\in_array($group, $testGroups, true)) {
                    $logger->info("User {$user->getId()} included to {$group}");
                    $connection->executeStatement("insert ignore into GroupUserLink(UserID, SiteGroupID) values(?, ?)", [$user->getId(), $groupsIdsMap[$group]]);
                } else {
                    $logger->info("User {$user->getId()} excluded from {$group}");
                    $connection->executeStatement("delete from GroupUserLink where UserID = ? and SiteGroupID = ?", [$user->getId(), $groupsIdsMap[$group]]);
                }
            }

            if ($data['referer']) {
                return $this->redirect(urlPathAndQuery($data['referer']));
            }
        }

        return $this->render("@AwardWalletMain/Manager/User/email_template_test_groups.html.twig", [
            "form" => $form->createView(),
            "userId" => $user->getId(),
        ]);
    }

    private function makeForm(Usr $user, Request $request): FormInterface
    {
        $builder = $this->createFormBuilder();
        $builder->add('test_groups', ChoiceType::class, [
            'label' => "Email template groups:",
            'choices' => [
                'Early & Full supporters 3 months upgrade' => Sitegroup::TEST_SUPPORTER_3M_UPGRADE,
                'VIP Full supporter' => Sitegroup::TEST_VIP_FULL_SUPPORTER,
            ],
            'multiple' => true,
            'expanded' => true,
            'required' => true,
        ]);
        $builder->add('referer', HiddenType::class);
        $data = [
            'test_groups' =>
                it($builder->get('test_groups')->getFormConfig()->getOption('choices'))
                ->filterIndexed(fn (string $choice) => $user->hasRole("ROLE_{$choice}"))
                ->toArray(),
            'referer' => $request->headers->get('referer') ? urlPathAndQuery($request->headers->get('referer')) : null,
        ];

        $builder->add('Save', SubmitType::class);
        $builder->setData($data);

        return $builder->getForm();
    }
}
