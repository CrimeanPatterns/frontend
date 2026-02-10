<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\Ad\Options;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

class Tools
{
    public static function createUser($accountLevel = ACCOUNT_LEVEL_FREE)
    {
        $user = new Entity\Usr();
        static::setValue($user, "userid", rand(1000, 9999));
        $user->setAccountlevel($accountLevel);
        $user->setEmail('test@test.com');
        $user->setFirstname(StringHandler::getRandomName()['FirstName']);
        $user->setLastname(StringHandler::getRandomName()['LastName']);
        $user->setCompany('Test Corporation');
        $user->setCreationdatetime(new \DateTime());
        $user->setLogin('testlogin');
        $user->setAccounts(rand(10, 100));
        $user->setLogoncount(rand(10, 500));
        $user->setResetpasswordcode(md5(123));

        return $user;
    }

    public static function createFamilyMember(Entity\Usr $agent)
    {
        $ua = new Entity\Useragent();
        static::setValue($ua, "useragentid", rand(1000, 9999));
        $ua->setAgentid($agent);
        $ua->setFirstname(StringHandler::getRandomName()['FirstName']);
        $ua->setLastname(StringHandler::getRandomName()['LastName']);
        $ua->setIsapproved(true);
        $ua->setSharecode("abcdef");
        $ua->setEmail("fm@test.com");

        return $ua;
    }

    public static function createConnection(Entity\Usr $agent, Entity\Usr $client)
    {
        $ua = new Entity\Useragent();
        static::setValue($ua, "useragentid", rand(1000, 9999));
        $ua->setAgentid($agent);
        $ua->setClientid($client);
        $ua->setIsapproved(true);

        return $ua;
    }

