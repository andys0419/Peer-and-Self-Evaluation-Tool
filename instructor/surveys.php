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


// store information about surveys as array of array
$surveys = array();
$tempSurvey = array();

//get information about all courses an instructor teaches
$stmt1 = $con->prepare('SELECT code FROM course WHERE instructor_id=?');
$stmt1->bind_param('i', $instructor->id);
$stmt1->execute();
$result1 = $stmt1->get_result();

$index = 0;
while ($row = $result->fetch_assoc())
{
    foreach ($row as $temp_code)
    {
        $tempSurvey[index] = $temp_code;
        $index++;

    }
}

//then, get information about courses an instructor has active surveys for
foreach($tempSurvey as $course_code) {
    $stmt2 = $con->prepare('SELECT course_id, start_date, expiration_date, rubric_id  FROM surveys WHERE course_id=? ORDER BY start_date ASC, expiration_date ASC');
    $stmt2->bind_param('i', $course_code);
    $stmt2->execute();
    $result1 = $stmt2->get_result();
    
    while ($row = $result1->fetch_assoc())
    {
        $survey_info = array();
        $survey_info['course_id'] = $row['course_id'];
        $survey_info['start_date'] = $row['start_date'];
        $survey_info['expiration_date'] = $row['expiration_date'];
        $survey_info['rubric_id'] = $row['rubric_id'];
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
    <title>Surveys</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Instructor Surveys</h2>
    </div>

    <table class=w3-table border=1.0 style=width:100%>
        <tr>
        <th>Course</th>
        <th>Questions</th>
        <th>Completed Surveys</th>
        <th>Start</th>
        <th>End</th>
        </tr>
        <?php 
        foreach ($surveys as $survey)
        {
          echo '<tr><td>' . htmlspecialchars($survey['course_id']) . '</td><td>' . htmlspecialchars($survey['start_date']) . '</td><td>' . htmlspecialchars($survey['expiration_date']) . ' ' . htmlspecialchars($survey['rubric_id']) . '</td></tr>';
        }
      ?>
    </table>
<body>

<div class = "center">
    <!---Redirect to addSurveys.html. Once backend linked, then addSurveys.php------------------->
    <input type='submit' name = "addSurveys" onclick="window.location.href = 'addSurveys.php';" class="w3-button w3-dark-grey" value="+ Add Survey"/></input>
</div> 

</html> 