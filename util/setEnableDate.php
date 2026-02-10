<?php

require __DIR__ . '/../web/kernel/public.php';

$container = getSymfonyContainer();

$em = $container->get('doctrine')->getManager();
$repository = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

$query = $repository->createQueryBuilder('p')
    ->where('p.state >= 1')
    ->getQuery();

$providers = $query->getResult();

$begin = new DateTime('2004-11-20');
$end = new DateTime('2012-03-20');
$end = $end->modify('+1 day');

$interval = new DateInterval('P2W');
$daterange = new DatePeriod($begin, $interval, $end);
$intervalCount = iterator_count($daterange);
$providersPerInterval = 503 / $intervalCount;
$counter = 1;
$currentInterval = 1;

/** @var \AwardWallet\MainBundle\Entity\Provider $provider */
foreach ($providers as $provider) {
    $creationDate = $provider->getCreationdate();
    $enableDate = clone $begin;

    if ($creationDate->format('Y') !== '-0001') {
        $provider->setEnabledate($creationDate);
    } else {
        $provider->setEnabledate($enableDate);

        if ($counter / $providersPerInterval >= $currentInterval) {
            $begin->modify('+2 week');
            $currentInterval++;
        }
        $counter++;
    }
    $em->persist($provider);
}

$em->flush();
