<?php 
reason_include_once( 'minisite_templates/modules/events.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/calendar_grid.php' );
reason_include_once( 'classes/icalendar.php' );
reason_include_once( 'classes/google_mapper.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherEventsModule';

class LutherEventsModule extends EventsModule
{
	var $list_date_format = 'l, F j';
	var $show_months = false;
	var $show_icalendar_links = false;
	
	//////////////////////////////////////
	// For The Events Listing
	//////////////////////////////////////
	function show_event_details()
	{
		$sponsorContactUrl = false;
		$url = get_current_url();
		$e =& $this->event;
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url))
		{
			echo '<div class="eventDetails">'."\n";
			//$this->show_images($e);
			echo '<h1>'.$e->get_value('name').'</h1>'."\n";
			//$this->show_ownership_info($e);
			$st = substr($e->get_value('datetime'), 0, 10);
			$lo = substr($e->get_value('last_occurence'), 0, 10);
			$now = date('Y-m-d');
			if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
			{
				if ($lo != $st)
				{
					echo '<p class="date">'.prettify_mysql_datetime($st, "F j, Y" ).' - '.prettify_mysql_datetime($lo, "F j, Y")."\n";
				}
				else 
				{
					echo '<p class="date">'.prettify_mysql_datetime( $this->request['date'], "F j, Y" )."\n";
				}
			}

			if ($now <= $lo || !$e->get_value('content'))
			{
				if ($e->get_value('description'))
				{
					echo '&nbsp;('.$e->get_value( 'description' ).')'."\n";
				}
				else if (substr($e->get_value( 'datetime' ), 11) != '00:00:00')
				{
					echo '&nbsp;('.prettify_mysql_datetime( $e->get_value( 'datetime' ), "g:i a" ).')'."\n";
				}
				
				if ($e->get_value('location'))
					echo '<br>'.$e->get_value('location')."\n";
			}	
			echo '</p>'."\n";
	
			if ($e->get_value('content'))
			{
				echo '<div class="eventContent">'."\n";
				echo $e->get_value( 'content' );
				echo '</div>'."\n";
			}
			
			if ($e->get_value('url'))
				echo '<div class="eventUrl">For more information, visit: <a href="'.$e->get_value( 'url' ).'">'.$e->get_value( 'url' ).'</a>.</div>'."\n";
			//$this->show_back_link();
			//$this->show_event_categories($e);
			//$this->show_event_audiences($e);
			//$this->show_event_keywords($e);
			echo '</div>'."\n";
		}
		else
		{		
			echo '<div class="eventDetails">'."\n";
			$this->show_back_link();
			//$this->show_images($e);
			echo '<h1>'.$e->get_value('name').'</h1>'."\n";
			//$this->show_ownership_info($e);
			$this->show_repetition_info($e);
			echo '<table>'."\n";
			if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
				echo '<tr><td width="15%">Date:</td><td width="85%">'.prettify_mysql_datetime( $this->request['date'], "l, F j, Y" ).'</td></tr>'."\n";
			if(substr($e->get_value( 'datetime' ), 11) != '00:00:00')
				echo '<tr><td width="15%">Time:</td><td width="85%">'.prettify_mysql_datetime( $e->get_value( 'datetime' ), "g:i a" ).'</td></tr>'."\n";
			$this->show_duration($e);
			if ($e->get_value('location'))
				echo '<tr><td width="15%">Location:</td><td width="85%">'.$e->get_value('location').'</td></tr>'."\n";
			echo '</table>'."\n";
			if ($e->get_value('description'))
			{
				echo '<p class="description">'.$e->get_value( 'description' ).'</p>'."\n";
			}
			if ($e->get_value('content'))
			{
				echo $e->get_value( 'content' )."\n";
			}
			if ($e->get_value('sponsor'))
			{
				echo '<p class="sponsor">Sponsor: '.$e->get_value('sponsor').'</p>'."\n";
				$sponsorContactUrl = true;
			}		
			$this->show_contact_info($e);
			if(!empty($contact))
			{
				$sponsorContactUrl = true;
			}
			if ($e->get_value('url'))
			{
				echo '<p class="eventUrl">For more information, visit: <a href="'.$e->get_value( 'url' ).'">'.$e->get_value( 'url' ).'</a>.</p>'."\n";
			}
			if ($sponsorContactUrl)
			{
				echo '<p class="eventUrl">&nbsp;</p>'."\n";
			}
			$this->show_google_map($e);
			echo '</div>'."\n";
		}
	}
	
	function show_back_link()
	{
		echo '<p class="back"><a title="Back to event listing" href="'.$this->construct_link().'">&#x25c4;</a></p>'."\n";
	}
	
	function show_repetition_info(&$e)
	{
		$rpt = $e->get_value('recurrence');
		$freq = '';
		$words = array();
		$dates_text = '';
		$occurence_days = array();
		if (!($rpt == 'none' || empty($rpt)))
		{
			$words = array('daily'=>array('singular'=>'day','plural'=>'days'),
							'weekly'=>array('singular'=>'week','plural'=>'weeks'),
							'monthly'=>array('singular'=>'month','plural'=>'months'),
							'yearly'=>array('singular'=>'year','plural'=>'years'),
					);
			if ($e->get_value('frequency') <= 1)
				$sp = 'singular';
			else
			{
				$sp = 'plural';
				$freq = $e->get_value('frequency').' ';
			}
			if ($rpt == 'weekly')
			{
				$days_of_week = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach($days_of_week as $day)
				{
					if($e->get_value($day))
						$occurence_days[] = $day;
				}
				$last_day = array_pop($occurence_days);
				$dates_text = ' on ';
				if (!empty( $occurence_days ) )
				{
					$comma = '';
					if(count($occurence_days) > 2)
						$comma = ',';
					$dates_text .= ucwords(implode(', ', $occurence_days)).$comma.' and ';
				}
				$dates_text .= prettify_string($last_day);
			}
			elseif ($rpt == 'monthly')
			{
				$suffix = array(1=>'st',2=>'nd',3=>'rd',4=>'th',5=>'th');
				if ($e->get_value('week_of_month'))
				{
					$dates_text = ' on the '.$e->get_value('week_of_month');
					$dates_text .= $suffix[$e->get_value('week_of_month')];
					$dates_text .= ' '.$e->get_value('month_day_of_week');
				}
				else
					$dates_text = ' on the '.prettify_mysql_datetime($e->get_value('datetime'), 'j').' day of the month';
			}
			elseif ($rpt == 'yearly')
			{
				$dates_text = ' on '.prettify_mysql_datetime($e->get_value('datetime'), 'F j');
			}
			echo '<p class="repetition">This event takes place each ';
			echo $freq;
			echo $words[$rpt][$sp];
			echo $dates_text;
			echo ' from '.prettify_mysql_datetime($e->get_value('datetime'), 'F j, Y').' to '.prettify_mysql_datetime($e->get_value('last_occurence'), 'F j, Y').'.';
			
			echo '</p>'."\n";
		}
			
	}
	
	function show_dates(&$e)
	{
		$dates = explode(', ', $e->get_value('dates'));
		if(count($dates) > 1 || empty($this->request['date']) || !strstr($e->get_value('dates'), $this->request['date']))
		{
			echo '<div class="dates"><h4>This event occurs on:</h4>'."\n";
			echo '<ul>'."\n";
			foreach($dates as $date)
			{
				echo '<li>'.prettify_mysql_datetime( $date, "l, F j, Y" ).'</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		}
	}
	
	function show_event_list_item_standard( $event_id, $day, $ongoing_type = '' )
	{
		$link = $this->events_page_url.$this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day));
		echo '<table><tr><td width="15%">';
		if($this->show_times && substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
		{
			echo prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format );
		}
		else
		{
			echo 'Today';
		}
		echo '</td><td width="85%"><a href="'.$link.'">';
		echo $this->events[$event_id]->get_value( 'name' );
		echo '</a>';
		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($this->events[$event_id]).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}
		echo '</td></tr></table>'."\n";
	}
	
	function no_events_error()
	{
		echo '<div class="newEventsError">'."\n";
		$start_date = $this->calendar->get_start_date();
		$audiences = $this->calendar->get_audiences();
		$categories = $this->calendar->get_categories();
		$min_date = $this->calendar->get_min_date();
		if($this->calendar->get_view() == 'all' && empty($categories) && empty( $audiences ) && empty($this->request['search']) )
		{
			//trigger_error('get_max_date called');
			$max_date = $this->calendar->get_max_date();
			if(empty($max_date))
			{
				echo '<p>This calendar does not have any events.</p>'."\n";
			}
			else
			{
				echo '<p>There are no future events in this calendar.</p>'."\n";
				echo '<ul>'."\n";
				echo '<li><a href="'.$this->construct_link(array('start_date'=>$max_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View most recent event</a></li>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<li><a href="'.$this->construct_link(array('start_date'=>$min_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View entire event archive</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		else
		{
			if(empty($categories) && empty($audiences) && empty($this->request['search']))
			{
				$desc = $this->get_scope_description();
				if(!empty($desc))
				{
					echo '<p>There are no events '.$this->get_scope_description().'.</p>'."\n";
					if($start_date > '1970-01-01')
					{
						echo '<ul><li><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></li></ul>'."\n";
					}
				}
				else
				{
					echo '<p>There are no events available.</p>'."\n";
				}
			}
			else
			{
				echo '<p>There are no events available';
				$clears = '<ul>'."\n";
				if(!empty($audiences))
				{
					$audience = current($audiences);
					echo ' for '.strtolower($audience->get_value('name'));
					$clears .= '<li><a href="'.$this->construct_link(array('audience'=>'')).'">Clear group/audience</a></li>'."\n";
				}
				if(!empty($categories))
				{
					$cat = current($categories);
					echo ' in the '.$cat->get_value('name').' category';
					$clears .= '<li><a href="'.$this->construct_link(array('category'=>'')).'">Clear category</a></li>'."\n";
				}
				if(!empty($this->request['search']))
				{
					echo ' that match your search for "'.htmlspecialchars($this->request['search']).'"';
					$clears .= '<li><a href="'.$this->construct_link(array('search'=>'')).'">Clear search</a></li>'."\n";
				}
				$clears .= '</ul>'."\n";
				echo $clears;
			
				if($this->calendar->get_start_date() > $this->today)
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'', 'view'=>'','category'=>'','audience'=>'', 'end_date'=>'','search'=>'')).'">Reset calendar to today</a></p>';
				}
				if($start_date > '1970-01-01')
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></p>'."\n";
				}
			}
		}
		echo '</div>'."\n";
	}
	
	function show_google_map(&$e)
	{
		$site_id = $this->site_id;
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'google_map_type' ) );
		$es->add_right_relationship($e->id(), relationship_id_of('event_to_google_map'));
		$es->add_rel_sort_field($e->id(), relationship_id_of('event_to_google_map'));
		$es->set_order('rel_sort_order');
		$gmaps = $es->run_one();
		
		draw_google_map($gmaps);
		
	}

}
?>