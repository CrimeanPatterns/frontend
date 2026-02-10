<?

require_once (__DIR__.'/../OfferPlugin.php');
// require (__DIR__.'/../../../lib/classes/TQuery.php');

class SpgOfferPlugin extends OfferPlugin{

    public function getDescription($connection = null){
        return '';
    }

    public function render($offerUserId, $params = null){
		// this function is obsolete, don't use it

		// if $params is set, render() works like a preview ignoring $offerUserId
		if (!is_null($params) && is_array($params)) {
		$dateInt = htmlspecialchars($params['DateInt']);
		echo "<script>alert('$dateInt')</script>";
        }

        //obsolete
        //$container = getSymfonyContainer();
        //$container->get('Templating')->renderResponse();
    }

    protected function searchUsers(){
        $i = 0;
        $this->log('Setting time limit to 59 seconds...');
        set_time_limit(59);
        $this->log('Searching for users...');
        $this->log('Stage 1: Parsing account history. This can take time, please be patient.');
        flush();
        $q = new TQuery("
select distinct(UserID) as 'u'
from AccountHistory
   join Account on Account.AccountID = AccountHistory.AccountID
where ProviderID = 25
  and not UserID in (
      select distinct(UserID)
      from Account
      join AccountHistory on Account.AccountID = AccountHistory.AccountID
      where ProviderID = 25
        and AccountHistory.Description like '%spg%'
      )
");
        $this->log('Stage 2: Parsing each user.');
        $su = 0;
        $resultUsers = array();
        while (!$q->EOF){
            set_time_limit(59);
            $toadd = false;
            $u = $q->Fields['u'];
            $q3 = new TQuery("select Usr.UserID as u, Reservation.HotelName as h, Reservation.CheckIndate as d, SUM(datediff(Reservation.CheckOutdate, Reservation.CheckIndate)) as days, HotelData.MaxPoints as points, HotelData.Category as category
                                  from Reservation
                                     join Account on Reservation.AccountID = Account.AccountID
                                     join Usr on Account.UserID = Usr.UserID
                                     join HotelData on HotelData.HotelName = Reservation.HotelName
                                  where Reservation.Cancelled <> 1
                                    and Reservation.CheckIndate < now()
                                    and Reservation.ProviderID = 25
                                    and HotelData.MaxPoints <= 30000
                                    and HotelData.MaxPoints > 0
                                    and Reservation.CheckOutDate > date_sub(now(), interval 1 year)
                                    and Usr.UserID = $u
                                  group by Reservation.HotelName
                                  order by days desc limit 1");
            if (!$q3->EOF){
                $toadd = true;
                $hotelName = $q3->Fields['h'];
                $category = $q3->Fields['category'];
                $points = $q3->Fields['points'];
                $nights = (int) (30000 / $points);
                $points = preg_replace('/(000)$/',',000',$points);
                $params = array(
                    "Hotel"		=> $hotelName,
                    //              "Date" 		=> "$date",
                    //				"DateInt"	=> "$unixDate",
                    "Category"	=> "$category",
                    "Points"	=> "$points",
                    "Nights"	=> "$nights"
                );
            }
            else{
                $q2 = new TQuery("
select AccountHistory.Description, HotelData.HotelName as 'hotelName', HotelData.Category as 'category', HotelData.MaxPoints as 'points', AccountHistory.PostingDate
from Account
   join AccountHistory on Account.AccountID = AccountHistory.AccountID
   join HotelData on AccountHistory.Description like CONCAT('%', HotelData.HotelName, '%')
where Account.ProviderID = 25
  and Account.UserID = $u
  and date_add(AccountHistory.PostingDate, interval 1 year) > now()
  and HotelData.MaxPoints <= 30000
  and HotelData.MaxPoints > 0
order by PostingDate desc
");
                if (!$q2->EOF){
                    $toadd = true;
                    $hotelName = $q2->Fields['hotelName'];
                    $category = $q2->Fields['category'];
                    $points = $q2->Fields['points'];
                    $nights = (int) (30000 / $points);
                    $points = preg_replace('/(000)$/',',000',$points);
                    $params = array(
                        "Hotel"		=> $hotelName,
                        //              "Date" 		=> "$date",
                        //				"DateInt"	=> "$unixDate",
                        "Category"	=> "$category",
                        "Points"	=> "$points",
                        "Nights"	=> "$nights",
                    );
                }
            }
            if ($toadd){
                $resultUsers[$u] = $params;
                $this->addUser($u, $params);
                $i++;
                if ($i % 1000 == 0){
                    $this->log("$i users added.");
                    flush();
                }
            }
            $q->Next();
            $su++;
            if ($su % 1000 == 0){
                $this->log("$su users parsed.");
                flush();
            }
        }
        $this->log("$su starwood users with no SPG card found.");
        $this->log("$i of them have visited SPG hotels.");
    	return $i;
	}
}
?>
