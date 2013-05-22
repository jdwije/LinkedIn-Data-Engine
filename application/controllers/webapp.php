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
			$this->linkedin->authorize_new_user();	
	}

	/* redirect function after user has authrnticated */
	public function authenticated() {
		$this->load->view('templates/header', $data);
		$this->load->view('pages/thankyou');
		$this->load->view('templates/footer', $data);
	}

}





?>