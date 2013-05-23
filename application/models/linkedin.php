<?php 

/* This is the controller for linked in application logic */
class Linkedin extends CI_Model {

	public $db_opts;

	function __construct()
	{
		$this->load->database();
		# include libs
		include_once realpath("resources/libs/oauth-php/library/OAuthStore.php");
		include_once realpath("resources/libs/oauth-php/library/OAuthRequester.php");
		
		# set db opts
		$this->db_opts = array(
				'server' => 'localhost', 
				'username' => 'root',
                'password' => 'Arz1|9KaF6[yg!6',  
                'database' => 'lde'
            );
	}

	public function authorize_new_user() {
		$this->begin_auth( '9tm0ff16gpuy', 'mYffXDX3RS3t8uEF', 1 );
		# $this->test_oauth();
	}

	public function get_oauth_servers() {
		$opts = $this->db_opts;
		$store = OAuthStore::instance('MySQL', $opts);
		$servers = $store->listServers('', 1);
		return $servers;
	}

	public function get_consumer_key ($id) {
		$servers = $this->get_oauth_servers();
		$c_key = $servers[ $id - 1 ]['consumer_key'];
		return $c_key;
	}

	public function build_oauth_store ($key, $secret) {
		# build oauth store
		$opts = $this->db_opts;
		$store = OAuthStore::instance('MySQL', $opts);

		# store user ID
		$uid = 1;

		# The server description
		$server = array(
		    'consumer_key' => $key,
		    'consumer_secret' => $secret,
		    'server_uri' => 'https://www.linkedin.com/',
		    'signature_methods' => array('HMAC-SHA1', 'PLAINTEXT'),
		    'request_token_uri' => 'https://api.linkedin.com/uas/oauth/requestToken',
		    'authorize_uri' =>  'https://api.linkedin.com/uas/oauth/authorize',
		    'access_token_uri' => 'https://api.linkedin.com/uas/oauth/accessToken'
		);

		# Save the server in the the OAuthStore
		$consumer_key = $store->updateServer($server, $uid);
	}

	public function begin_auth($key, $secret, $uid) {
		# get c key
		$consumer_key = $this->get_consumer_key($uid);

		// Obtain a request token from the server
		$token = OAuthRequester::requestRequestToken($consumer_key, $uid);

		// Callback to our (consumer) site, will be called when the user finished the authorization at the server
		$callback_uri = base_url() . 'access_granted?consumer_key='.rawurlencode($consumer_key).'&usr_id='.intval($uid);

		// Now redirect to the autorization uri and get us authorized
		if (!empty($token['authorize_uri']))
		{
		    // Redirect to the server, add a callback to our server
		    if (strpos($token['authorize_uri'], '?'))
		    {
		        $uri = $token['authorize_uri'] . '&'; 
		    }
		    else
		    {
		        $uri = $token['authorize_uri'] . '?'; 
		    }
		    $uri .= 'oauth_token='.rawurlencode($token['token']).'&oauth_callback='.rawurlencode($callback_uri);
		}
		else
		{
		    // No authorization uri, assume we are authorized, exchange request token for access token
		   $uri = $callback_uri . '&oauth_token='.rawurlencode($token['token']);
		}

		header('Location: '. $uri);
		exit();
	}

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