    public static function createSocialAd()
    {
        $ad = new Entity\Socialad();
        static::setValue($ad, "socialadid", rand(1000, 9999));
        $ad->setContent("
            <h2>Advertising</h2>
            <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla scelerisque vestibulum feugiat.
                Sed ac mauris nec quam aliquet semper. <a href=\"http://site.com\">Link</a>.
                Praesent pharetra placerat tincidunt. Aliquam erat volutpat.
                Duis luctus leo in felis ultricies mattis.
            </p>
        ");

        return $ad;
    }

    public static function createAbRequest($contactName)
    {
        $request = new Entity\AbRequest();
        static::setValue($request, "AbRequestID", rand(1000, 9999));
        $request->setContactName($contactName)
            ->setContactEmail('test@test.com')
            ->setContactPhone('12345678-987')
            ->setNotes('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                 labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                 aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore
                 eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
                 mollit anim id est laborum.
            ')
            ->setPriorSearchResults('
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                labore et dolore magna aliqua
            ')
            ->setPaymentCash((bool) rand(0, 1));

        return $request;
    }

    public static function createAbPassenger($fn, $ln, $gender = 'M', $nationality = 'US', $birthday = '-25 year')
    {
        $passenger = new Entity\AbPassenger();
        $passenger->setBirthday(new \DateTime($birthday))
            ->setFirstName($fn)
            ->setLastName($ln)
            ->setGender($gender)
            ->setNationality($nationality);

        return $passenger;
    }

    public static function createAbSegment(
        $dep,
        $arr,
        $depFrom = "+1 day",
        $depTo = "+3 day",
        $depIdeal = "+2 day",
        $returnFrom = "+10 day",
        $returnTo = "+12 day",
        $returnIdeal = "+11 day"
    ) {
        $segment = new Entity\AbSegment();
        $segment->setDep($dep)
            ->setArr($arr)
            ->setDepDateFrom(new \DateTime($depFrom))
            ->setDepDateTo(new \DateTime($depTo))
            ->setDepDateIdeal(new \DateTime($depIdeal))
            ->setReturnDateFrom(new \DateTime($returnFrom))
            ->setReturnDateTo(new \DateTime($returnTo))
            ->setReturnDateIdeal(new \DateTime($returnIdeal))
            ->setRoundTrip(1);

        return $segment;
    }

    public static function createAbCustomProgram($name, $balance, $owner, $requested = true)
    {
        $cp = new Entity\AbCustomProgram();
        $cp->setBalance($balance);
        $cp->setName($name);
        $cp->setOwner($owner);
        $cp->setRequested($requested);

        return $cp;
    }

    public static function createAbAccountProgram(Entity\Account $account, $requested = true)
    {
        $ap = new Entity\AbAccountProgram();
        $ap->setAccount($account);
        $ap->setRequested($requested);

        return $ap;
    }

    public static function createAbMessage($str)
    {
        $message = new Entity\AbMessage();
        $message->setCreateDate(new \DateTime());
        static::setValue($message, "AbMessageID", rand(1000, 9999));
        $message->setPost($str);

        return $message;
    }

    public static function createAbInvoice()
    {
        $message = new Entity\AbMessage();
        static::setValue($message, "AbMessageID", rand(1000, 9999));
        $invoice = new Entity\AbInvoice();
        $message->setInvoice($invoice);

        return $message;
    }

    public static function createAccount(Entity\Usr $owner, Entity\Provider $provider, $balance)
    {
        $account = new Entity\Account();
        static::setValue($account, "accountid", rand(1000, 9999));
        $account->setUserid($owner);
        $account->setProviderid($provider);
        $account->setBalance($balance);

        return $account;
    }

    public static function createProvider($name = "Test Provider", $programName = "Program"): Entity\Provider
    {
        $provider = new Entity\Provider();
        static::setValue($provider, "providerid", rand(1000, 9999));
        $provider->setDisplayname(sprintf("%s (%s)", $name, $programName));
        $provider->setName($name);
        $provider->setProgramname($programName);
        $provider->setCurrency(new Entity\Currency());
        $provider->setItineraryautologin(ITINERARY_AUTOLOGIN_BOTH);
        $provider->setAutologin(true);
        $provider->setKind(array_rand(Entity\Provider::getKinds()));

        return $provider;
    }

    public static function createProviderCoupon(Entity\Usr $owner, $programName = "My Program", $desc = "Desc for my program", $value = "100k")
    {
        $coupon = new Entity\Providercoupon();
        $coupon->setProgramname($programName);
        $coupon->setUserid($owner);
        $coupon->setDescription($desc);
        $coupon->setValue($value);

        return $coupon;
    }

    public static function createProviderProperty(Entity\Provider $provider, $code, $kind)
    {
        $pp = new Entity\Providerproperty();
        static::setValue($pp, "providerpropertyid", rand(1000, 9999));
        $pp->setCode($code);
        $pp->setKind($kind);
        $pp->setProviderid($provider);

        return $pp;
    }

    public static function createAccountProperty(Entity\Providerproperty $property, Entity\Account $account, $value)
    {
        $ap = new Entity\Accountproperty();
        static::setValue($ap, "accountpropertyid", rand(1000, 9999));
        $ap->setProviderpropertyid($property);
        $ap->setAccountid($account);
        $ap->setVal($value);

        return $ap;
    }

    public static function createTrip($conf, $category = TRIP_CATEGORY_AIR, ?Entity\Provider $provider = null)
    {
        $trip = new Entity\Trip();
        $trip->setConfirmationNumber($conf);
        $trip->setCategory($category);
        $trip->setRealProvider($provider);
        static::setValue($trip, "id", rand(1000, 9999));

        return $trip;
    }

    public static function createTripSegment()
    {
        $tripSeg = new Entity\Tripsegment();
        $tripSeg->setDepdate(new \DateTime());
        static::setValue($tripSeg, "tripsegmentid", rand(1000, 9999));

        return $tripSeg;
    }

    public static function createReservation($conf, ?Entity\Provider $provider = null)
    {
        $reservation = new Entity\Reservation();
        $reservation->setHotelname("Test Hotel");
        $reservation->setCheckindate(new \DateTime());
        $reservation->setCheckoutdate(new \DateTime("+2 day"));
        $reservation->setConfirmationNumber($conf);
        $reservation->setRealProvider($provider);
        static::setValue($reservation, "id", rand(1000, 9999));

        return $reservation;
    }

    public static function createRental($conf, ?Entity\Provider $provider = null)
    {
        $rental = new Entity\Rental();
        $rental->setConfirmationNumber($conf);
        $rental->setPickupdatetime(new \DateTime());
        $rental->setRealProvider($provider);
        static::setValue($rental, "id", rand(1000, 9999));

        return $rental;
    }

    public static function createRestaurant($conf, ?Entity\Provider $provider = null)
    {
        $restaurant = new Entity\Restaurant();
        $restaurant->setConfirmationNumber($conf);
        $restaurant->setStartdate(new \DateTime());
        $restaurant->setRealProvider($provider);
        static::setValue($restaurant, "id", rand(1000, 9999));

        return $restaurant;
    }

    public static function createParking($conf, ?Entity\Provider $provider = null)
    {
        $parking = new Entity\Parking();
        $parking->setConfirmationNumber($conf);
        $parking->setStartDatetime(new \DateTime());
        $parking->setRealProvider($provider);
        static::setValue($parking, "id", rand(1000, 9999));

        return $parking;
    }

    public static function createCart(
        Entity\Usr $user,
        $paymentType = Entity\Cart::PAYMENTTYPE_CREDITCARD,
        $billFn = null,
        $billLn = null,
        $billCountry = null,
        $billAddress = null,
        $billCity = null,
        $billZip = null
    ) {
        $cart = new Entity\Cart();
        static::setValue($cart, 'cartid', rand(1000, 9999));
        $cart->setUser($user);
        $cart->setPaymenttype($paymentType);
        $cart->setBillfirstname($billFn);
        $cart->setBilllastname($billLn);
        $cart->setBillcountry($billCountry);
        $cart->setBilladdress1($billAddress);
        $cart->setBillcity($billCity);
        $cart->setBillzip($billZip);

        return $cart;
    }

    public static function createInviteCode(Entity\Usr $user)
    {
        $i = new Entity\Invitecode();
        static::setValue($i, 'invitecodeid', rand(1000, 9999));
        $i->setEmail("test@test.com");
        $i->setUserid($user);
        $i->setCode("abcdef");
        $i->setSource("*");
        $i->setCreationdate(new \DateTime());

        return $i;
    }

    public static function createInvites(Entity\Usr $inviter, $email, $code)
    {
        $i = new Entity\Invites();
        static::setValue($i, 'invitesid', rand(1000, 9999));
        $i->setEmail($email);
        $i->setInviterid($inviter);
        $i->setCode($code);
        $i->setInvitedate(new \DateTime());

        return $i;
    }

    public static function createBonusConversion(Entity\Usr $user, $airlineName, $miles, Entity\Account $account)
    {
        $bc = new Entity\BonusConversion();
        static::setValue($bc, 'id', rand(1000, 9999));
        $bc->setUser($user);
        $bc->setAirline($airlineName);
        $bc->setMiles($miles);
        $bc->setAccount($account);
        $bc->setCreationDate(new \DateTime());
        $bc->setProcessed(true);

        return $bc;
    }

    public static function createCoupon(Entity\Usr $user)
    {
        $coupon = new Entity\Coupon();
        static::setValue($coupon, 'couponid', rand(1000, 9999));
        $coupon->setCode("Invite-" . $user->getUserid() . "-ABCDEF");
        $coupon->setCreationdate(new \DateTime());

        return $coupon;
    }

    public static function getAdvtByAccountId(ContainerInterface $container, array $accountIds, $emailKind)
    {
        $em = $container->get('doctrine.orm.default_entity_manager');
        $adAccounts = $em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findBy(['accountid' => $accountIds]);

        if (count($adAccounts) > 0) {
            $opt = new Options(ADKIND_EMAIL, $adAccounts[0]->getUser(), $emailKind);
            $opt->accounts = $adAccounts;

            return $container->get('aw.manager.advt')->getAdvt($opt);
        }

        return null;
    }

    public static function getAdvtByItineraryId(ContainerInterface $container, $itineraryId, $emailKind)
    {
        $itineraryId = str_replace(".", "", $itineraryId);
        $itineraryId = str_replace(["CI", "CO"], "R", $itineraryId);
        $itineraryId = str_replace(["PU", "DO"], "L", $itineraryId);
        $kind = substr($itineraryId, 0, 1);
        $itineraryId = substr($itineraryId, 1);

        $em = $container->get('doctrine.orm.default_entity_manager');
        /** @var \AwardWallet\MainBundle\Entity\Itinerary $it */
        $it = $em->getRepository(Entity\Itinerary::getItineraryClass($kind))->find($itineraryId);

        if (!$it && $kind == 'T') {
            /** @var Entity\Tripsegment $it */
            $it = $em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->find($itineraryId);

            if ($it) {
                $it = $it->getTripid();
            }
        }

        if ($it) {
            $opt = new Options(ADKIND_EMAIL, $it->getUser(), $emailKind);

            if ($it->getProvider()) {
                $opt->providers = [$it->getProvider()];
            }

            return $container->get('aw.manager.advt')->getAdvt($opt);
        }

        return null;
    }

    public static function addAdvtByAccountIdForm(FormBuilder $builder, ContainerInterface $container, $formOptions = [])
    {
        $builder->add('AdAccountID', TextType::class, array_merge([
            'label' => /** @Ignore */ 'Advert for AccountID',
        ], $formOptions));
    }

    public static function addAdvtByItineraryIdForm(FormBuilder $builder, ContainerInterface $container, $formOptions = [])
    {
        $builder->add('AdItID', TextType::class, array_merge([
            'label' => /** @Ignore */ 'Advert for itinerary (e.g. T12345, L54321)',
        ], $formOptions));
    }

    /**
     * @return Entity\AbBookerInfo
     */
    public static function getDefaultMerchant(ContainerInterface $container)
    {
        return $container->get("doctrine.orm.default_entity_manager")
            ->createQueryBuilder()
            ->select("bi")
            ->from(Entity\AbBookerInfo::class, "bi")
            ->orderBy("bi.AbBookerInfoID", "asc")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public static function getProviders(ContainerInterface $container)
    {
        $qb = $container->get("doctrine.orm.default_entity_manager")->createQueryBuilder();
        $qb->from(Entity\Provider::class, 'p')
            ->select('p')
            ->where('p.state >= ' . PROVIDER_ENABLED)
            ->orderBy('p.displayname', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public static function addUserAccountLevelForm(FormBuilder $builder, ContainerInterface $container, $formOptions = [])
    {
        $builder->add('AccountLevel', ChoiceType::class, array_merge([
            'choices' => [
                /** @Ignore */
                'Regular Account' => ACCOUNT_LEVEL_FREE,
                /** @Ignore */
                'Aw Plus Account' => ACCOUNT_LEVEL_AWPLUS,
                /** @Ignore */
                'Business Account' => ACCOUNT_LEVEL_BUSINESS,
            ],
        ], $formOptions));
    }

    public static function addMerchantForm(FormBuilder $builder, ContainerInterface $container, $formOptions = [])
    {
        /** @var \PDOStatement $stm */
        $stm = $container->get("doctrine")->getConnection()->prepare("
            SELECT
                AbBookerInfoID,
                ServiceName
            FROM
                AbBookerInfo
            ORDER BY
                ServiceName ASC
        ");
        $stm->execute();
        $merchants = [];

        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $merchants[$row['ServiceName']] = $row['AbBookerInfoID'];
        }

        $builder->add('Merchant', ChoiceType::class, array_merge([
            'required' => false,
            /** @Ignore */
            'label' => 'Merchant',
            'choices' => $merchants,
        ], $formOptions));
    }

    public static function setValue($obj, $property, $value)
    {
        $class = new \ReflectionClass(get_class($obj));
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }
}
