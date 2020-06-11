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


// store information about courses as array of array
$courses = array();

// get information about the courses
$stmt = $con->prepare('SELECT code, name, semester, year FROM course WHERE instructor_id=? ORDER BY year DESC, semester DESC');
$stmt->bind_param('i', $instructor->id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
  $course_info = array();
  $course_info['code'] = $row['code'];
  $course_info['name'] = $row['name'];
  $course_info['semester'] = SEMESTER_MAP_REVERSE[$row['semester']];
  $course_info['year'] = $row['year'];
  array_push($courses, $course_info);
  
  
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
    <link rel="stylesheet" type="text/css" href="../styles/courses.css">
    <title>Courses</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Instructor Courses</h2>
    </div>

    <table border=1 style=width:100%>
      <tr>
      <th>Code</th>
      <th>Name</th>
      <th>Semester</th>
      <th>Instructor</th>
      </tr>
      <?php 
        foreach ($courses as $course)
        {
          echo '<tr><td>' . htmlspecialchars($course['code']) . '</td><td>' . htmlspecialchars($course['name']) . '</td><td>' . htmlspecialchars($course['semester']) . ' ' . htmlspecialchars($course['year']) . '</td><td>' . htmlspecialchars($instructor->name) . '</td></tr>';
        }
      ?>
    </table>
    <br />
<div class = "center">
    <a href="addCourses.php"><button class="w3-button w3-dark-grey">+ Add Course</button></a>
</div> 


</body>
</html> 
