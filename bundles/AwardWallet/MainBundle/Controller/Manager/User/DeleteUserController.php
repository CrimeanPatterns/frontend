<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Service\UserRemover;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DeleteUserController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_DELETE_USER')")
     * @Route("/manager/delete-user", name="aw_manager_delete_user")
     */
    public function deleteUserAction(
        Request $request,
        UserManager $userManager,
        UserRemover $userRemover
    ) {
        $builder = $this->createFormBuilder();

        $builder->add('UserID', TextType::class, [
            'label' => "User ID(s)",
        ]);
        $data = [
            'UserID' => $request->query->get("UserID"),
        ];

        $builder->add('Delete', SubmitType::class, ['label' => 'Delete User(s)', 'attr' => ['onclick' => 'return window.confirm("Are you sure, you want to delete this user(s)?")']]);
        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $users =
                    it(\explode(',', $data["UserID"]))
                    ->map(fn (string $userId) => \trim($userId))
                    ->toArray();
                $removedCount = 0;

                foreach ($users as $user) {
                    try {
                        $user = $userManager->findUser($user, true);
                        $userRemover->deleteUser($user, "User deleted by manager " . $this->getUser()->getUsername());
                        $removedCount++;
                        $this->addFlash('success', "User '{$user->getUsername()}' ({$user->getId()}, {$user->getEmail()}) has been deleted successfully.");
                    } catch (AuthenticationException $e) {
                        $form->addError(new FormError("We could not find user '{$user}'"));
                    }
                }

                $showFormWithFlashes = $request->query->has('BackTo') || (\count($form->getErrors()) > 0);
                $willRedirect =
                    $showFormWithFlashes
                    && ($request->query->has('BackTo') && (\count($form->getErrors()) === 0));
                $this->addFlash('success',
                    "Total {$removedCount} user(s) deleted successfully."
                    . ($willRedirect ? " Redirecting in 5 seconds..." : "")
                );

                if ($showFormWithFlashes) {
                    return $this->render("@AwardWalletMain/Manager/deleteUser.html.twig", [
                        "form" => $form->createView(),
                        'backTo' => $willRedirect ?
                            \urlPathAndQuery($request->query->get('BackTo'))
                            : null,
                    ]);
                }

                return new RedirectResponse($this->generateUrl("aw_manager_list", ["Schema" => "UserAdmin"]));
            } catch (AuthenticationException $e) {
                $form->addError(new FormError("We could not find user '{$data['UserID']}'"));
            }
        }

        return $this->render("@AwardWalletMain/Manager/deleteUser.html.twig", [
            "form" => $form->createView(),
        ]);
    }
}
