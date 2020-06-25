<?php

//error logging
error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);
ini_set("error_log", "~/php-error.log");

// //start the session variable
session_start();

// //bring in required code
require_once "../lib/database.php";
require_once "../lib/constants.php";
require_once "../lib/infoClasses.php";
require_once "../lib/fileParse.php";


// set timezone
date_default_timezone_set('America/New_York');


// //query information about the requester
$con = connectToDatabase();

// //try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


// store information about courses as array of array
$courses = array();

// get information about the courses
$stmt = $con->prepare('SELECT id, code, name, semester, year FROM course WHERE instructor_id=? ORDER BY year DESC, semester DESC');
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
  $course_info['id'] = $row['id'];
  array_push($courses, $course_info);
}

//stores error messages corresponding to form fields
$errorMsg = array();

// set flags
$course_id = NULL;
$rubric_id = NULL;
$start_date = NULL;
$end_date = NULL;
$start_time = NULL;
$end_time = NULL;
$pairing_mode = NULL;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
  
  // make sure values exist
  if (!isset($_POST['pairing-mode']) or !isset($_FILES['pairing-file']) or !isset($_POST['start-date']) or !isset($_POST['start-time']) or !isset($_POST['end-date']) or !isset($_POST['end-time']) or !isset($_POST['csrf-token']))
  {
    http_response_code(400);
    echo "Bad Request: Missing parmeters.";
    exit();
  }
  
  // check CSRF token
  if (!hash_equals($instructor->csrf_token, $_POST['csrf-token']))
  {
    http_response_code(403);
    echo "Forbidden: Incorrect parameters.";
    exit();
  }
  
  // check course is not empty
  if (!isset($_POST['course-id']))
  {
    $errorMsg['course-id'] = "Please choose a course.";
  }
  else
  {
    $course_id = trim($_POST['course-id']);
    if (empty($course_id))
    {
      $errorMsg['course-id'] = "Please choose a course.";
    }
    // make sure the instructor is teaching the course
    else
    {
      $course_id = intval($course_id);
      if ($course_id === 0)
      {
        $errorMsg['course-id'] = "Please choose a valid course.";
      }
      
      $stmt = $con->prepare('SELECT year FROM course WHERE id=? AND instructor_id=?');
      $stmt->bind_param('ii', $course_id, $instructor->id);
      $stmt->execute();
      $result = $stmt->get_result();
      $data = $result->fetch_all(MYSQLI_ASSOC);

      // reply forbidden if instructor did not create survey
      if ($result->num_rows == 0)
      {
        $errorMsg['course-id'] = "Please choose a valid course.";
      }
    }
  }

  $start_date = trim($_POST['start-date']);
  $end_date = trim($_POST['end-date']);
  if (empty($start_date))
  {
    $errorMsg['start-date'] = "Please choose a start date.";
  }
  if (empty($end_date))
  {
    $errorMsg['end-date'] = "Please choose a end date.";
  }
  
  // check the date's validity
  if (!isset($errorMsg['start-date']) and !isset($errorMsg['end-date']))
  {
    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$start)
    {
      $errorMsg['start-date'] = "Please choose a valid start date (YYYY-MM-DD)";
    }
    else if ($start->format('Y-m-d') != $start_date)
    {
      $errorMsg['start-date'] = "Please choose a valid start date (YYYY-MM-DD)";
    }
    
    $end = DateTime::createFromFormat('Y-m-d', $end_date);
    if (!$end)
    {
      $errorMsg['end-date'] = "Please choose a valid end date (YYYY-MM-DD)";
    }
    else if ($end->format('Y-m-d') != $end_date)
    {
      $errorMsg['end-date'] = "Please choose a valid end date (YYYY-MM-DD)";
    }
  }


  $start_time = trim($_POST['start-time']);
  $end_time = trim($_POST['end-time']);

  if (empty($start_time))
  {
    $errorMsg['start-time'] = "Please choose a start time.";
  }
  if (empty($end_time))
  {
    $errorMsg['end-time'] = "Please choose a end time.";
  }
  
  if (!isset($errorMsg['start-time']) and !isset($errorMsg['end-time']))
  {
    $start = DateTime::createFromFormat('H:i', $start_time);
    if (!$start)
    {
      $errorMsg['start-time'] = "Please choose a valid start time (HH:MM) (Ex: 15:00)";
    }
    else if ($start->format('H:i') != $start_time)
    {
      $errorMsg['start-time'] = "Please choose a valid start time (HH:MM) (Ex: 15:00)";
    }
    
    $end = DateTime::createFromFormat('H:i', $end_time);
    if (!$end)
    {
      $errorMsg['end-time'] = "Please choose a valid end time (HH:MM) (Ex: 15:00)";
    }
    else if ($end->format('H:i') != $end_time)
    {
      $errorMsg['end-time'] = "Please choose a valid end time (HH:MM) (Ex: 15:00)";
    }
  }
  
  // check dates and times
  if (!isset($errorMsg['start-date']) and !isset($errorMsg['start-time']) and !isset($errorMsg['end-date']) and !isset($errorMsg['end-time']))
  {
    $s = new DateTime($start_date . ' ' . $start_time);
    $e = new DateTime($end_date . ' ' . $end_time);
    $today = new DateTime();
    
    if ($e < $s)
    {
      $errorMsg['end-date'] = "End date and time cannot be before start date and time.";
      $errorMsg['end-time'] = "End date and time cannot be before start date and time.";
      $errorMsg['start-date'] = "End date and time cannot be before start date and time.";
      $errorMsg['start-time'] = "End date and time cannot be before start date and time.";
    }
    else if ($e < $today)
    {
      $errorMsg['end-date'] = "End date and time must occur in the future.";
      $errorMsg['end-time'] = "End date and time must occur in the future.";
    }
  }
  
  // check the pairing mode
  $pairing_mode = trim($_POST['pairing-mode']);
  if (empty($pairing_mode))
  {
    $errorMsg['pairing-mode'] = 'Please choose a valid mode for the pairing file.';
  }
  else if ($pairing_mode != '1' and $pairing_mode != '2')
  {
    $errorMsg['pairing-mode'] = 'Please choose a valid mode for the pairing file.';
  }
  
  // validate the uploaded file
  if ($_FILES['pairing-file']['error'] == UPLOAD_ERR_INI_SIZE)
  {
    $errorMsg['pairing-file'] = 'The selected file is too large.';
  }
  else if ($_FILES['pairing-file']['error'] == UPLOAD_ERR_PARTIAL)
  {
    $errorMsg['pairing-file'] = 'The selected file was only paritally uploaded. Please try again.';
  }
  else if ($_FILES['pairing-file']['error'] == UPLOAD_ERR_NO_FILE)
  {
    $errorMsg['pairing-file'] = 'A pairing file must be provided.';
  }
  else if ($_FILES['pairing-file']['error'] != UPLOAD_ERR_OK)
  {
    $errorMsg['pairing-file'] = 'An error occured when uploading the file. Please try again.';
  }
  // start parsing the file
  else
  {
    // start parsing the file
    $file_string = file_get_contents($_FILES['pairing-file']['tmp_name']);
    
    // get rid of BOM if it exists
    if (strlen($file_string) >= 3)
    {
      if ($file_string[0] == "\xef" and $file_string[1] == "\xbb" and $file_string[2] == "\xbf")
      {
        $file_string = substr($file_string, 3);
      }
    }
    
    // catch errors or continue parsing the file
    if ($file_string === false)
    {
      $errorMsg['pairing-file'] = 'An error occured when uploading the file. Please try again.';
    }
    else
    {
      $emails = parse_pairings($pairing_mode, $file_string);

      // check for any errors
      if (isset($emails['error']))
      {
        $errorMsg['pairing-file'] = $emails['error'];
      }
      else
      {
        
        // now make sure the users are in the course roster
        $student_ids = check_pairings($pairing_mode, $emails, $course_id, $con);
        
        // check for any errors
        if (isset($student_ids['error']))
        {
          $errorMsg['pairing-file'] = $student_ids['error'];
        }
        else
        {
          // finally add the pairings to the database if no other error message were set so far
          // first add the survey details to the database
          if (empty($errorMsg))
          {
            $sdate = $start_date . ' ' . $start_time;
            $edate = $end_date . ' ' . $end_time;
            $stmt = $con->prepare('INSERT INTO surveys (course_id, start_date, expiration_date, rubric_id) VALUES (?, ?, ?, 0)');
            $stmt->bind_param('iss', $course_id, $sdate, $edate);
            $stmt->execute();

            add_pairings($pairing_mode, $emails, $student_ids, $con->insert_id, $con);

            // redirect to survey page with sucess message
            $_SESSION['survey-add'] = "Successfully added survey.";

            http_response_code(302);   
            header("Location: surveys.php");
            exit();
          }
        }
        
      }
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
    <link rel="stylesheet" type="text/css" href="../styles/addSurveys.css">
    <title>Add Surveys</title>
</head>
<body>

  <div class="icon-bar">
    <a href="surveys.php"><i class="survey"><img src ="../icons/survey.png" width="50" height="50" class="img-center"> Surveys</i></a>
  
    <a href="question-banks.php" class="disable"><i class="question"><img src="../icons/check.png" width="50" height="50"  class="img-center"> Question Banks </i></a>
  
    <a class="active" href="courses.php"><i class="disable"><img src ="../icons/online-learning.png" width="50" height="50" class="img-center">Courses</i></a>
  
    <a href="logout.php" class="disable"><i class="logout"><img src="../icons/logout.png" width="50" height="50"  class="img-center"> Logout </i></a>

  </div>

  <div class="w3-container w3-center">
    <h2>Survey Information</h2>
  </div>


<form action="addSurveys.php" method ="post" enctype="multipart/form-data" class="w3-container">
    <span class="w3-card w3-red"><?php if(isset($errorMsg["course-id"])) {echo $errorMsg["course-id"];} ?></span><br />
    <label for="course-id">Course:</label><br>
    <select id="course-id" class="w3-select w3-border" style="width:61%" name="course-id"><?php if ($course_id) {echo 'value="' . htmlspecialchars($course_id) . '"';} ?>
        <option value="-1" disabled <?php if (!$course_id) {echo 'selected';} ?>>Select Course</option>
        <?php
        foreach ($courses as $course) {
          if ($course_id == $course['id'])
          {
            echo '<option value="' . $course['id'] . '" selected>' . htmlspecialchars($course['code']) . ' ' . htmlspecialchars($course['name']) . ' - ' . htmlspecialchars($course['semester']) . ' ' . htmlspecialchars($course['year']) . '</option>';
          }
          else
          {
            echo '<option value="' . $course['id'] . '" >' . htmlspecialchars($course['code']) . ' ' . htmlspecialchars($course['name']) . ' - ' . htmlspecialchars($course['semester']) . ' ' . htmlspecialchars($course['year']) . '</option>';
          }
        }
        ?>
    </select><br><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["rubric-id"])) {echo $errorMsg["rubric-id"];} ?></span><br />
    <label for="rubric-id">Question Bank:</label><br>
    <select class="w3-select w3-border" style="width:61%" name="rubric-id" id="rubric-id" disabled>
        <option value="0" selected>Default</option>
    </select><br><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["start-date"])) {echo $errorMsg["start-date"];} ?></span><br />
    <label for="start-date">Start Date:</label><br>
    <input type="date" id="start-date" class="w3-input w3-border" style="width:61%" name="start-date" <?php if ($start_date) {echo 'value="' . htmlspecialchars($start_date) . '"';} ?>><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["start-time"])) {echo $errorMsg["start-time"];} ?></span><br />
    <label for="start-time">Start time:</label><br>
    <input type="time" id="start-time" class="w3-input w3-border" style="width:61%" name="start-time" <?php if ($start_time) {echo 'value="' . htmlspecialchars($start_time) . '"';} ?>><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["end-date"])) {echo $errorMsg["end-date"];} ?></span><br />
    <label for="end-date">End Date:</label><br>
    <input type="date" id="end-date" class="w3-input w3-border" style="width:61%" name="end-date" <?php if ($end_date) {echo 'value="' . htmlspecialchars($end_date) . '"';} ?>><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["end-time"])) {echo $errorMsg["end-time"];} ?></span><br />
    <label for="end-time">End time:</label><br>
    <input type="time" id="end-time" class="w3-input w3-border" style="width:61%" name="end-time" <?php if ($end_time) {echo 'value="' . htmlspecialchars($end_time) . '"';} ?>><br>

    <span class="w3-card w3-red"><?php if(isset($errorMsg["pairing-mode"])) {echo $errorMsg["pairing-mode"];} ?></span><br />
    <label for="pairing-mode">Pairing File Mode:</label><br>
    <select id="pairing-mode" class="w3-select w3-border" style="width:61%" name="pairing-mode">
        <option value="1" <?php if (!$pairing_mode) {echo 'selected';} ?>>Raw</option>
        <option value="2" <?php if ($pairing_mode == 2) {echo 'selected';} ?>>Team</option>
    </select><br><br>
    
    <span class="w3-card w3-red"><?php if(isset($errorMsg["pairing-file"])) {echo $errorMsg["pairing-file"];} ?></span><br />
    <label for="pairing-file">Pairings (CSV File):</label><br>
    <input type="file" id="pairing-file" class="w3-input w3-border" style="width:61%" name="pairing-file"><br>

    <input type="hidden" name="csrf-token" value="<?php echo $instructor->csrf_token; ?>" />
    
    <input type="submit" class="w3-button w3-blue" value="Create Survey">
</form>
</body>
</html>