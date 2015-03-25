<?php
// REQUIRES: page

$conn = new PDO('mysql:host=localhost;dbname=showcase', 'root', '');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getIsCrawler() {
    $agents = array(
    	"google", 
    	"facebook", 
    	"bing",
    	"yahoo",
    	"twitter",
    	"instagram",
    	"amazon"
    );
    foreach ($agents as $agent) 
    {
	    if(stripos($_SERVER['HTTP_USER_AGENT'], $agent) !== False)
	    {
	    	return True;
	    }
    }
    return False;
}

$iscrawler = getIsCrawler();

if ($iscrawler == False) 
{
	$ip = $_SERVER['REMOTE_ADDR']; 
	$date = date('n')."-".date('d')."-".date('Y');
	
	//find next ID for new user
	$comm = $conn->query("select count(*) from Visitors");
	foreach ($comm as $com)
	{
		$nextID = (string)((int)$com[0] + 1);
		break;
	}
	
	//user has never visited site
	if ($_COOKIE['ID'] == "")
	{
		setcookie('ID', $nextID, time()+31556926);
		setcookie('IP', $ip, time()+31556926);
		setcookie('date', $date, time()+31556926);
		setcookie('visited', $_REQUEST['page'], time()+31556926);
		$comm = $conn->prepare("insert into Visitors (ID, IP, visited, date) values ('".$nextID."', '".$ip."', 'admin.php', '".$date."')");
		$comm->execute();
	}

	//user has visited site
	else 
	{
		//update cookie for today's date
		if(in_array($date, explode(" ", $_COOKIE['date'])) == False)
		{
			//filter bad dates out from previous visitor functions
			$baddt = False;
			foreach(explode(" ", $_COOKIE['date'] as $str)
			{
				//only the month or only the year or month-year
				if(strlen($str) == 1 || strlen($str) == 4 || strlen($str) == 6) 
				{
					$baddt = True;
					break;
				}
			}

			if($baddt)
			{
				setcookie('date', $date, time()+31556926);
			}
			else
			{
				setcookie('date', $_COOKIE['date']." ".$date, time()+31556926);
			}
		}

		//update cookie for visited pages
		if (strpos($_COOKIE['visited'], $_REQUEST['page']) === False)
		{
			setcookie('visited', $_COOKIE['visited']." ".$_REQUEST['page'], time()+31556926);
		}

		//update iplist cookie
		if (strpos($_COOKIE['IP'], $ip) === False)
		{
			setcookie('IP', $_COOKIE['IP']." ".$ip, time()+31556926);
		}

		$comm = $conn->query("select * from Visitors where ID = '".$_COOKIE['ID']."'");
		$exists = False;
		foreach ($comm as $com) 
		{
			$exists = True;
		}

		//user was never entered in database
		if($exists == False)
		{
			setcookie('ID', $nextID, time()+31556926);
			$comm = $conn->prepare("insert into Visitors (ID, IP, date, visited) values ('".$_COOKIE['ID']."', '".$_COOKIE['IP']."', '".$_COOKIE['date']."', '".$_COOKIE['visited']."')");
			$comm->execute();
		}

		//user exists in database
		else
		{
			$comm = $conn->prepare("update Visitors set IP = '".$_COOKIE['IP']."', date = '".$_COOKIE['date']."', visited = '".$_COOKIE['visited']."' where ID='".$_COOKIE['ID']."'");
			$comm->execute();
		}
	}
}
echo "success";
exit;
?>