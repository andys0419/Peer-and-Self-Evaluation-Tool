<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

// start the session variable
session_start();

// bring in required code
require "../lib/database.php";
require "../lib/constants.php";

// handle access code submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
  
  // connect to the database
  $con = connectToDatabase();
  
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
    <link rel="stylesheet" type="text/css" href="../styles/accessCode.css">
    <title>UB CSE Peer Evaluation :: Instructor Access Code Entry</title>
</head>
<body>
    <div class="w3-bar w3-black" style="background-color: #100e0e">
      <img src="../images/logo_UB.png" width="150" height="100" class="d-inline-block align-top" alt="UB Logo">
      <p style="font-size:250%;display:inline-block" >UB CSE Peer Evaluation (Instructor Login)</p>
    </div>
    
    <div class="form-group">
    
      <?php 
        if (isset($_SESSION['email-entry']) and $_SESSION['email-entry'])
        {
          echo '<div class="w3-card w3-green"> Successfully sent email to ' . htmlspecialchars($_SESSION['email-entry'][0]) . '<br /> The access code expires in 15 minutes.</div>';
        }
      ?>
      
      <br />
      <br />
      <p>Please enter the access code sent to your email.</p><br />
      <span class="w3-red"></span>
      <form method="post" action="instructorOTPEntry.php">
        <label for="otp">Access Code:</label><br />
        <input class = "w3-input w3-border" type="text" id="otp" placeholder="#######" name="otp" /><br />
        <input type="submit" class="w3-btn w3-dark-grey" value="Submit Access Code" />
        <a href="instructorLogin.php"><button type='button' class="w3-button w3-dark-grey" />Don't have a valid code?</button></a>
      </form>
    </div>
</html>
