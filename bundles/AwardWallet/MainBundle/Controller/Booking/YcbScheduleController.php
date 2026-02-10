<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/awardBooking/ycb")
 */
class YcbScheduleController extends AbstractController
{
    private AbRequestRepository $abRequestRep;
    private UsrRepository $userRep;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private BookingRequestManager $manager;
    private EventDispatcherInterface $eventDispatcher;
    private \HTMLPurifier $purifier;

    public function __construct(
        AbRequestRepository $abRequestRep,
        UsrRepository $userRep,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        BookingRequestManager $manager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->abRequestRep = $abRequestRep;
        $this->userRep = $userRep;
        $this->em = $em;
        $this->logger = $logger;
        $this->manager = $manager;
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $this->purifier = new \HTMLPurifier($config);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/schedule", name="aw_booking_ycb_schedule", methods={"POST"})
     * @return Response
     */
    public function scheduleAction(Request $request)
    {
        $this->logYcbRequest($request, 'schedule');

        $abRequest = $this->getAbRequest($request);

        if ($abRequest) {
            $params = $this->getYcbRequestParams($request);
            $atPhone = '';

            if (!empty($params['phone'])) {
                $atPhone = " (at {$params['phone']})";
            }

            $post = "
                    <p>
                        Thank you for choosing a time for our one-on-one {$params['type']} session on {$params['startTime']}.
                        <br />
                        Please ensure to log in to this online thread at the time of your {$params['type']} call appointment{$atPhone}, as we will be concurrently speaking with you and posting info on this thread.
                    </p>
                    
                    <p>
                        Thank you again.
                        <br />
                        {$abRequest->getBooker()->getBookerInfo()->getServiceName()} Team
                    </p>
                    
                    <p>
                        You can also:
                        <ul>
                            <li><a href='{$params['rescheduleLink']}' target='_blank'>Reschedule this booking</a></li>
                            <li><a href='{$params['cancelLink']}' target='_blank'>Cancel this booking</a></li>
                        </ul>
                    </p>
                    ";

            $this->addMessage($abRequest, $post);
        }

        return $this->getResponse($abRequest);
    }

    /**
     * @Route("/reschedule", name="aw_booking_ycb_reschedule", methods={"POST"})
     * @return Response
     */
    public function rescheduleAction(Request $request)
    {
        $this->logYcbRequest($request, 'reschedule');

        $abRequest = $this->getAbRequest($request);

        if ($abRequest) {
            $params = $this->getYcbRequestParams($request);

            $post = "
                    <p>
                        Thanks for rescheduling your appointment! Please be sure to open up this booking request on: {$params['startTime']} 
                        and be available to chat with us via this page for {$params['duration']}.
                    </p>
                    
                    <p>
                        You can also:
                        <ul>
                            <li><a href='{$params['rescheduleLink']}' target='_blank'>Reschedule this booking</a></li>
                            <li><a href='{$params['cancelLink']}' target='_blank'>Cancel this booking</a></li>
                        </ul>
                    </p>
                    ";

            $this->addMessage($abRequest, $post);
        }

        return $this->getResponse($abRequest);
    }

    /**
     * @Route("/cancel", name="aw_booking_ycb_cancel", methods={"POST"})
     * @return Response
     */
    public function cancelAction(Request $request)
    {
        $this->logYcbRequest($request, 'cancel');

        $abRequest = $this->getAbRequest($request);

        if ($abRequest) {
            $params = $this->getYcbRequestParams($request);
            $userName = $abRequest->getUser()->getFullName();

            $post = "
                    <p>{$userName} canceled appointment that was originally scheduled for {$params['startTime']}</p>
                    ";

            $this->addMessage($abRequest, $post);
        }

        return $this->getResponse($abRequest);
    }

    /**
     * @return AbRequest|null
     */
    private function getAbRequest(Request $request)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        $requestId = $data['request_id'] ?? null;
        $code = $data['request_code'] ?? null;

        if (!$requestId) {
            return null;
        }

        /** @var AbRequest $abRequest */
        $abRequest = $this->abRequestRep->find($requestId);

        return $abRequest && $abRequest->getHash() == $code ? $abRequest : null;
    }

    /**
     * @param AbRequest|null $abRequest
     * @return Response
     */
    private function getResponse($abRequest)
    {
        $response = new Response();

        if ($abRequest) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent('Request found: ' . $abRequest->getAbRequestID());
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent('Request not found');
        }

        return $response;
    }

    private function addMessage(AbRequest $abRequest, string $post)
    {
        $message = new AbMessage();

        $message
            ->setCreateDate(new \DateTime())
            ->setPost($post)
            ->setRequest($abRequest)
            ->setUser($abRequest->getBooker())
            ->setFromBooker(true)
            ->setType(AbMessage::TYPE_YCB_SCHEDULE);

        $abRequest->addMessage($message);
        $this->manager->addMessage($message);
        $this->manager->flush();
    }

    private function logYcbRequest(Request $request, $action)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        $firstName = $data['fname'] ?? null;
        $lastName = $data['lname'] ?? null;
        $email = $data['email'] ?? null;
        $requestId = $data['request_id'] ?? null;
        $clientIp = $request->getClientIp();

        $message = "Action: {$action}, RequestId: {$requestId}, Email: {$email}, First name: {$firstName}, Last Name: {$lastName}, Client: {$clientIp}";

        $this->logger->warning("YCB booking", ["message" => $message, 'data' => $data]);
    }

    private function getYcbRequestParams(Request $request)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        $startTime = $data['start_time'] ?? null;
        $duration = $data['duration'] ?? null;

        $rescheduleLink = $data['reschedule_link'] ?? null;
        $cancelLink = $data['cancel_link'] ?? null;

        //        $rescheduleUrl = preg_match("/\((.+)\)/", $rescheduleLink);
        //        $cancelUrl = preg_match("/\((.+)\)/", $cancelLink);
        //
        //        if($rescheduleUrl && $cancelUrl){
        //            $rescheduleLink = $this->validateYcbLink($rescheduleUrl[1] );
        //            $cancelLink = $this->validateYcbLink($cancelUrl[1]);
        //        }

        return [
            'startTime' => $this->purifier->purify($startTime),
            'duration' => $this->purifier->purify($duration),
            'rescheduleLink' => $rescheduleLink,
            'cancelLink' => $cancelLink,
            'type' => strtolower($data['type'] ?? 'phone'),
            'phone' => $data['phone'] ?? null,
        ];
    }

    private function validateYcbLink($link)
    {
        if (!preg_match('/https:\/\/(.+)\.youcanbook.me\//', $link)) {
            $link = '';
        }

        return $link;
    }
}
