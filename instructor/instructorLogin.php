<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

// start the session variable
session_start();

// bring in required code
require_once "../lib/random.php";
require_once "../lib/database.php";
require_once "../lib/constants.php";
require_once "../lib/infoClasses.php";


// query information about the requester
$con = connectToDatabase();

// try to get information about the instructor who made this request by checking the session cookie
// redirect to home page if already logged in
$instructor = new InstructorInfo();
$instructor->check_session($con, 1);

// define needed variables
$email_error_message = "";
$email_error_message2 = "";

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
  $email = trim($_POST['email']);
  
  // store error messages
  if (empty($email))
  {
    $email_error_message = "Error: Email cannot be blank.";
  }
  
  // now, lookup the email in the database
  $stmt = $con->prepare('SELECT id FROM instructors WHERE email=?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);
  $id = NULL;
  
  // check if the email matches and store error messages or get the instructor id
  if ($result->num_rows == 0)
  {
    $email_error_message2 = "Error: Email Address Not in List of Registered Instructors.";
  }
  else
  {
    $id = $data[0]['id'];
  }
  
  // skip over if errors exist
  if (!$email_error_message and !$email_error_message2)
  {
    
    // generate the access code
    $access_code = random_digits(7);
    
    // hash the password
    $hashed_access_code = password_hash($access_code, PASSWORD_BCRYPT);
    
    // generate the expiration time
    $otp_expiration = time() + OTP_EXPIRATION_SECONDS;
    
    // generate the inital authorization cookie
    $initial_auth_cookie = random_bytes(TOKEN_SIZE);
    
    // hash the initial authorization cookie
    $hashed_cookie = hash_pbkdf2("sha256", $initial_auth_cookie, SESSIONS_SALT, PBKDF2_ITERS);
    
    // send the email with the access code
    mail($email,"Access Code for Teamwork Evaluations", "<h1>Your code is: ".$access_code."</h1>
        <p>It will expire in 15 minutes.</p>
        </hr>
        Use it here: ".SITE_HOME."instructor/instructorOTPEntry.php",
        'Content-type: text/html; charset=utf-8\r\n'.
        'From: Teamwork Evaluation Access Code Generator <apache@buffalo.edu>');
        
    // set the initial authorization cookie for 1 hour
    setcookie(INIT_AUTH_COOKIE_NAME, bin2hex($initial_auth_cookie), time() + INIT_AUTH_TOKEN_EXPIRATION_SECONDS);
    
    // store the access code, expiration, and authorization cookie into the database
    $stmt = $con->prepare('UPDATE instructors SET init_auth_id=?, otp=?, otp_expiration=? WHERE id=?');
    $stmt->bind_param('ssii', $hashed_cookie, $hashed_access_code, $otp_expiration, $id);
    $stmt->execute();
        
    // redirect to the next page    
    http_response_code(302);   
    header("Location: instructorOTPEntry.php");
    exit();
    
  }
  
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-blue.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/login.css">
    <title>UB CSE Peer Evaluation :: Instructor Login</title>
</head>
<body>
    <div class="w3-bar w3-black" style="background-color: #100e0e">
      <img src="../images/logo_UB.png" width="150" height="100" class="d-inline-block align-top" alt="UB Logo">
      <p style="font-size:250%;display:inline-block" >UB CSE Peer Evaluation (Instructor Login)</p>
    </div>
    
    <div class="form-group">
      <p>Welcome! Please enter your UB email address in order to receive an access code to login.</p><br />
      <span class="w3-red"><?php if ($email_error_message) {echo "$email_error_message";} elseif ($email_error_message2) {echo "$email_error_message2";} ?></span>
      <form method="post" action="instructorLogin.php">
        <label for="email">Email Address:</label><br />
        <input class = "w3-input w3-border" type="text" id="email" placeholder="UBITname@buffalo.edu" name="email" /><br />
        <input type="submit" class="w3-btn w3-dark-grey" value="Send Access Code" />
      </form>
    </div>
</body>
</html>
