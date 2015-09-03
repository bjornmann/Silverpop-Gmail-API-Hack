<?php
//
//
//Gmail code modified from: http://davidwalsh.name/gmail-php-imap
//
//

//Set to your timezone
date_default_timezone_set('America/New_York');

//Gmail host, you shouldn't have to change this.
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';

//Your gmail account
$username = 'yourname@gmail.com';

//Your gmail password
$password = 'Password123!';

//The email address your report will come from, your sender email in Silverpop
$spopEmailAdd = 'youraddress@yourdomain.com';

//The password you set for the CSV download in Silverpop
$dlpw = 'yourDownloadPassword123';

//Create your variable to hold your report URL
$url;

//Create your variable to hold the email headers;
$headers;

//Get today's date
$today = date("Y-m-d");

// try to connect
$inbox = imap_open($hostname,$username,$password) or die(imap_last_error());

// grab emails
$emails = imap_search($inbox,'ALL');

// if emails are returned, cycle through each...
if($emails) {
	//Sort by newest first
	rsort($emails);
	//start looping through
	foreach($emails as $email_number) {

		//grab the email header info
		$header = imap_headerinfo($inbox,$email_number);

		//Get the sender info from the header
		$fromaddr = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		//Get the send date from the header, format it to match our today and yesterday variables
		$date = date_create($header->MailDate);
		$date = date_format($date, 'Y-m-d');

		//If this email is from your Silverpop address, and it was sent today, continue
		if($fromaddr === $spopEmailAdd && $date === $today){

			//Get the body text of the email
			$str = imap_fetchbody($inbox,$email_number,1);

			//Use Regex to find the link to the CSV report that Silverpop sends
			$re = "/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/";
			preg_match($re, $str, $matches);
			$url = $matches[0];
		}
		//If the email is from someone other than your Silverpop sender, or is from a date other than today, do this stuff instead
		else{
			//Get yesterday
			$yesterday = date("Y-m-d", strtotime( '-1 days' ) );

			//If the email is from yesterday, and from your sender address, delete it. This assumes you have a cron job set up to run this script each day.
			if($fromaddr === $spopEmailAdd && $date === $yesterday){
				imap_delete($inbox,$email_number);
			}
			//In every other instance, delete the email and expunge the inbox.
			else{
				imap_delete($inbox,$email_number);
				imap_expunge($inbox);
			}
		}
	}
}
//If there aren't any emails returned, fail.
else{
	die('No emails :-(');
}
// close the Gmail connection
imap_close($inbox);

//
//Get your CSV!
//
//Take the URL from the email, and append /dl/ and then the password for the download (set in Silverpop)
$url = $url."/dl/".$dlpw;

//Set up a count variable if you'd like to count how many entries you get
$count = 0;

//If the CSV exists, and can be opened to read, continue
if (($handle = fopen($url, "r")) !== FALSE) {
	//open the file once, that will grab the first row, which contains your headers, and advance past them, giving your next fgetcsv just data rows.
	fgetcsv($handle);
	//make sure there's data there, and then it's time to boogie!
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
		//Your first column
		echo $data[0];

		//Your second column, and so on...
		echo $data[1];

		//add one to your count
		$count++;
	}
	echo 'There were '.$count.' rows of data! Party!';
	fclose($handle);
}
?>
