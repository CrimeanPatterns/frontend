<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Month;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GiveAwPlusController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_GIVEAWPLUS')")
     * @Route("/manager/give-awplus", name="aw_manager_giveawplus")
     */
    public function giveAwPlusAction(Request $request, EntityManagerInterface $entityManager, Manager $cartManager)
    {
        $builder = $this->createFormBuilder();

        $builder->add('UserID', TextType::class, [
            'label' => "User ID",
        ]);
        $builder->add('Period', ChoiceType::class, ["choices" => [
            "trial, 3 months" => AwPlusTrial::TYPE,
            "1 month" => AwPlus1Month::TYPE,
            "6 months" => AwPlus::TYPE,
            "1 Year" => AwPlus1Year::TYPE,
        ]]);
        $builder->add('Amount', IntegerType::class, ["label" => "How much \$ user paid"]);
        $builder->add('TransactionID', TextType::class, ["label" => "PayPal Transaction ID", "required" => false, 'attr' => ['maxlength' => 40]]);
        $builder->add('Comments', TextareaType::class, ["label" => "Comments", "required" => false, 'attr' => ['maxlength' => 250]]);
        $builder->add('DoNotSendUserMail', CheckboxType::class, [
            'label' => 'Do not send message to user',
            'required' => false,
            'attr' => ['checked' => 'checked'],
        ]);
        $data = [
            'UserID' => $request->query->get("UserID"),
            'Period' => AwPlusTrial::TYPE,
            'Amount' => null,
            'TransactionID' => null,
            'Comments' => null,
        ];

        $builder->add('submit', SubmitType::class, ['label' => 'Give AW Plus']);

        $builder->setData($data);

        $message = null;
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Usr $user */
            $user = $entityManager->getRepository(Usr::class)->find($data['UserID']);

            if (!empty($user)) {
                $cartManager->setUser($user);
                $cart = $cartManager->createNewCart();
                $cart->setUser($user);

                switch ($data['Period']) {
                    case AwPlusTrial::TYPE:
                        $item = new AwPlusTrial();

                        break;

                    case AwPlus1Month::TYPE:
                        $item = new AwPlus1Month();

                        break;

                    case AwPlus::TYPE:
                        $item = new AwPlus();

                        break;

                    case AwPlus1Year::TYPE:
                        $item = new AwPlus1Year();

                        break;

                    default:
                        throw new \InvalidArgumentException("Unknown type: " . $data['Period']);
                }
                $item->setPrice($data['Amount']);
                $cart->addItem($item);

                if (!empty($data['TransactionID'])) {
                    $cart->setBillingtransactionid($data['TransactionID']);
                }

                if (!empty($data['Comments'])) {
                    $cart->setComments($data['Comments']);
                }
                $cart->setPaymenttype(PAYMENTTYPE_CREDITCARD);
                $cartManager->markAsPayed(null, null, null, !empty($form->get('DoNotSendUserMail')->getData()));
                $message = "User " . $user->getFullName() . " got " . $item->getName();
            } else {
                $form->addError(new FormError("We could not find user '{$data['UserID']}'"));
            }
        }

        return $this->render("@AwardWalletMain/Manager/giveAWPlus.html.twig", [
            "form" => $form->createView(),
            "message" => $message,
        ]);
    }
}
