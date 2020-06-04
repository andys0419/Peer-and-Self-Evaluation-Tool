<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

// start the session variable
session_start();

// bring in required code
require "../lib/random.php";
require "../lib/database.php";
require "../lib/constants.php";

// connect to the database
$con = connectToDatabase();

// handle email submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
  
  // make sure the email submission is set
  if (!isset($_POST['email']))
  {
    http_response_code(400);
    echo "Bad Request: Missing parmeters.";
    exit();
  }
  
  // make sure the email is not just whitespace
  $email = trim($_POST['email']));
  
  // store error messages
  $email_error_message = "";
  if (empty($email))
  {
    $email_error_message = "Email cannot be blank.";
  }
  
  // now, lookup the email in the database
  $stmt = $con->prepare('SELECT id FROM instructors WHERE email=?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $result->fetch_all();
  
  // check if the email matches and store error messages
  $email_error_message2 = "";
  if ($result->num_rows == 0)
  {
    $email_error_message2 = "Email Address Not List of Registered Instructors.";
  }
  
  
}


	//check if student is enrolled
	$stmt = $con->prepare('SELECT email from students WHERE email=?');
  $stmt->bind_param('s',$email);
  $stmt->execute();
	$stmt->bind_result($flag);
	$stmt->store_result();
	$stmt->fetch();
	if($stmt->num_rows == 0){
		echo '<script language="javascript">';
    echo 'alert("Email was not found in the list of students. Please contact your professor.")';
    echo '</script>';
		$stmt->close();
		exit();
	}

  $expiration_time = time()+ 60 * 15;
  //update passcode and timestamp
  $stmt = $con->prepare('UPDATE student_login SET expiration_time =? WHERE email=?');
  $stmt->bind_param('is', $expiration_time, $email);
  $stmt->execute();
  if($stmt->affected_rows == 0){
      $stmt = $con->prepare('INSERT INTO student_login (email,expiration_time) VALUES(?,?)');
      $stmt->bind_param('si', $email, $expiration_time);
      $stmt->execute();
  }
  $code_available = false;
  //if password is taken try until it's not taken
  while(!$code_available){
      $code = random_string(10);
      $stmt = $con->prepare('UPDATE student_login SET password =? WHERE email=?');
      $stmt->bind_param('ss', $code, $email);
      $code_available = $stmt->execute();
  }
  $date = new DateTime("@$expiration_time");
  $date->setTimezone(new DateTimeZone('America/New_York'));
  $human_exp_time = $date->format('h:i a');
  //be careful the email text is whitespace sensitive
  mail($email,"Teamwork Evaluation Form Access Code", "<h1>Your code is: ".$code."</h1>
        <p>It will expire at ".$human_exp_time." EST</p>
        </hr>
        Use it here: ".SITE_HOME."accessCodePage.php",
        'Content-type: text/html; charset=utf-8\r\n'.
        'From: Teamwork Evaluation Access Code Generator <apache@buffalo.edu>');
      header("Location: emailConfirmation.php"); /* Redirect browser to a test link*/
  exit();
}
?>
<hr>
</div>

<!-- Footer -->
<footer id="footer" class="w3-container w3-theme-dark w3-padding-16">
  <h3>Acknowledgements</h3>
  <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a></p>
  <p> <a  class=" w3-theme-light" target="_blank"></a></p>
</footer>

</body>
</html>
