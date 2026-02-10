<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class AbstractVoter implements VoterInterface
{
    public const PREFIX_NOT = 'NOT_';

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function supportsAttribute($attribute)
    {
        $expectedAttributes = $this->getAttributes();

        if (!$expectedAttributes) {
            return false;
        }

        // prepare attribute
        $attribute = $this->prepareAttribute($attribute);

        return in_array($attribute, array_keys($expectedAttributes));
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $expectedClass = $this->getClass();

        if (!is_null($expectedClass) && !($object instanceof $expectedClass)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $expectedAttributes = $this->getAttributes();
        $result = VoterInterface::ACCESS_ABSTAIN;

        foreach ($attributes as $attr) {
            if (!$this->supportsAttribute($attr)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            $hasNot = $this->containsNot($attr);

            if (isset($expectedAttributes[$attr])) {
                $callback = $expectedAttributes[$attr];

                if ($hasNot) {
                    $hasNot = false;
                }
            } else {
                $callback = $expectedAttributes[$this->prepareAttribute($attr)];
            }

            if (is_callable($callback)) {
                $r = call_user_func_array($callback, [$token, $object]);

                if (is_bool($r)) {
                    $r = ($hasNot) ? !$r : $r;

                    if ($r) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }
            }
        }

        return $result;
    }

    public function isBusiness()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        ) {
            $requestMatcher = new RequestMatcher();
            $businessHost = $this->container->getParameter('business_host');
            $requestMatcher->matchHost($businessHost);

            return $requestMatcher->matches($request);
        }

        return false;
    }

    /**
     * @return array attributes
     *
     * array(
     *  attribute => method
     * )
     */
    protected function getAttributes()
    {
        return [];
    }

    /**
     * @return string|null
     */
    protected function getClass()
    {
        return null;
    }

    /**
     * @return Usr|null
     */
    protected function getBusinessUser(TokenInterface $token)
    {
        $user = $token->getUser();

        if (!($user instanceof Usr)) {
            return null;
        }

        $tokenStorage = $this->container->get('aw.security.token_storage');

        if (spl_object_hash($tokenStorage->getToken()) == spl_object_hash($token)) {
            $user = $tokenStorage->getBusinessUser();
        } elseif ($user->getAccountlevel() != ACCOUNT_LEVEL_BUSINESS) {
            $user = $this->container->get('doctrine')->getRepository(Usr::class)->getBusinessByUser($user);
        }

        return $user;
    }

    private function containsNot($attr)
    {
        return strpos($attr, self::PREFIX_NOT) === 0;
    }

    private function prepareAttribute($attr)
    {
        if ($this->containsNot($attr)) {
            $attr = substr($attr, strlen(self::PREFIX_NOT));
        }

        return $attr;
    }
}
