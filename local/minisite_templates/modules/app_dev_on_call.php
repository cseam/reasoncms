<?php
reason_include_once( 'minisite_templates/modules/default.php' );
set_include_path('/usr/local/webapps/reason/reason_package/ZendGdata-1.10.3/library');
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AppDevOnCallModule';
//reason_include_once(DISCO.INC.'disco.php');
require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');

/**
 * @see Zend_Gdata_AuthSub
 */
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

/**
 * @see Zend_Gdata_ClientLogin
 */
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

/**
 * @see Zend_Gdata_Calendar
 */
Zend_Loader::loadClass('Zend_Gdata_Calendar');
Zend_Loader::loadClass('Zend_Gdata_Extension_Visibility');


class AppDevOnCallModule extends DefaultMinisiteModule
{
  function init( $args = array() )
  {

  }
  
  function has_content()
  {
    return true;
  }

   

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using the ClientLogin credentials supplied.
 * 
 * @param  string $user The username, in e-mail address format, to authenticate
 * @param  string $pass The password for the user specified
 * @return Zend_Http_Client
 */    
function getClientLoginHttpClient($user, $pass) {
	$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
	$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
	return $client;
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing an
 * event on the authenticated user's calendar.  Includes the start time and
 * event ID in the output.  Events are ordered by starttime and include only
 * events occurring in the future.
 *
 * @param  Zend_Http_Client $client The authenticated client object
 * @return void
 */
function getPerson($client, $startDate, $endDate, $currentHour, $currentMinute, $findNext)
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  $query = $gdataCal->newEventQuery();
  $query->setUser('luther.edu_39333139333636353730@resource.calendar.google.com');
  $query->setVisibility('private');
  $query->setProjection('full');
  $query->setOrderby('starttime');
  $query->setStartMin($startDate);
  $query->setStartMax($endDate);
  $query->setFutureevents(false);
  $query->setSingleevents(true);
  $query->setSortorder('a');
  $eventFeed = $gdataCal->getCalendarEventFeed($query);
  foreach ($eventFeed as $event) {
    foreach ($event->when as $when) {
       $endTime =  split("T", $when->endTime);
       $endTime = split(":", $endTime[1]);
       $endHour = $endTime[0];
       $endMinute = $endTime[1];
       
       $startTime =  split("T", $when->startTime);
       $startTime = split(":", $startTime[1]);
       $startHour = $startTime[0];
       $startMinute = $startTime[1];
       
       // if findNext is true then we don't care about times, we just want to find the next appointment in the future
       if $findNext {
          return $event->title->text;
       }
       // if we haven't reached the end hour or we are currently living the end hour
       //echo '</br>' . $event->title->text . ' ' . $startHour . ' ' . $endHour . ' ' . $currentHour;
       //echo 'current hour: '.$currentHour;
       if (($endHour >= $currentHour) and ($startHour <= $currentHour)) {
           //  if the end hour is the current hour we better check minutes
           if ($endHour == $currentHour) {
               //  if the ending minute has passed this is not the event we want
               if (($endMinute >= $currentMinute) and ($startMinute <= $currentMinute)) {
                   //echo $event->title->text;
                   return $event->title->text;
               }
           }
           //  if the end hours do not match we know we haven't reached that time yet
           else {
               //echo $event->title->text;
               return $event->title->text;
           }
       }
    }
  }
}

 function run()
  { 
        $today = date("Y-m-d");
        $tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
        $tomorrow = date("Y-m-d", $tomorrow_temp);
        $next_week_temp = mktime(0, 0, 0, date("m")  , date("d")+7, date("Y"));
        $next_week = date("Y-m-d", $next_week_temp);

        $client = $this->getClientLoginHttpClient('google_api_user@luther.edu', 'bTI1+9scGSkeORU');
        $currentHour = date("H");
        $currentMinute = date("i");

        $onCall = $this->getPerson($client, $today, $tomorrow, $currentHour, $currentMinute, false);
        if ($onCall != '') {
             // this is where we should send a text message and probably an email to the on-call person
             echo "The on call person for today is ".$onCall.".";
        }
         else {
             // this is where we would let the HD/requestor know that nobody is on-call at this time and
             //   send an email to the next available on call person (next available)
             $next_available = $this->getPerson($client, $today, $next_week, $currentHour, $currentMinute, true);
             echo "Nobody is on call at the current time, but " . $next_available . " is next in line";
        }    
  }
}
?>