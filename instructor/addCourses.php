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

//allocates fields from this 'addCourse' form into an array
$courseInfo = array('course-code','course-name','semester','course-year');

//stores error messages corresponding to form fields
$errorMsg = array();

//flag for error messages
$error = false;
if(isset($_POST['add'])) {

    foreach($courseInfo AS $info) {
    
        //check valid formatting
        if ($info == 'course-code') {
            
            if (!preg_match("/^[a-zA-Z0-9]*$/",$_POST[$info])) {
                //echo "<br>";
                $errorMsg["course-code"] = "<span class=\"error\">Please enter a valid course code.</span>";
                $error = true;
            }


        } elseif($info == 'course-name') {

            if (preg_match("/^[a-zA-Z0-9\/&\-',\\s]*$/", $_POST[$info])) {
                
            } else {
                //echo "<br>";
                $errorMsg["course-name"] = "<span class=\"error\">Please enter a valid course name.</span>";
                $error = true;

            }
        } elseif($info == 'semester') {
            

          //Prevent injections into 'semester' field
          if ($_POST[$info] == "fall" || $_POST[$info] == "winter" || $_POST[$info] == "spring" || $_POST[$info] == "summer") {
               
          } else {
             //echo "<br>";
             $errorMsg["semester"] = "<span class=\"error\">Please enter a valid semester.</span>"; 
             $error = true; 
          }
        
        }

        else {
            
            if ($info == 'course-year') {
            
                if(!preg_match("/^[0-9]*$/",$_POST[$info]) || strlen($_POST[$info]) != 4) {
                    //echo "<br>";
                    $errorMsg["course-year"] = "<span class=\"error\">Please enter a valid year.</span>";
                    $error = true;
                }

            }   
        }
    
    }

    if (!$error) {

        if (count($errorMsg) == 4) {
        $stmt = $con -> prepare("INSERT INTO TestCourses (course_code,course_name,course_semester,course_year) VALUES ($course_code, $course_name, $semester, $course_code)");
        $stmt -> execute();
        echo "<script>alert('Your course was added sucessfully!');</script>";
        exit();
        }

        
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
    <link rel="stylesheet" type="text/css" href="../styles/addCourses.css">
    <title>Add Courses</title>
</head>
<body>

    <div class="w3-container w3-center">
        <h2>Course Information</h2>
    </div>

<!--------form action="addCourses.php" once linked-------------------->
<form action="addCourses.php" method ="post" class="w3-container">
    <p><?php if(isset($errorMsg["course-code"])) {echo $errorMsg["course-code"];} ?></p>
    <label for="course-code">Course Code:</label><br>
    <input type="text" id="course-code" minlength=5 maxlength=6 class="w3-input w3-border w3-animate-input" style="width:30%" name="course-code" placeholder="e.g, CSE442" required><br>
    

    <p><?php if(isset($errorMsg["course-name"])) {echo $errorMsg["course-name"];}  ?></p>
    <label for="course-name">Course Name:</label><br>
    <input type="text" id="course-name" class="w3-input w3-border w3-animate-input" style="width:30%" name="course-name" placeholder="e.g, Software Engineering Concepts" required><br>

    <p><?php if(isset($errorMsg["semester"])) {echo $errorMsg["semester"];} ?></p>
    <label for="semester">Course Semester:</label><br>
    <select class="w3-select w3-border" style="width:30%" name="semester" required>
        <option value="" disabled selected>Choose semester:</option>
        <option value="fall">Fall</option>
        <option value="winter">Winter</option>
        <option value="spring">Spring</option>
        <option value="summer">Summer</option>
    </select><br><br>

    <p><?php if(isset($errorMsg["course-year"])) {echo $errorMsg["course-year"];} ?></p>
    <label for="year">Course Year:</label><br>
    <input type="number" min="2020" id="year" class="w3-input w3-border w3-animate-input" style="width:30%" name="course-year" placeholder="e.g, 2020" required><br>
    <input type="submit" name="add" value="Add">

</form>
</html>