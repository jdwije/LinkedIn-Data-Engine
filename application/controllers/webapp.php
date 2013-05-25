<?php 


class Webapp extends CI_Controller {
	private $li_model;

	/* this object constructor fn */
	function __construct () {
		# construct parent
		parent::__construct();
		# load required libs and models
		$this->load->model('linkedin');
		$this->load->helper('url_helper');
	}

	/* The view method. This fn is for viewing pages, as a default it only works with the index page. */
	public function view($page = 'welcome')
	{
		if ( ! file_exists( 'application/views/pages/'. $page .'.php' ) )
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		$data['page_title'] = ucfirst($page); // Capitalize the first letter
		$data['page_description'] = ucfirst("A research program for Philip Schneider's master thesis investigating the effects of networking on entrepreneurship."); // Capitalize the first letter

		$this->load->view('templates/header', $data);
		$this->load->view('pages/'.$page, $data);
		$this->load->view('templates/footer', $data);
	}

	/* this method kicks off the authorization process so we can fetch the users data */
	public function authorize() {
		# authenticate the new user
		$this->linkedin->do_authentication();	
	}

	/* redirect function after user has authrnticated */
	public function authenticate() {
		$data['page_description'] = "Authenticating your LinkedIn account";
		$data['page_title'] = "Authenticate";
		$this->linkedin->verify_auth();
	}

	# this fn is the call back for the oauth process. users are directed
	# here along with there access token in as a GET param ['code']
	public function access_granted() {
		$data['page_title'] = "Thank You";
		$this->load->view('templates/header', $data);
		$this->load->view('pages/thankyou', $data);
		$this->load->view('templates/footer', $data);
	}

}





?>