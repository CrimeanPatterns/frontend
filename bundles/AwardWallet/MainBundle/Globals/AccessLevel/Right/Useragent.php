<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

class Useragent extends AbstractRight
{
    public const ACCESS_ASKED = 0xFF00;

    public function fetchFields($ids, $filter)
    {
        $connection = $this->em->getConnection();
        $sql = "
        SELECT a.UserAgentID as `UAID`,
        a.ClientID, a.AgentID,
        `ShareByDefault`, `TripShareByDefault`, `AccessLevel` ,
        # COALESCE(a.`FirstName`,u.`FirstName`) as `FirstName`,
        # COALESCE(a.`LastName`,u.`LastName`) as `LastName`,
        `IsApproved`
        FROM UserAgent a
          LEFT JOIN Usr u
            on u.UserID = a.ClientID
        WHERE a.AgentID IN ({$filter})";
        $this->fields = $connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllPermissions()
    {
        return [
            'read_balance', 'write_balance',
            'read_travelplan', 'write_travelplan',
            'asked', '',
        ];
    }

    /*
        private function getPermit($type,$level,$include=true){
            $res=array();
            if(!is_array($level))$level=array($level);
            foreach($this->fields as $ua){
                if(strpos($type,$ua['Source'])!==false){
                    if($ua['IsApproved']){
                        if(in_array($ua['AccessLevel'],$level) === $include){
                            $res[$ua['UAID']]=true;
                            continue;
                        }
                    }elseif(in_array(self::ACCESS_ASKED,$level)){
                        $res[$ua['UAID']]=true;
                        continue;
                    }
                }
                $res[$ua['UAID']]=false;
            }
            return $res;
        }
        public function read_balance(){
            return $this->getPermit('A*',array(ACCESS_WRITE,ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_REFERRAL),false);
        }
        public function write_balance(){
            return $this->getPermit('A*',array(ACCESS_WRITE,ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_REFERRAL));
        }
        public function read_travelplan(){
            return $this->getPermit('T*',array(ACCESS_WRITE,ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_REFERRAL),false);
        }
        public function write_travelplan(){
            return $this->getPermit('T*',array(ACCESS_WRITE,ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_REFERRAL));
        }
        public function asked(){
            return $this->getPermit('AT*',self::ACCESS_ASKED);
        }
    */
}
