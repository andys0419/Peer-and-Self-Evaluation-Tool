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

// set timezone
date_default_timezone_set('America/New_York');

// query information about the requester
$con = connectToDatabase();

// try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


// store information about surveys as array
$surveys = array();

// store information about courses as map of course ids
$courses = array();

//get information about all courses an instructor teaches
$stmt1 = $con->prepare('SELECT name, semester, year, code, id FROM course WHERE instructor_id=?');
$stmt1->bind_param('i', $instructor->id);
$stmt1->execute();
$result1 = $stmt1->get_result();

while ($row = $result1->fetch_assoc())
{
  $tempSurvey = array();
  $tempSurvey['name'] = $row['name'];
  $tempSurvey['semester'] = SEMESTER_MAP_REVERSE[$row['semester']];
  $tempSurvey['year'] = $row['year'];
  $tempSurvey['code'] = $row['code'];
  $tempSurvey['id'] = $row['id'];
  $courses[$row['id']] = $tempSurvey;
}

//Then, get information about courses an instructor has active surveys for
foreach($courses as $course) {
  
    $stmt2 = $con->prepare('SELECT course_id, start_date, expiration_date, rubric_id, id FROM surveys WHERE course_id=? ORDER BY start_date ASC, expiration_date ASC');
    $stmt2->bind_param('i', $course['id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    while ($row = $result2->fetch_assoc())
    {
        $survey_info = array();
        $survey_info['course_id'] = $row['course_id'];
        $survey_info['start_date'] = $row['start_date'];
        $survey_info['expiration_date'] = $row['expiration_date'];
        $survey_info['rubric_id'] = $row['rubric_id'];
        $survey_info['id'] = $row['id'];
        array_push($surveys, $survey_info);
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
    <link rel="stylesheet" type="text/css" href="../styles/surveys.css">
    <link rel="stylesheet" type="text/css" href="../styles/header.css">
    <title>Surveys</title>
</head>
<body>

 <div class="icon-bar">
  <a class="active" href="surveys.php"><i class="disable"><img src ="../icons/survey.png" width="50" height="50" class="img-center"> Surveys</i></a>
  
  <a href="question-banks.php" class="disable"><i class="question"><img src="../icons/check.png" width="50" height="50"  class="img-center"> Question Banks </i></a>
  
  <a href="courses.php"><i class="courses"><img src ="../icons/online-learning.png" width="50" height="50" class="img-center">Courses</i></a>
  
  <a href="logout.php" class="disable"><i class="logout"><img src="../icons/logout.png" width="50" height="50"  class="img-center"> Logout </i></a>

</div>


    <div class="w3-container w3-center">
        <h2>Instructor Surveys</h2>
    </div>
    
    <?php
      
      // echo the success message if any
      if (isset($_SESSION['survey-add']) and $_SESSION['survey-add'])
      {
        echo '<div class="w3-container w3-center w3-green">' . $_SESSION['survey-add'] . '</div><br />';
        $_SESSION['survey-add'] = NULL;
      }
      // echo deletion message
      else if (isset($_SESSION['survey-delete']) and $_SESSION['survey-delete'])
      {
        echo '<div class="w3-container w3-center w3-red">' . $_SESSION['survey-delete'] . '</div><br />';
        $_SESSION['survey-delete'] = NULL;
      }
      
    ?>

    <table class=w3-table border=1.0 style=width:100%>
        <tr>
        <th>Course</th>
        <th>Status</th>
        <th>Start Date and Time</th>
        <th>End Date and Time</th>
        <th>Question Bank</th>
        <th>Actions</th>
        </tr>
        <?php 
          $today = new DateTime();
          foreach ($surveys as $survey)
          {
            
            $s = new DateTime($survey['start_date']);
            $e = new DateTime($survey['expiration_date']);
            
            echo '<tr><td>' . htmlspecialchars($courses[$survey['course_id']]['code'] . ' ' . $courses[$survey['course_id']]['name'] . ' - ' . $courses[$survey['course_id']]['semester'] . ' ' . $courses[$survey['course_id']]['year']) . '</td><td>';
            
            if ($today < $s)
            {
              echo 'Upcoming';
            }
            else if ($today < $e)
            {
              echo 'Active';
            }
            else
            {
              echo 'Expired';
            }
            echo '</td><td>' . htmlspecialchars($survey['start_date']) . '</td><td>' . htmlspecialchars($survey['expiration_date']) . '</td><td>Default</td><td><a href="surveyPairings.php?survey=' . $survey['id'] . '">View or Edit Pairings</a> | <a href="surveyDelete.php?survey=' . $survey['id'] . '">Delete Survey</a></td></tr>';
          }
      ?>
    </table>
<body>

<div class = "center">
    <a href="addSurveys.php"><button class="w3-button w3-blue">+ Add Survey</button></a>
</div> 

</html> 
