<?php 


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
		$this->api_daily_limit = $this->db->query("SELECT max_fetched_per_day FROM lde_settings WHERE id = '$active_id' LIMIT 1")->row(1)->max_fetched_per_day;
		# get current fetch count
		$this->api_fetch_count = $this->db->query("SELECT fetched_today FROM lde_active_brain WHERE id = '1' LIMIT 1")->row(1)->fetched_today;
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
	   	$linkedin_id = $xml->id;
	   	$fname = $xml->{'first-name'};
	   	$lname = $xml->{'last-name'};
	   	$email =  $xml->{'email-address'};
	   	$industry = $xml->industry;
	   	# start suspect :: the number of connections returned by this kind of profile search is limited to 500
	   	# might be an idea to remove it to save on data
	   	# :: end supect
	   	$location_name = $xml->location->name;
	   	$location_country = $xml->location->country->name;
	   	$location_country_code = $xml->location->country->code;
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
	private function recurse_fetch_network ($uid, $client) {
		# cache settings
		$active_settings = $this->active_settings;
		$limit = $this->api_daily_limit;
		$this->api_fetch_count = $this->db->query("SELECT fetched_today FROM lde_active_brain WHERE id = '1' LIMIT 1")->row(1)->fetched_today;
		$fetch_count = $this->api_fetch_count;

		# get the latest, fetched today count
		$fetched_today = $this->db->query("SELECT fetched_today FROM lde_settings WHERE id = '$active_settings' LIMIT 1")->row(1)->fetched_today;

		# get how many connections we have fetched for this user to date
		$particpant_data = $this->db->query("SELECT num_connections, connections_fetched FROM lde_participants WHERE id = '$uid' LIMIT 1")->row(1);
		# how many connections we have fetched for this user to date i.e. where to begin
		$participant_fetch_total = $particpant_data->connections_fetched;
		# how many connections this user has in total
		$participant_network_total = $particpant_data->num_connections;
		echo $fetched_today . "<br>";
		echo $this->api_fetch_count . "<br>";
		echo $limit;
		# only fetch if we havent exceeded out daily limit
		if ($fetched_today < $limit) {
			# make sure we  havent fetched all this users contacts already
			if ($participant_fetch_total < $participant_network_total) {
				$network_xml = $client->fetch( 'https://api.linkedin.com/v1/people/~/connections:(id,first-name,last-name,location,positions)', array('start'=>$participant_fetch_total, 'count'=> $fetch_count) );
				$network = simplexml_load_string($network_xml['result']);
				$code = $network_xml['code'];
				echo "<h3>$code</h3>";
				if ($code == 200) {
					# everything went ok
					# iterate the data we got back
					foreach($network->person as $person) {
						# cache person data
						$linkedin_id = mysql_real_escape_string($person->id);
						$fname = mysql_real_escape_string($person->{'first-name'});
						$lname = mysql_real_escape_string($person->{'last-name'});
						$location_name = mysql_real_escape_string($person->location->name);
						$location_code = mysql_real_escape_string($person->location->country->code);
						
						# save person data
						$save_person = $this->db->query("INSERT INTO lde_network VALUES ('','$linkedin_id','$uid', '$fname','$lname','$location_name','$location_code') ");

						# get last inserted uid for this contact
						$contact_uid = $this->db->insert_id();

						# iterate this persons prior positions
						foreach ($person->positions->position as $position) {
							# cache the values
							$p_linkedin_id = $position->id;
							$p_title = $position->title;
							$p_start_date = $position->{'start-date'}->year . "-" . $position->{'start-date'}->month . "-01";
							$p_end_date =  $position->{'end-date'}->year != '' ? $position->{'end-date'}->year . "-" . $position->{'end-date'}->month . "-01" : '';
							$p_is_current = $position->{'is-current'} == true ? 1 : 0;
							$p_company_name = $position->company->name;
							$p_company_size = $position->company->size;
							$p_company_industry = $position->company->industry;
							# save the values
							$save_positions = $this->db->query("INSERT INTO lde_positions VALUES('','$p_linkedin_id', '$contact_uid','2','$p_title',
																	'$p_start_date', '$p_end_date', '$p_is_current', '$p_company_name', '$p_company_size', '$p_company_industry')");
						}	
						# all finished for this person						
					}
					$new_count = $fetched_today + $fetch_count;
					# update our apps global settings/constraints before continuing
					$update_sys = $this->db->query("UPDATE lde_active_brain SET fetched_today = '$new_count' WHERE id = '1'");
				}
				else if ($code == 403) {
					# probably hit our data limit
					echo "<h2>Data limit has been throttled for the day, resuming later. Code: $code</h2>";
				}
				else {
					# something else went wrong
					echo "<h2>Something has gone wrong. Code: $code</h2>";
				}
			}
			else {
				# set this user to completed, clear this user from the schedule
				echo "should remove user.";
			}
		}
	}	

	# removes a user from the schedule
	# @param $user_id (INT) :: The system id of the user to remove from the scheduel
	private function remove_scheduled_user ( $user_id ) {

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
		$user = $this->db->query("SELECT * FROM lde_participants WHERE id = '$uid'")->row(1);
		$token = $user->token;
		# create client
		$client = new OAuth2\Client(self::CLIENT_ID, self::CLIENT_SECRET);
		# set access token
		$client->setAccessToken($token);
		# set client token name
		$client->setAccessTokenParamName('oauth2_access_token');
		# fetch network info
		$this->recurse_fetch_network($uid, $client);
	}

	# get the UID for the next scheduled participant
	private function get_next_sheduled_participant () {
		$result = $this->db->query("SELECT user_id FROM lde_schedule ORDER BY added_on ASC LIMIT 1");
	    $row = $result->row(1); 
		return $row->user_id;
	}

}

?>