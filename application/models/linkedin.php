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

	# returns available oauth server from store
	public function get_oauth_servers() {
		$opts = $this->db_opts;
		$store = OAuthStore::instance('MySQL', $opts);
		$servers = $store->listServers('', 1);
		return $servers;
	}

	# gets the consumer key from server with id $id
	# @param $id (INT) :: The id of the store as an integer
	public function get_consumer_key ($id) {
		$servers = $this->get_oauth_servers();
		$c_key = $servers[ $id - 1 ]['consumer_key'];
		return $c_key;
	}

	# do an oauth. !
	# @param $key (String) :: Your consumer key as a string
	# @param $secret (String) :: Your consumer secret as a string
	public function do_authentication() {
	
		$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

		if (!isset($_GET['code']))
		{
		    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI, array('state' => APP_STATE));
		    header('Location: ' . $auth_url);
		    die('Redirect')	;
		}
		else
		{
		    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
		    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);
		    parse_str($response['result'], $info);
		    $client->setAccessToken($info['access_token']);
		    var_dump($response, $response['result']);
		}
	}

	# verify authentication. called via a url
	# @GET['oauth_token'] (String) :: The oauth token provided by the server as a GET param
	public function verify_auth () {
		# get post vars
		$oauth_token = $_GET['oauth_token'];
		# $oauth_token_secret = $_GET['oauth_token_secret'];
		$consumer_key =$this->get_consumer_key(1);

		try
		{	

		#	echo $oauth_token;
		#	echo "<br />";

		#	echo $oauth_token_secret;
		#	echo "<br />";

		#	echo $consumer_key;
			
		   $mad_token = OAuthRequester::requestAccessToken($consumer_key, $oauth_token, 1);
		}
		catch (OAuthException $e)
		{
		    var_dump($e);
		}	
	}

}

?>