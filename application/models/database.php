<?php 
## NOT YET IN USE. MIGHT BE USED TO ABSTRACT DATABASE INTERACTION BUT UNLIKELY
/* This is the controller for linked in application logic */
class Database extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
}

?>