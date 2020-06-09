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
//Check the presence of the init auth cookie. 
//If cookie is not found, redirects user to instructorLogin page. 
if(!isset($_COOKIE['init-auth'])){
	header("Location: https://www-student.cse.buffalo.edu/CSE442-542/2020-Summer/cse-442b/production/instructor/instructorLogin.php");
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
    <link rel="stylesheet" type="text/css" href="../styles/nav-header.css">
    <title>Instructor Webpage</title>
<ul> 
  <li><a href="dashboard.php">Home</a></li>
  <li><a href="surveys.php">Surveys</a></li>
  <li><a href="question-banks.php">Question Banks</a></li>
  <li><a href="courses.php">Courses</a></li>
  <li><a href="logout.php">Logout</a></li> 
</ul>
</head>
<body>

