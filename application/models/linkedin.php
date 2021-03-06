<?php 
/*
Copyright (C) 2013 Jason Wijegooneratne (www.jwije.com), Philip Schneider

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/* This is the controller for linked in application logic */
class Linkedin extends CI_Model {
	# define constants
	const CLIENT_ID     = '9tm0ff16gpuy';
	const CLIENT_SECRET = 'mYffXDX3RS3t8uEF';
	const REDIRECT_URI           = 'http://54.251.251.190/index.php/participate';
	const AUTHORIZATION_ENDPOINT = 'https://www.linkedin.com/uas/oauth2/authorization';
	const TOKEN_ENDPOINT         = 'https://www.linkedin.com/uas/oauth2/accessToken';
	const APP_STATE = '413E7FA26978F9F447BCF1173B9D6';

	# what settings configuration to use from the database
	private $active_settings;
	private $api_daily_limit;
	private $api_fetch_count;
	private $num_fetched_today;

	# builds our object
	function __construct()
	{	
		# load db class
		$this->load->database();
		# include libs
		require('resources/libs/php-oauth2/Client.php');
		require('resources/libs/php-oauth2/GrantType/IGrantType.php');
		require('resources/libs/php-oauth2/GrantType/AuthorizationCode.php');

		# figure out what settings we are using
		$settings_query = $this->db->query("SELECT * FROM lde_active_brain LIMIT 1");
		$settings_row = $settings_query->row(1);
		$this->active_settings = $settings_row->active_configuration;
		$active_id = $this->active_settings;

		# load the settings conguration from the database and store in object
		$settings = $this->db->query("SELECT max_fetched_per_day, fetch_count FROM lde_settings WHERE id = '$active_id' LIMIT 1")->row(1);
		# get the maximum number of fetches allowed per day as defined in our settings
		$this->api_daily_limit = $settings->max_fetched_per_day;
		# get the number of contacts to fetch per request as defined in our settings
		$this->api_fetch_count = $settings->fetch_count;
		# get the number of fetches we have performed so far today
		$this->num_fetched_today = $this->db->query("SELECT fetched_today FROM lde_active_brain WHERE id = '1' LIMIT 1")->row(1)->fetched_today;
	}

