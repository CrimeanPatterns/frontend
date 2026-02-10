<?php

namespace AwardWallet\MainBundle\Controller\Manager\Loyalty;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Resources\PasswordRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/loyalty")
 */
class PasswordRequestController extends AbstractController
{
    private ApiCommunicator $awCommunicator;

    public function __construct(ApiCommunicator $awCommunicator)
    {
        $this->awCommunicator = $awCommunicator;
    }

    /**
     * @Route("/password-request/list", name="aw_manager_loyalty_password_request")
     * @Security("is_granted('ROLE_MANAGE_WSDLPASSWORDREQUEST')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordRequestList(Request $request)
    {
        $data = [];

        try {
            $data = $this->awCommunicator->passwordRequestList();
            $data = json_decode($data, true);
        } catch (ApiCommunicatorException $e) {
        }

        $resultMessage = $request->query->get('resultMessage', null);

        if (!empty($resultMessage)) {
            $data['resultMessage'] = base64_decode($resultMessage);
        }

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/password-request-list.html.twig', $data);
    }

    /**
     * @Route("/password-request/remove", name="aw_manager_loyalty_password_request_remove")
     * @Security("is_granted('ROLE_MANAGE_WSDLPASSWORDREQUEST')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordRequestRemove(Request $request)
    {
        $id = $request->query->get('id', null);

        if (!empty($id)) {
            try {
                $data = $this->awCommunicator->passwordRequestRemove($id);
                $resultMessage = $data;
                $data = json_decode($data, true);
            } catch (ApiCommunicatorException $e) {
                $resultMessage = $e->getMessage();
            }
        }

        return $this->redirectToRoute('aw_manager_loyalty_password_request', ['resultMessage' => base64_encode($resultMessage)]);
    }

    /**
     * @Route("/password-request/edit", name="aw_manager_loyalty_password_request_form")
     * @Security("is_granted('ROLE_MANAGE_WSDLPASSWORDREQUEST')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordRequestForm(Request $request)
    {
        $id = $request->query->get('id', null);
        $layoutData = [];

        $form = $this->createFormBuilder()
            ->add('partner', TextType::class, ['required' => true, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('provider', TextType::class, ['required' => true, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('login', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->add('note', TextType::class, ['required' => false, 'attr' => ["onClick" => "$(this).select();"]])
            ->getForm();

        if ($request->isMethod('POST') && $params = $request->request->get('form')) {
            $form->setData($params);
            $passwordRequest = (new PasswordRequest())->setPartner($params['partner'])
                ->setLogin(trim($params['login']) !== '' ? $params['login'] : null)
                ->setProvider($params['provider'])
                ->setNote(trim($params['note']) !== '' ? $params['note'] : null)
                ->setUserId($this->getUser()->getUserid());

            try {
                if (empty($id)) {
                    $result = $this->awCommunicator->passwordRequest($passwordRequest);
                } else {
                    $result = $this->awCommunicator->passwordRequestEdit($id, $passwordRequest);
                }

                return $this->redirectToRoute('aw_manager_loyalty_password_request', ['resultMessage' => base64_encode($result)]);
            } catch (ApiCommunicatorException $e) {
                $layoutData['resultMessage'] = $e->getMessage();
            }
        }

        if (!empty($id)) {
            try {
                $data = $this->awCommunicator->passwordRequestList($id);
                $data = json_decode($data, true);
                $form->setData($data['item']);
            } catch (ApiCommunicatorException $e) {
                $layoutData['resultMessage'] = $e->getMessage();
            }
        }

        $layoutData['form'] = $form->createView();

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/password-request-form.html.twig', $layoutData);
    }
}
