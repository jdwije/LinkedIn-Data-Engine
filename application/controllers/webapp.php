<?php 
/*
Copyright (C) 2013 Jason Wijegooneratne, Philip Schneider

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

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
		# if page doesn't exist show 404
		if ( ! file_exists( 'application/views/pages/'. $page .'.php' ) )
		{
			// Whoops, we don't have a page for that!
			show_404();
		}

		# set page data
		$data['page_title'] = ucfirst($page); // Capitalize the first letter
		$data['page_description'] = ucfirst("A research program for Philip Schneider's master thesis investigating the effects of networking on entrepreneurship."); // Capitalize the first letter
		
		# load views
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
	# here to be shown a thank you for participating message, just to keep it friendly :)
	public function access_granted() {
		# set page data
		$data['page_title'] = "Thank You";
		$data['page_description'] = "Just a personal note from the developers to show a little love";
		# load views
		$this->load->view('templates/header', $data);
		$this->load->view('pages/thankyou', $data);
		$this->load->view('templates/footer', $data);
	}

	public function do_schedule () {
		@$passcode = $_POST['passcode'];
		if ($passcode == 'z39FimidbYKCxKDVerng') {
			$this->linkedin->run_schedule();
		}
		else {
			die();
		}
	} 

}





?>