	# do an oauth. !
	# @param $key (String) :: Your consumer key as a string
	# @param $secret (String) :: Your consumer secret as a string
	public function do_authentication() {
		# create a new client
		$client = new OAuth2\Client(self::CLIENT_ID, self::CLIENT_SECRET);

		# filter for 'code' param in $_GET
		if (!isset($_GET['code']))
		{	
			# not set, redirect to the authorisation dialogue
		    $auth_url = $client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, self::REDIRECT_URI, array('state' => self::APP_STATE));
		    header('Location: ' . $auth_url);
		    die('Redirect');
		}
		else
		{	
			# set, lets save our user
		    $params = array('code' => $_GET['code'], 'redirect_uri' => self::REDIRECT_URI);
		    $response = $client->getAccessToken(self::TOKEN_ENDPOINT, 'authorization_code', $params);
		    $result = $response['result'];
		    $code = $response['code'];
		    $content_type = $response['content_type'];
		    # we have our token, save it along with some user data
		    $access_token = $result['access_token'];
		    $expires_in = $result['expires_in'];
		    # set access token
   		    $client->setAccessToken($access_token);
   		   $this->register_new_participant($client, $access_token, $expires_in );
		}
	}


	private function register_new_participant ($oauth_client, $token, $token_expiry) {
		# set client token
		$oauth_client->setAccessTokenParamName('oauth2_access_token');
		# do fetch
	   	$data = $oauth_client->fetch('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address,industry,location)');
	   	# load xml
	   	$xml = simplexml_load_string($data['result']);
	   	# cache required values
	   	$linkedin_id = mysql_real_escape_string($xml->id);
	   	$fname = mysql_real_escape_string($xml->{'first-name'});
	   	$lname = mysql_real_escape_string($xml->{'last-name'});
	   	$email =  mysql_real_escape_string($xml->{'email-address'});
	   	$industry = mysql_real_escape_string($xml->industry);
	   	# start suspect :: the number of connections returned by this kind of profile search is limited to 500
	   	# might be an idea to remove it to save on data
	   	# :: end supect
	   	$location_name = mysql_real_escape_string($xml->location->name);
	   	$location_country = mysql_real_escape_string($xml->location->country->name);
	   	$location_country_code = mysql_real_escape_string($xml->location->country->code);
	   	# set current date time
	   	$current_time = date('y-m-d');
	   	# check if participant exists before adding
	   	$p_exists = $this->participant_exists($linkedin_id);
	   	# only update if the user doesnt already exist
	   	if (!$p_exists) {
	   		# figure out number of connections
	   		# added here so we only call if required to save on data
		   	$connections_data = $oauth_client->fetch('https://api.linkedin.com/v1/people/~/connections:(id)', array('start'=>0,'count'=>1));
		   	$connections_xml = simplexml_load_string($connections_data['result']);
		   	$num_connections;
		   	foreach ($connections_xml->attributes() as $attr => $val) {
		   		if ($attr == 'total') {
		   			$num_connections = $val;
		   		}
		   	}
	   		# user does not yet exists
		   	$this->db->query("INSERT INTO lde_participants VALUES ('','$linkedin_id','$fname','$lname','$email','$industry',
			  							'$location_name','$location_country','$location_country_code','$num_connections','','$current_time','0','$token','$token_expiry')");
		   	# get last inserted id
		   	$last_id = $this->db->insert_id();
		   	# add last inserted user to the schedules table. insert '' for table id column
		   	$this->db->query("INSERT INTO lde_schedule VALUES ('','$last_id','$current_time')");
		}
		/*  
			This is where whe would do a reauthentication if we had the time e.g:
			else {
				# do reauth...
			}
		*/

		# do redirect
   	   	header('Location: ' . site_url('access_granted'));

   	   	# kill
	   	die('Redirect');
	}
	
	# checks if particip[ant is already registerd
	# @param $linkedin_id (String) :: A linked in profile id
	# @returns $result (Boolean) :: true or false if participants exists or not
	private function participant_exists($linkedin_id) {
		# search participants for linkedin id
		$result = $this->db->query("SELECT linkedin_id FROM lde_participants WHERE linkedin_id = '$linkedin_id'");
		# if we have no rows matching return false 
		return $result->num_rows() < 1 ? false : true;
	}

	# function recursively fetches a users contacts
	# it is limited by the constraints set in the settings table
	# @param $uid (INT) :: The system user id of the person we should fetch network contacts for
	# @param $client (Object) :: A oauth2-php client object with the correct token and token name set for the given user already
	private function fetch_network ($uid, $client) {
		### cache settings
		$this->load->database();
		# toggle to go to next user
		$runnext = false;
		# what settings config to use
		$active_settings = $this->active_settings;
		# our global limit to check against
		$limit = $this->api_daily_limit;
		# how many calls we have made so far today. update our pclass property for this as well
		$get_fetched = $this->db->query("SELECT fetched_today FROM lde_active_brain WHERE id = '1' LIMIT 1");
		$num_fetched_today = $get_fetched->row(1)->fetched_today;
		$this->num_fetched_today = $num_fetched_today;
		# how many contacts we should fetch per request
		$fetch_count = $this->api_fetch_count;
		# some particpant data
		$particpant_data = $this->db->query("SELECT num_connections, connections_fetched FROM lde_participants WHERE id = '$uid' LIMIT 1")->row(1);
		# how many connections we have fetched for this user to date i.e. where to begin
		$participant_fetch_total = $particpant_data->connections_fetched;
		# how many connections this user has in total
		$participant_network_total = $particpant_data->num_connections;

		# only fetch if we havent exceeded out daily limit
		if ($num_fetched_today < $limit) {
			# make sure we  havent fetched all this users contacts already
			if ($participant_fetch_total < $participant_network_total) {
				$network_xml = $client->fetch( 'https://api.linkedin.com/v1/people/~/connections:(id,first-name,last-name,num-connections,location,positions)', array('start'=>$participant_fetch_total, 'count'=> $fetch_count) );
				$network = simplexml_load_string($network_xml['result']);
				$code = $network_xml['code'];
				if ($code == 200) {
					# everything went ok
					# iterate the data we got back
					if ($network->person != null) {
						foreach($network->person as $person) {
							# cache person data
							$linkedin_id = mysql_real_escape_string($person->id);
							$fname = mysql_real_escape_string($person->{'first-name'});
							$lname = mysql_real_escape_string($person->{'last-name'});
							$n_connections = mysql_real_escape_string($person->{'num-connections'});
							# remove notice for these fields because a decent amount of users dont have this set
							@$location_name = mysql_real_escape_string($person->location->name);
							@$location_code = mysql_real_escape_string($person->location->country->code);
							
							# save person data
							$save_person = $this->db->query("INSERT INTO lde_network VALUES ('','$linkedin_id','$uid', '$fname','$lname','$location_name','$location_code', '$n_connections') ");

							# get last inserted uid for this contact
							$contact_uid = $this->db->insert_id();

							# iterate this persons prior positions
							if ($person->positions->position != null) {
								foreach ($person->positions->position as $position) {
									# cache the values
									$p_linkedin_id = mysql_real_escape_string($position->id);
									$p_title =mysql_real_escape_string($position->title);
									$p_start_date = mysql_real_escape_string(  $position->{'start-date'}->year . "-" . $position->{'start-date'}->month . "-01" );
									$p_end_date =  mysql_real_escape_string( $position->{'end-date'}->year != '' ? $position->{'end-date'}->year . "-" . $position->{'end-date'}->month . "-01" : '' );
									$p_is_current = mysql_real_escape_string( $position->{'is-current'} == true ? 1 : 0 );
									$p_company_name = mysql_real_escape_string($position->company->name);
									$p_company_size = mysql_real_escape_string($position->company->size);
									$p_company_industry = mysql_real_escape_string($position->company->industry);
									# save the values
									$save_positions = $this->db->query("INSERT INTO lde_positions VALUES('','$p_linkedin_id', '$contact_uid','2','$p_title',
																			'$p_start_date', '$p_end_date', '$p_is_current', '$p_company_name', '$p_company_size', '$p_company_industry')");
								}	
								# all finished for this person	
							}					
						}
					}
					# update our user data
					$new_user_network_count = $participant_fetch_total + $network->count();
					$update_user = $this->db->query("UPDATE lde_participants SET connections_fetched = '$new_user_network_count' WHERE id = '$uid'");
					# update our apps global settings/constraints before continuing
					$new_fetched_today = $num_fetched_today + $network->count();
					$update_sys = $this->db->query("UPDATE lde_active_brain SET fetched_today = '$new_fetched_today' WHERE id = '1'");
					$this->fetch_network($uid, $client);
				}
				else if ($code == 403) {
					# probably hit our data limit
					echo "Data limit has been throttled for the day, resuming later. Code: $code";
					die();
				}
				else {
					# something else went wrong
					echo "Something has gone wrong. Code: $code";
					die();
				}
			}
			else {
				# set this user to completed, clear this user from the schedule
				$this->remove_scheduled_user($uid);
				# set to move onto next scheduled user
				$runnext = true;
			}
		}
		# close database
		$this->db->close();
		# do next if finished
		if ($runnext === true) {
			# sleep for a bit (don't overload linked in) then go onto next scheduled user
			sleep(20);
			$this->do_next_scheduled_user();
		}
	}	

	# removes a user from the schedule
	# @param $user_id (INT) :: The system id of the user to remove from the scheduel
	private function remove_scheduled_user ( $user_id ) {
		$update_schedule = $this->db->query("DELETE FROM lde_schedule WHERE user_id = '$user_id'");
		return $update_schedule;
	}

	# function is called from CLI and runs the apps schedule for fetching participants network
	# connections
	public function run_schedule () {
		$this->do_next_scheduled_user();
	}

	# runs routine on next scheduled user
	private function do_next_scheduled_user () {
		# get our next user. get there details
		$uid = $this->get_next_sheduled_participant();
		if ($uid !== 'none left') {
			$user = $this->db->query("SELECT * FROM lde_participants WHERE id = '$uid'")->row(1);
			$token = $user->token;
			# create client
			$client = new OAuth2\Client(self::CLIENT_ID, self::CLIENT_SECRET);
			# set access token
			$client->setAccessToken($token);
			# set client token name
			$client->setAccessTokenParamName('oauth2_access_token');
			# fetch network info
			$this->fetch_network($uid, $client);
		}
		else {
			die();
		}
	}

	# get the UID for the next scheduled participant
	private function get_next_sheduled_participant () {
		$result = $this->db->query("SELECT user_id FROM lde_schedule ORDER BY added_on ASC LIMIT 1");
		$r = 'none left';
		if ($result->num_rows() > 0) {
			$row = $result->row(1); 
			$r = $row->user_id;
		}
		return $r;
	}

}

?>