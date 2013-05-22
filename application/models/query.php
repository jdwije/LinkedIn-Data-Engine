<?php 

/* This is the controller for linked in application logic */
class Query extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
}

?>