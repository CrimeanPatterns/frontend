<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

class User extends AbstractDbEntity
{
    /**
     * @var GroupUserLink[]
     */
    private array $groups;

    /**
     * @var Cart[]
     */
    private array $carts;

    private ?BusinessInfo $businessInfo = null;

    private ?UserPointValue $userPointValue = null;

    public function __construct(?string $login = null, bool $staff = true, array $fields = [])
    {
        if (empty($login)) {
            $login = sprintf('test-' . StringHandler::getRandomCode(10));
        }

        if (!empty($fields['Pass'])) {
            $encoder = new NativePasswordEncoder(null, null, 4);
            $fields['Pass'] = $encoder->encodePassword($fields['Pass'], null);
        }

        parent::__construct(array_merge([
            'Pass' => '$2y$04$8D8o2s3q7bkSRltaEU89fO9S.D/APIQaF2H7HDAvamzkwyPAbfazO', // awdeveloper
            'FirstName' => 'Ragnar',
            'LastName' => 'Petrovich',
            'Email' => $login . '@fakemail.com',
            'City' => 'Las Vegas',
            'CreationDateTime' => (new \DateTime())->format('Y-m-d H:i:s'),
            'EmailVerified' => EMAIL_VERIFIED,
            'CountryID' => 230, // USA
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
            'RefCode' => StringHandler::getRandomCode(5),
            'Secret' => StringHandler::getRandomCode(32, true),
            'RegistrationIP' => '127.0.0.1',
            'LastLogonIP' => '127.0.0.1',
        ], $fields, [
            'Login' => $login,
        ]));

        $this->groups = [];

        if ($staff) {
            $this->groups[] = new GroupUserLink(3);
            $this->groups[] = new GroupUserLink(37);
        }

        $this->carts = [];

        if ($this->getFields()['AccountLevel'] === ACCOUNT_LEVEL_BUSINESS) {
            $this->businessInfo = new BusinessInfo();
        }
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    public function addGroup(GroupUserLink $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    public function getCarts(): array
    {
        return $this->carts;
    }

    public function setCarts(array $carts): self
    {
        $this->carts = $carts;

        return $this;
    }

    public function addCart(Cart $cart): self
    {
        $this->carts[] = $cart;

        return $this;
    }

    public function getBusinessInfo(): ?BusinessInfo
    {
        return $this->businessInfo;
    }

    public function setBusinessInfo(?BusinessInfo $businessInfo): self
    {
        $this->businessInfo = $businessInfo;

        return $this;
    }

    public function getUserPointValue(): ?UserPointValue
    {
        return $this->userPointValue;
    }

    public function setUserPointValue(?UserPointValue $userPointValue): self
    {
        $this->userPointValue = $userPointValue;

        return $this;
    }

    public function getTableName(): string
    {
        return 'Usr';
    }

    public function getPrimaryKey(): string
    {
        return 'UserID';
    }
}
