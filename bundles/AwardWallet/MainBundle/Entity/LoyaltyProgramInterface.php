<?php

namespace AwardWallet\MainBundle\Entity;

interface LoyaltyProgramInterface extends UserOwnedInterface, IdentityInterface, CardImageContainerInterface, LocationContainerInterface, CustomLoyaltyPropertyContainerInterface
{
}
