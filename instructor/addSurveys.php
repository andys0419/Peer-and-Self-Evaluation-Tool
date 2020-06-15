<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

//start the session variable
session_start();

//bring in required code
require_once "../lib/database.php";
require_once "../lib/constants.php";
require_once "../lib/infoClasses.php";


//query information about the requester
$con = connectToDatabase();

//try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


//stores error messages corresponding to form fields
$errorMsg = array();

// set flags
$survey_type = NULL;
$course_id = NULL;
$rubric_id = NULL;
$pairing_file = NULL;
$start_date = NULL;
$end_date = NULL;
$start_time = NULL;
$end_time = NULL;

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-blue.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/addSurveys.css">
    <title>Add Surveys</title>
</head>
<body>

    <div class="w3-container w3-center">
        <h2>Survey Information</h2>
    </div>

<div id="columns">
<div id="left">
<span class="w3-card w3-red"><?php if(isset($errorMsg["duplicate"])) {echo $errorMsg["duplicate"];} ?></span>
<form action="addCourses.php" method ="post" class="w3-container">
    <span class="w3-card w3-red"><?php if(isset($errorMsg["survey-type"])) {echo $errorMsg["survey-type"];} ?></span><br />
    <label for="survey-type">Survey Type:</label><br>
    <select class="w3-select w3-border" style="width:61%" name="survey-type">
        <option value="" disabled <?php if (!$survey_type) {echo 'selected';} ?>>Choose Survey:</option>
        <option value="FIXME" <?php if ($survey_type == 1) {echo 'selected';} ?>>placeholder</option>
        <option value="FIXME" <?php if ($survey_type == 2) {echo 'selected';} ?>>placeholder</option>
    </select><br><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["rubric-id"])) {echo $errorMsg["rubric-id"];} ?></span><br />
    <label for="rubric-id">Survey Type:</label><br>
    <select class="w3-select w3-border" style="width:61%" name="rubric-id">
        <option value="" disabled <?php if (!$rubric_id) {echo 'selected';} ?>>Choose Survey:</option>
        <option value="FIXME" <?php if ($rubric_id == 1) {echo 'selected';} ?>>placeholder</option>
        <option value="FIXME" <?php if ($rubric_id == 2) {echo 'selected';} ?>>placeholder</option>
    </select><br><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["start-date"])) {echo $errorMsg["start-date"];} ?></span><br />
    <label for="start-date">Start Date:</label><br>
    <input type="date" id="start-date" class="w3-input w3-border" style="width:61%" name="start-date" placeholder="mm/dd/yyyy" <?php if ($start_date) {echo 'value="' . htmlspecialchars($start_date) . '"';} ?>><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["start-time"])) {echo $errorMsg["start-time"];} ?></span><br />
    <label for="start-time">Start time:</label><br>
    <input type="time" id="start-time" class="w3-input w3-border" style="width:61%" name="start-time" placeholder="e.g., 09:05:PM" <?php if ($start_time) {echo 'value="' . htmlspecialchars($start_time) . '"';} ?>><br>
    </div>

<div id="right">
    <span class="w3-card w3-red"><?php if(isset($errorMsg["course-id"])) {echo $errorMsg["course-id"];} ?></span><br />
    <label for="course-id">Question Bank:</label><br>
    <select class="w3-select w3-border" style="width:61%" name="course-id">
        <option value="" disabled <?php if (!$course_id) {echo 'selected';} ?>>Whateveritdoes:</option>
        <option value="FIXME" <?php if ($course_id == 1) {echo 'selected';} ?>>placeholder</option>
        <option value="FIXME" <?php if ($course_id == 2) {echo 'selected';} ?>>placeholder</option>
    </select><br><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["pairing-file"])) {echo $errorMsg["pairing-file"];} ?></span><br />
    <label for="pairing-file">Pairings:</label><br>
    <input type="text" id="pairing-file" class="w3-input w3-border" style="width:61%" name="pairing-file" placeholder="e.g, data.csv" <?php if ($pairing_file) {echo 'value="' . htmlspecialchars($pairing_file) . '"';} ?>><br>
    

    <span class="w3-card w3-red"><?php if(isset($errorMsg["end-date"])) {echo $errorMsg["end-date"];} ?></span><br />
    <label for="end-date">End Date:</label><br>
    <input type="date" id="end-date" class="w3-input w3-border" style="width:61%" name="end-date" placeholder="mm/dd/yyyy" <?php if ($end_date) {echo 'value="' . htmlspecialchars($end_date) . '"';} ?>><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["end-time"])) {echo $errorMsg["end-time"];} ?></span><br />
    <label for="end-time">End time:</label><br>
    <input type="time" id="end-time" class="w3-input w3-border" style="width:61%" name="end-time" placeholder="e.g., 11:59:PM" <?php if ($end_time) {echo 'value="' . htmlspecialchars($end_time) . '"';} ?>><br>
    </div>
</div>
    <p id="submission"><input type="submit" value="Add Survey"></p>
</form>
</html>