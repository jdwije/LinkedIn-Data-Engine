<?php 

/* This is the controller for linked in application logic */
class Linkedin extends CI_Model {

	public function __construct()
	{
		$this->load->database();
		# include libs
		include_once realpath("resources/libs/oauth-php/library/OAuthStore.php");
		include_once realpath("resources/libs/oauth-php/library/OAuthRequester.php");
	}

	public function authorize_new_user() {
		$this->do_oauth('9tm0ff16gpuy', 'mYffXDX3RS3t8uEF' );
	}

	private function do_oauth($key, $secret) {		
		# build oauth store
		$options = array('server' => 'localhost', 'username' => 'root',
                 'password' => 'Arz1|9KaF6[yg!6',  'database' => 'lde');
		$store   = OAuthStore::instance('MySQL', $options);

		# add store to connect
		$uid = 1;

		# The server description
		$server = array(
		    'consumer_key' => $key,
		    'consumer_secret' => $secret,
		    'server_uri' => 'https://www.linkedin.com/',
		    'signature_methods' => array('HMAC-SHA1', 'PLAINTEXT'),
		    'request_token_uri' => 'https://www.linkedin.com/uas/oauth2/authorization',
		    'authorize_uri' =>  base_url() . "authenticate",
		    'access_token_uri' => 'https://www.linkedin.com/uas/oauth2/accessToken'
		);

		# Save the server in the the OAuthStore
		$consumer_key = $store->updateServer($server, $uid);

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

		header('Location: '.$uri);
		exit();
	}

}

?>