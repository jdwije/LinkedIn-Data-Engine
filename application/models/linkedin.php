<?php 

/* This is the controller for linked in application logic */
class Linkedin extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		# include libs
		include_once realpath("resources/libs/oauth-php/library/OAuthStore.php");
		include_once realpath("resources/libs/oauth-php/library/OAuthRequester.php");
	}

	public function authorize_new_user() {
		$this->do_oauth('9tm0ff16gpuy', 'mYffXDX3RS3t8uEF' );
	}

	private function do_oauth($key, $secret) {		
		# set some vars
		$options = array('consumer_key' => $key, 'consumer_secret' => $secret);
		$state = 'DCEEFWF45453sdffef424';
		$redirect_uri = base_url() . "authenticated";
		$method = "GET";
		$params = null;

		# create new store
		OAuthStore::instance("2Leg", $options);

		# build our redirect url
		$url = "https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=" . $key . "&state=" . $state . "&redirect_uri=" . $redirect_uri;

		try
		{
			// Obtain a request object for the request we want to make
			$request = new OAuthRequester($url, $method, $params);

			// Sign the request, perform a curl request and return the results, 
			// throws OAuthException2 exception on an error
			// $result is an array of the form: array ('code'=>int, 'headers'=>array(), 'body'=>string)
			$result = $request->doRequest();
			
			$response = $result['body'];
		}
		catch(OAuthException2 $e)
		{
			print_r($e);
		}
	}

}

?>