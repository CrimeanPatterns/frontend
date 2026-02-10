<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\Profile\ProfileCouponResultType;
use AwardWallet\MobileBundle\Form\Type\Profile\ProfileCouponType;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use AwardWallet\MobileBundle\Form\View\Block\Paragraph;
use AwardWallet\MobileBundle\Form\View\Block\Table;
use AwardWallet\MobileBundle\Form\View\UrlTransformer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    protected ApiVersioningService $versioning;

    public function __construct(
        LocalizeService $localizeService,
        ApiVersioningService $apiVersioningService
    ) {
        $localizeService->setRegionalSettings();
        $this->versioning = $apiVersioningService;
    }

    /**
     * @Route("/useCoupon", name="aw_mobile_usecoupon", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @JsonDecode
     */
    public function useCouponAction(
        Request $request,
        Manager $manager,
        Handler $awFormProfileCouponHandlerMobile,
        TranslatorInterface $translator,
        UrlTransformer $awMobileUrlTransformer,
        FormDehydrator $formDehydrator
    ) {
        if (!$this->versioning->supports(MobileVersions::AWPLUS_VIA_COUPON_ROUTE)) {
            throw new NotFoundHttpException();
        }

        $cart = $manager->createNewCart();
        $code = $request->query->get('code');

        if (StringUtils::isNotEmpty($code)) {
            $request = $request->duplicate([], ['coupon' => $code]);
            $request->setMethod('PUT');
        }

        $form = $this->createForm(ProfileCouponType::class, $cart, ['method' => 'PUT', 'csrf_protection' => false]);
        $extError = $this->versioning->supports(MobileVersions::COUPON_ERROR_EXTENSION);

        if ($awFormProfileCouponHandlerMobile->handleRequest($form, $request)) {
            $coupon = $cart->getCoupon();

            if ($coupon && $coupon->getFirsttimeonly()) {
                return new JsonResponse([
                    'error' => 'This coupon can only be applied on the desktop version of AwardWallet.',
                ]);
            }

            $request->getSession()->set("use_coupon.cart", $cart->getCartid());
            $formTitle = !$extError ? $translator->trans('user.coupon.form.title') : null;
            $formLink = $awMobileUrlTransformer->generate('aw_mobile_usecoupon_result');

            return new JsonResponse([
                'success' => true,
                'next' => [
                    'route' => 'index.profile-edit',
                    'params' => [
                        'formLink' => $formLink,
                        'formTitle' => $formTitle,
                        'action' => str_replace("/profile/", "", $formLink),
                    ],
                ],
            ]);
        } elseif ($extError && $form->isSubmitted()) {
            $errors = $form->getErrors(true);

            if ($errors->count() === 1 && isset($errors[0])) {
                $c = $errors[0]->getCause();

                if ($c && method_exists($c, 'getCause') && is_array($c->getCause()) && sizeof($c->getCause())) {
                    $form = $formDehydrator->dehydrateForm($form, false);
                    $form2 = $this->getErrorForm($c->getCause(), $formDehydrator, $translator);

                    if (isset($form['children'], $form2['children']) && is_array($form['children']) && is_array($form2['children'])) {
                        $form['children'] = array_merge($form['children'], $form2['children']);
                    }

                    return new JsonResponse(array_merge(
                        $form,
                        ['formTitle' => $translator->trans('user.coupon.form.title')]
                    ));
                }
            }
        }

        return new JsonResponse(
            $formDehydrator->dehydrateForm($form, false)
        );
    }

    /**
     * @Route("/useCoupon/result", name="aw_mobile_usecoupon_result", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function useCouponResultAction(Request $request, FormDehydrator $formDehydrator, TranslatorInterface $translator)
    {
        if (!$this->versioning->supports(MobileVersions::AWPLUS_VIA_COUPON_ROUTE)) {
            throw new NotFoundHttpException();
        }

        $cartId = $request->getSession()->get("use_coupon.cart");

        if (!isset($cartId) || !is_numeric($cartId)) {
            throw $this->createNotFoundException();
        }
        $cart = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)
            ->find($cartId);

        if (!$cart || $cart->getUser()->getId() != $this->getCurrentUser()->getId() || !$cart->getCoupon()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ProfileCouponResultType::class, null, ['method' => 'PUT', 'cart' => $cart]);

        // TODO: change shitty client form request data structure
        $request->request->replace([
            $form->getName() => $request->request->all(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->remove("use_coupon.cart");

            return new JsonResponse([
                'needUpdate' => true,
                'success' => true,
            ]);
        }

        return new JsonResponse(
            array_merge(
                $formDehydrator->dehydrateForm($form, false),
                ['formTitle' => $translator->trans('user.coupon.form.title')]
            )
        );
    }

    private function getErrorForm(array $errorCause, FormDehydrator $formDehydrator, TranslatorInterface $tr)
    {
        $rows = [];

        if ($this->versioning->supports(MobileVersions::NATIVE_FORM_EXTENSION)) {
            $rows[] = [
                $tr->trans('coupon.current-account-status'),
                '<p style="text-align: right">
                    <strong>' . $errorCause['status'] . '</strong>' .
                    (isset($errorCause['expires']) ? '<br>' . $errorCause['expires'] : '') .
                '</p>',
            ];
            $rows[] = [
                $tr->trans('coupon.last-upgrade-via'),
                '<strong>' . $errorCause['upgradeVia'] . '</strong>',
            ];
            $rows[] = [
                $tr->trans('coupon.last-upgrade-on'),
                '<strong>' . $errorCause['upgradeOn'] . '</strong>',
            ];
        } else {
            $rows[] = [
                $tr->trans('coupon.current-account-status'),
                '<strong>' . $errorCause['status'] . '</strong>' .
                (isset($errorCause['expires']) ? '<br>' . $errorCause['expires'] : ''),
            ];
            $rows[] = [
                $tr->trans('coupon.last-upgrade-via'),
                $errorCause['upgradeVia'],
            ];
            $rows[] = [
                $tr->trans('coupon.last-upgrade-on'),
                $errorCause['upgradeOn'],
            ];
        }

        $form = $this->createFormBuilder(null, ['csrf_protection' => false])
            ->add("about", BlockContainerType::class, [
                "blockData" => new GroupTitle($tr->trans('coupon.about-account'), true),
                "attr" => ["class" => "small pad"],
            ])
            ->add("table", BlockContainerType::class, [
                'blockData' => new Table($rows),
                "attr" => ["class" => "coupon-table"],
            ])
            ->add("afterword", BlockContainerType::class, [
                'blockData' => new Paragraph($tr->trans('coupon.used-first-time-notice', ['%bold_on%' => '<b>', '%bold_off%' => '</b>'])),
                "attr" => ["class" => "coupon-text"],
            ])->getForm();

        return $formDehydrator->dehydrateForm($form, false);
    }
}
