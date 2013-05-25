<?php 
const CLIENT_ID     = '9tm0ff16gpuy';
const CLIENT_SECRET = 'mYffXDX3RS3t8uEF';
const REDIRECT_URI           = 'http://54.251.251.190/index.php/participate';
const AUTHORIZATION_ENDPOINT = 'https://www.linkedin.com/uas/oauth2/authorization';
const TOKEN_ENDPOINT         = 'https://www.linkedin.com/uas/oauth2/accessToken';
const APP_STATE = '413E7FA26978F9F447BCF1173B9D6';

/* This is the controller for linked in application logic */
class Linkedin extends CI_Model {

	public $db_opts; # stores the database connection config

	# builds our object
	function __construct()
	{	
		# load db class
		$this->load->database();
		# include libs
		require('resources/libs/php-oauth2/Client.php');
		require('resources/libs/php-oauth2/GrantType/IGrantType.php');
		require('resources/libs/php-oauth2/GrantType/AuthorizationCode.php');
		
		# set db opts
		$this->db_opts = array(
				'server' => 'localhost', 
				'username' => 'root',
                'password' => 'Arz1|9KaF6[yg!6',  
                'database' => 'lde'
            );
	}

	# do an oauth. !
	# @param $key (String) :: Your consumer key as a string
	# @param $secret (String) :: Your consumer secret as a string
	public function do_authentication() {
		# create a new client
		$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

		# filter for 'code' param in $_GET
		if (!isset($_GET['code']))
		{	
			# not set, redirect to the authorisation dialogue
		    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI, array('state' => APP_STATE));
		    header('Location: ' . $auth_url);
		    die('Redirect');
		}
		else
		{	
			# set, lets save our user
		    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
		    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);
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
		$oauth_client->setAccessTokenParamName('oauth2_access_token');
	   	$data = $oauth_client->fetch('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address,industry,location,num-connections)');
	   	$xml = simplexml_load_string($data['result']);
	   	$linkedin_id = $xml->id;
	   	$fname = $xml->{'first-name'};
	   	$lname = $xml->{'last-name'};
	   	$email =  $xml->{'email-address'};
	   	$industry = $xml->industry;
	   	$num_connections = $xml->{'num-connections'};
	   	$location_name = $xml->location->name;
	   	$location_country = $xml->location->country;
	   	$location_country_code = $xml->location->code;
	   	$current_time = date('y-m-d');

	   	$this->db->query("INSERT INTO participants VALUES ('','$linkedin_id','$fname','$lname','$email','$industry',
	   							'$location_name','$location_country','$location_country_code','$num_connections','$current_time','0','$token','$token_expiry')");
	}

	# checks if particip[ant is already registerd
	private function participant_exists($linkedin_id) {
		
	}

}

?>