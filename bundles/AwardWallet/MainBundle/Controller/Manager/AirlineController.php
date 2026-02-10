<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\AirlineAlias;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/manager/airline")
 */
class AirlineController extends AbstractController
{
    public const perPage = 20;

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/", name="aw_manager_airline_index", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request)
    {
        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            SELECT
              a.AirlineID               AS id,
              a.Name                    AS name,
              a.Code                    AS IATA,
              a.ICAO                    AS ICAO,
              a.LastUpdateDate          AS updateDate,
              count(distinct aa.AirlineAliasID)  AS aliasCnt,
              p.ProviderID              AS providerId,
              p.ShortName               AS providerName,
              p.Kind                    AS providerKind
            from Airline a
              left join AirlineAlias aa on a.AirlineID = aa.AirlineID
              left join Provider p on a.Code = p.IATACode and p.Kind = :kind and p.State > /state
            GROUP BY a.AirlineID
            ORDER BY a.Name
        ", [
            ':kind' => PROVIDER_KIND_AIRLINE,
            ':state' => PROVIDER_DISABLED,
        ]);

        $airlines = $q->fetchAll(\PDO::FETCH_ASSOC);

        $iata = [];
        $icao = [];
        $name = [];

        foreach ($airlines as $i => $a) {
            if (!array_key_exists($a['IATA'], $iata)) {
                $iata[$a['IATA']] = [];
            }

            if (!array_key_exists($a['ICAO'], $icao)) {
                $icao[$a['ICAO']] = [];
            }

            if (!array_key_exists($a['name'], $name)) {
                $name[$a['name']] = [];
            }
            $iata[$a['IATA']][] = $i;
            $icao[$a['ICAO']][] = $i;
            $name[$a['name']][] = $i;
        }

        $iata = array_filter($iata, function ($v) { return count($v) >= 2; });
        $icao = array_filter($icao, function ($v) { return count($v) >= 2; });
        $name = array_filter($name, function ($v) { return count($v) >= 2; });

        foreach ($iata as $idx) {
            foreach ($idx as $i) {
                $airlines[$i]['doubleIATA'] = true;
            }
        }

        foreach ($icao as $idx) {
            foreach ($idx as $i) {
                $airlines[$i]['doubleICAO'] = true;
            }
        }

        foreach ($name as $idx) {
            foreach ($idx as $i) {
                $airlines[$i]['doubleName'] = true;
            }
        }

        $q = $connection->executeQuery("
            SELECT
              a.AirlineID               AS id,
              p.ProviderID              AS providerId,
              p.ShortName               AS providerName,
              p.IATACode                AS IATA
            from Provider p
              left join Airline a on a.Code = p.IATACode
            where p.Kind = :kind and p.State > :state
            having id is null
            ORDER BY p.ShortName
        ", [
            ':kind' => PROVIDER_KIND_AIRLINE,
            ':state' => PROVIDER_DISABLED,
        ]);

        $providers = $q->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'airlines' => $airlines,
            'providers' => $providers,
        ];
    }

    /**
     * @Route("/view", name="aw_manager_airline_view", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/view.html.twig")
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineRep->find($id);

        if (empty($airline)) {
            return $this->createNotFoundException();
        }

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            SELECT
              a.AirlineID               AS id,
              a.Name                    AS name,
              a.Code                    AS IATA,
              a.ICAO                    AS ICAO,
              a.LastUpdateDate          AS updateDate,
              count(aa.AirlineAliasID)  AS aliasCnt,
              p.ProviderID              AS providerId,
              p.ShortName               AS providerName,
              p.Kind                    AS providerKind
            from Airline a
              left join AirlineAlias aa on a.AirlineID = aa.AirlineID
              left join Provider p on a.Code = p.IATACode and p.Kind = :kind and p.State > :state
            where
              a.Name = :name
              " . ($airline->getCode() ? "or a.Code = :IATA" : '') . "
              " . ($airline->getIcao() ? "or a.ICAO = :ICAO" : '') . "
            GROUP BY a.AirlineID
            ORDER BY (a.AirlineID = :id) desc, a.Name
        ", [
            ':kind' => PROVIDER_KIND_AIRLINE,
            ':state' => PROVIDER_DISABLED,
            ':name' => $airline->getName(),
            ':IATA' => $airline->getCode(),
            ':ICAO' => $airline->getIcao(),
            ':id' => $airline->getAirlineid(),
        ]);

        $airlines = $q->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'airline' => $airline,
            'airlines' => $airlines,
        ];
    }

    /**
     * @Route("/add", name="aw_manager_airline_add", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/add.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('name', TextType::class, ['required' => true])
            ->add('iata', TextType::class, ['required' => false])
            ->add('icao', TextType::class, ['required' => true])
            ->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['name']) && isset($data['iata']) && isset($data['icao'])) {
                $data['name'] = trim($data['name']);
                $data['iata'] = strtoupper(trim($data['iata']));
                $data['icao'] = strtoupper(trim($data['icao']));

                if (!empty($data['name'])
                    && !empty($data['icao']) && preg_match('/^[A-Z]{3}$/i', $data['icao'])
                    && (empty($data['iata']) || preg_match('/^([0-9][A-Z]|[A-Z][0-9]|[A-Z]{2})$/i', $data['iata']))
                ) {
                    $airline = new Airline();
                    $airline->setName($data['name']);
                    $airline->setCode($data['iata']);
                    $airline->setIcao($data['icao']);
                    $airline->setLastupdatedate(new \DateTime());
                    $em->persist($airline);
                    $em->flush();

                    return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $airline->getAirlineid()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/edit", name="aw_manager_airline_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/edit.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineRep->find($id);

        if (empty($airline)) {
            return $this->createNotFoundException();
        }

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('name', TextType::class, ['required' => true])
            ->add('iata', TextType::class, ['required' => false])
            ->add('icao', TextType::class, ['required' => true])
            ->getForm();
        $form->setData([
            'name' => $airline->getName(),
            'iata' => $airline->getCode(),
            'icao' => $airline->getIcao(),
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['name']) && isset($data['iata']) && isset($data['icao'])) {
                $data['name'] = trim($data['name']);
                $data['iata'] = strtoupper(trim($data['iata']));
                $data['icao'] = strtoupper(trim($data['icao']));

                if (!empty($data['name'])
                    && !empty($data['icao']) && preg_match('/^[A-Z]{3}$/i', $data['icao'])
                    && (empty($data['iata']) || preg_match('/^([0-9][A-Z]|[A-Z][0-9]|[A-Z]{2})$/i', $data['iata']))
                ) {
                    $airline->setName($data['name']);
                    $airline->setCode($data['iata']);
                    $airline->setIcao($data['icao']);
                    $airline->setLastupdatedate(new \DateTime());
                    $em->flush();

                    return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $airline->getAirlineid()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
            'airline' => $airline,
        ];
    }

    /**
     * @Route("/delete", name="aw_manager_airline_delete", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineRep->find($id);

        if (empty($airline)) {
            return $this->createNotFoundException();
        }

        $em->remove($airline);
        $em->flush();

        return $this->redirect($this->router->generate('aw_manager_airline_index'));
    }

    /**
     * @Route("/convert", name="aw_manager_airline_convert", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function convertAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);
        $airlineAliasRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AirlineAlias::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineRep->find($id);

        if (empty($airline)) {
            return $this->createNotFoundException();
        }

        $targetId = $request->get('targetId');

        if (empty($targetId)) {
            return $this->createNotFoundException();
        }

        $targetAirline = $airlineRep->find($targetId);

        if (empty($targetAirline)) {
            return $this->createNotFoundException();
        }

        $aliases = $airlineAliasRep->findBy(['Alias' => $airline->getName()]);

        if (empty($aliases)) {
            $alias = new AirlineAlias();
            $alias->setAlias($airline->getName());
            $alias->setAirline($targetAirline);
            $em->persist($alias);
        }
        $em->remove($airline);
        $em->flush();

        return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $targetAirline->getAirlineid()]));
    }

    /**
     * @Route("/alias-add", name="aw_manager_airline_alias_add", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/editAlias.html.twig")
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addAliasAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);
        $airlineAliasRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AirlineAlias::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineRep->find($id);

        if (empty($airline)) {
            return $this->createNotFoundException();
        }

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('alias', TextType::class, ['required' => true])
            ->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['alias'])) {
                $data['alias'] = trim($data['alias']);

                if (!empty($data['alias'])) {
                    $aliases = $airlineAliasRep->findBy(['Alias' => $data['alias']]);

                    if (!empty($aliases)) {
                        $form->addError(new FormError('This alias binds to ' . $aliases[0]->getAirline()->getName() . ' (' . $aliases[0]->getAirline()->getCode() . ')'));
                    } else {
                        $alias = new AirlineAlias();
                        $alias->setAlias($data['alias']);
                        $alias->setAirline($airline);
                        $em->persist($alias);
                        $em->flush();

                        return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $airline->getAirlineid()]));
                    }
                }
            }
        }

        return [
            'form' => $form->createView(),
            'airline' => $airline,
        ];
    }

    /**
     * @Route("/alias-edit", name="aw_manager_airline_alias_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/Airline/editAlias.html.twig")
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAliasAction(Request $request)
    {
        $airlineAliasRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AirlineAlias::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $alias = $airlineAliasRep->find($id);

        if (empty($alias)) {
            return $this->createNotFoundException();
        }

        $airline = $alias->getAirline();

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('alias', TextType::class, ['required' => true])
            ->getForm();
        $form->setData([
            'alias' => $alias->getAlias(),
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['alias'])) {
                $data['alias'] = trim($data['alias']);

                if (!empty($data['alias'])) {
                    $aliases = $airlineAliasRep->findBy(['Alias' => $data['alias']]);

                    if (!empty($aliases)) {
                        foreach ($aliases as $i => $a) {
                            if ($a->getAirlineAliasID() == $alias->getAirlineAliasID()) {
                                unset($aliases[$i]);
                            }
                        }
                    }

                    if (!empty($aliases)) {
                        $form->addError(new FormError('This alias binds to ' . $aliases[0]->getAirline()->getName() . ' (' . $aliases[0]->getAirline()->getCode() . ')'));
                    } else {
                        $alias->setAlias($data['alias']);
                        $em->flush();

                        return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $airline->getAirlineid()]));
                    }
                }
            }
        }

        return [
            'form' => $form->createView(),
            'airline' => $airline,
            'alias' => $alias,
        ];
    }

    /**
     * @Route("/alias-delete", name="aw_manager_airline_alias_delete", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAliasAction(Request $request)
    {
        $airlineAliasRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AirlineAlias::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $airlineAlias = $airlineAliasRep->find($id);

        if (empty($airlineAlias)) {
            return $this->createNotFoundException();
        }

        $airline = $airlineAlias->getAirline();

        $em->remove($airlineAlias);
        $em->flush();

        return $this->redirect($this->router->generate('aw_manager_airline_view', ['id' => $airline->getAirlineid()]));
    }
}
