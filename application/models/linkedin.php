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
   		    $client->setAccessTokenParamName('oauth2_access_token');
		   	$data = $client->fetch('https://api.linkedin.com/v1/people/~');
		   	echo $access_token . "<br />";
		   	echo $code . "<br />";
		   	var_dump($data);
		}
	}

}

?>