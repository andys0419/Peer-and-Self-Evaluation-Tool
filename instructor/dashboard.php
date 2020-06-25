<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

// start the session variable
session_start();

// bring in required code
require_once "../lib/database.php";
require_once "../lib/constants.php";
require_once "../lib/infoClasses.php";


// query information about the requester
$con = connectToDatabase();

// try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-blue.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/header.css">
    <title>UB CSE Peer Evaluation :: Dashboard</title>
</head>
<body>
  <div class="w3-container">
        <img src="../images/logo_UB.png" width="150" height="100" alt="UB Logo">
        <p style="font-size:250%;display:inline-block" >UB CSE Peer Evaluation</p>
  </div>
  
  <div class="icon-bar">
  <a class="active" href="dashboard.php"><i class="home"><img src="../icons/home.png" width="50" height="50" class="img-center">Home</i></a>

  <a href="surveys.php"><i class="survey"><img src ="../icons/survey.png" width="50" height="50" class="img-center"> Surveys</i></a>
  
  <a href="question-banks.php" class="disable"><i class="question"><img src="../icons/check.png" width="50" height="50"  class="img-center"> Question Banks </i></a>
  
  <a href="courses.php"><i class="courses"><img src ="../icons/online-learning.png" width="50" height="50" class="img-center">courses</i></a>
  
  <a href="logout.php" class="disable"><i class="logout"><img src="../icons/logout.png" width="50" height="50"  class="img-center"> Logout </i></a>
  
<a class="disable"> <i class="hello">Welcome, <?php echo htmlspecialchars($instructor->name); ?></a></i>

</div>
</body>
</html>
