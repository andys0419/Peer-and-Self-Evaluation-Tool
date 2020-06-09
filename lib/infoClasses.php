<?php

// define a class that handles information related to instructors
class InstructorInfo
{
  
  // standard data members
  public $id = NULL;
  public $name = NULL;
  public $email = NULL;
  public $otp = NULL;
  public $otp_expiration = NULL;
  public $session_expiration = NULL;
  public $csrf_token = NULL;
  
  // flag data members
  public $init_auth_status = 1;
  public $otp_status = 1;
  public $session_status = 2;
  
  
  // function members
  // checks the value of the initial authorization cookie
  public function check_init_auth($db_connection)
  {
  }
  
  // checks the value of the session cookie
  public function check_session($db_connection, $action)
  {
  }
  
}

?>
