<?php 
## NOT YET IN USE. MIGHT BE USED TO ABSTRACT QUERIES
/* This is the controller for linked in application logic */
class Query extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
}

?>