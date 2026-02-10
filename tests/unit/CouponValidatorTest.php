<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\MainBundle\Validator\CouponValidator;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

/**
 * @group frontend-unit
 */
class CouponValidatorTest extends BaseUserTest
{
    /**
     * @var CouponValidator
     */
    private $validator;

    /**
     * @var Manager
     */
    private $cartManager;

    public function _before()
    {
        parent::_before();

        $this->cartManager = $this->container->get('aw.manager.cart');
        $this->cartManager->setUser($this->user);
        $translator = $this->getMockBuilder(IdentityTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));
        $translator->expects($this->any())->method('transChoice')->will($this->returnArgument(0));

        $localizer = $this->container->get(LocalizeService::class);
        $localizer->setLocale("en");
        $this->validator = new CouponValidator(
            $translator,
            $this->container->get('doctrine.orm.default_entity_manager'),
            $localizer,
            $this->container->get(ExpirationCalculator::class),
            $this->container->get("security.authorization_checker"),
            $this->container->get(Formatter::class)
        );
    }

    public function _after()
    {
        $this->validator = $this->cartManager = null;
        parent::_after();
    }

    public function testFirstTimeOnlyAfterRegistration()
    {
        $this->cartManager->giveAwPlusTrial();
        $coupon = $this->haveCoupon("FTO" . $this->aw->grabRandomString(10), 100, null, null, 1, 1);
        $context = $this->getContext();
        $context->expects($this->never())->method('buildViolation');
        $result = $this->validator->validateCoupon($coupon, $context);
        $this->assertEmpty($result);

        $this->user->setCreationdatetime(new \DateTime("-13 hour"));
        $this->em->flush();

        $context = $this->getContext();
        $context->expects($this->never())->method('buildViolation');
        $result = $this->validator->validateCoupon($coupon, $context);
        $this->assertEmpty($result);
    }

    /**
     * @param string $code
     * @param int $discount
     * @param int $maxUses
     * @param int $firstTimeOnly
     * @return CouponModel
     */
    private function haveCoupon(
        $code,
        $discount = 100,
        ?\DateTime $start = null,
        ?\DateTime $end = null,
        $maxUses = 1,
        $firstTimeOnly = 1
    ) {
        $fields = [
            'Name' => $code,
            'Code' => $code,
            'Discount' => $discount,
            'MaxUses' => $maxUses,
            'FirstTimeOnly' => $firstTimeOnly,
        ];

        if (isset($start)) {
            $fields['StartDate'] = $start->format("Y-m-d H:i:s");
        }

        if (isset($end)) {
            $fields['EndDate'] = $end->format("Y-m-d H:i:s");
        }

        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Coupon::class)->find(
            $this->db->shouldHaveInDatabase("Coupon", $fields)
        );
        $couponModel = new CouponModel();
        $couponModel->setCoupon($code);
        $couponModel->setEntity($this->cartManager->createNewCart());

        return $couponModel;
    }

    private function getContext()
    {
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()
            ->setMethods(['buildViolation'])->getMock();
        $context->method('buildViolation')->willReturnCallback(function () {
            $builder = $this->getMockBuilder(ConstraintViolationBuilder::class)
                ->setMethods(['addViolation', 'setCause'])
                ->disableOriginalConstructor()->getMock();
            $builder->method("setCause")->willReturnSelf();

            return $builder;
        });

        return $context;
    }
}
