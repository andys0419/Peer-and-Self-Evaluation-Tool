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
$course_code = NULL;
$course_name = NULL;
$semester = NULL;
$course_year = NULL;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
  
  // make sure values exist
  if (!isset($_POST['course-code']) or !isset($_POST['course-name']) or !isset($_POST['course-year']))
  {
    http_response_code(400);
    echo "Bad Request: Missing parmeters.";
    exit();
  }
  
  //check valid formatting
  $course_code = trim($_POST['course-code']);
  if (empty($course_code))
  {
    $errorMsg['course-code'] = 'Course code cannot be blank.';
  }
  else if (!preg_match("/^[a-zA-Z0-9]*$/", $course_code))
  {
    $errorMsg["course-code"] = "Please enter a valid course code.";
  }
  
  $course_name = trim($_POST['course-name']);
  if (empty($course_name))
  {
    $errorMsg['course-name'] = 'Course name cannot be blank.';
  }
  else if (!preg_match("/^[a-zA-Z0-9\/&\-',\\s]*$/", $course_name))
  {    
    $errorMsg["course-name"] = "Please enter a valid course name.";
  } 

  if (!isset($_POST['semester']))
  {
    $errorMsg['semester'] = 'Please choose a semester.';
  }
  else
  {
    $semester = trim($_POST['semester']);         
    if (empty($semester))
    {
      $errorMsg['semester'] = 'Please choose a semester.';
    }
    //Prevent injections into 'semester' field
    else if ($semester != "fall" and $semester != "winter" and $semester != "spring" and $semester != "summer")
    {
       $errorMsg["semester"] = "Please select a valid semester."; 
    }
    
    $semester = SEMESTER_MAP[$semester];
  }
  
  $course_year = trim($_POST['course-year']);
  if (empty($course_year))
  {
    $errorMsg['course-year'] = 'Course year cannot be blank.';
  }
  else if(!preg_match("/^[0-9]*$/",$course_year) || strlen($course_year) != 4)
  {
    $errorMsg["course-year"] = "Please enter a valid year.";
  }

  // check if fields are all valid
  if (empty($errorMsg))
  {
    
    // check for duplicate courses
    $stmt = $con->prepare('SELECT id FROM course WHERE code=? AND name=? AND semester=? AND year=? AND instructor_id=?');
    $stmt->bind_param('ssiii', $course_code, $course_name, $semester, $course_year, $instructor->id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // only add if not a duplicate
    if ($result->num_rows == 0)
    {
      $stmt = $con->prepare('INSERT INTO course (code, name, semester, year, instructor_id) VALUES (?, ?, ?, ?, ?)');
      $stmt->bind_param('ssiii', $course_code, $course_name, $semester, $course_year, $instructor->id);
      $stmt->execute();
      
      // redirect to course page with message
      $_SESSION['course-add'] = "Successfully added course: " . htmlspecialchars($course_code) . ' - ' . htmlspecialchars($course_name) . ' - ' . SEMESTER_MAP_REVERSE[$semester] . ' ' . htmlspecialchars($course_year);
      
      http_response_code(302);   
      header("Location: courses.php");
      exit();
      
    }
    else
    {
      $errorMsg['duplicate'] = 'Error: The entered course already exists.';
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

<span class="w3-card w3-red"><?php if(isset($errorMsg["duplicate"])) {echo $errorMsg["duplicate"];} ?></span>
<form action="addCourses.php" method ="post" class="w3-container">
    <span class="w3-card w3-red"><?php if(isset($errorMsg["course-code"])) {echo $errorMsg["course-code"];} ?></span><br />
    <label for="course-code">Course Code:</label><br>
    <input type="text" id="course-code" class="w3-input w3-border" style="width:30%" name="course-code" placeholder="e.g, CSE442" <?php if ($course_code) {echo 'value="' . htmlspecialchars($course_code) . '"';} ?>><br>
    

    <span class="w3-card w3-red"><?php if(isset($errorMsg["course-name"])) {echo $errorMsg["course-name"];} ?></span><br />
    <label for="course-name">Course Name:</label><br>
    <input type="text" id="course-name" class="w3-input w3-border" style="width:30%" name="course-name" placeholder="e.g, Software Engineering Concepts" <?php if ($course_name) {echo 'value="' . htmlspecialchars($course_name) . '"';} ?>><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["semester"])) {echo $errorMsg["semester"];} ?></span><br />
    <label for="semester">Course Semester:</label><br>
    <select class="w3-select w3-border" style="width:30%" name="semester">
        <option value="" disabled <?php if (!$semester) {echo 'selected';} ?>>Choose semester:</option>
        <option value="fall" <?php if ($semester == 4) {echo 'selected';} ?>>Fall</option>
        <option value="winter" <?php if ($semester == 1) {echo 'selected';} ?>>Winter</option>
        <option value="spring" <?php if ($semester == 2) {echo 'selected';} ?>>Spring</option>
        <option value="summer" <?php if ($semester == 3) {echo 'selected';} ?>>Summer</option>
    </select><br><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["course-year"])) {echo $errorMsg["course-year"];} ?></span><br />
    <label for="year">Course Year:</label><br>
    <input type="number" id="year" class="w3-input w3-border" style="width:30%" name="course-year" placeholder="e.g, 2020" <?php if ($course_year) {echo 'value="' . htmlspecialchars($course_year) . '"';} ?>><br>
    <input type="submit" value="Add Course">

</form>
</html>