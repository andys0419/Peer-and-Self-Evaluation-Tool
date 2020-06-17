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
require_once "../lib/fileParse.php";


//query information about the requester
$con = connectToDatabase();

//try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


//stores error messages corresponding to form fields
$errorMsg = array();

// store information about course codes in an array
$courseCodes = array();

// get information about the courses
$stmt = $con->prepare('SELECT code FROM course WHERE instructor_id=? ORDER BY code ASC');
$stmt->bind_param('i', $instructor->id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
  array_push($courseCodes, $row['code']);
}

// set flags
$course_id = NULL;
$rubric_id = NULL;
$pairing_file = NULL;
$start_date = NULL;
$end_date = NULL;
$start_time = NULL;
$end_time = NULL;
$pairing_mode = NULL;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
  
  // make sure values exist
  if (!isset($_POST['pairing-mode']) or !isset($_FILES['pairing-file']))
  {
    http_response_code(400);
    echo "Bad Request: Missing parmeters.";
    exit();
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
  
  // check for any uploaded file errors
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
  $file_string = file_get_contents($_FILES['pairing-file']['tmp_name']);
  else
  {
    // start parsing the file
    $file_string = file_get_contents($_FILES['pairing-file']['tmp_name']);

    // catch errors or continue parsing the file
    if ($file_string === false)
    {
      $errorMsg['pairing-file'] = 'An error occured when uploading the file. Please try again.';
    }
    else
    {
      $data = parse_pairings($pairing_mode, $file_string);
      echo var_dump($data);
    }
  }
  
  
  // check if main fields are valid
  if (!empty($errorMsg))
  {
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

<div class="w3-container w3-center">
    <h2>Survey Information</h2>
</div>


<span class="w3-card w3-red"><?php if(isset($errorMsg["duplicate"])) {echo $errorMsg["duplicate"];} ?></span>
<form action="addSurveys.php" method ="post" enctype="multipart/form-data" class="w3-container">
    <span class="w3-card w3-red"><?php if(isset($errorMsg["course_id"])) {echo $errorMsg["course_id"];} ?></span><br />
    <label for="course_id">Course:</label><br>
    <select id="course_id" class="w3-select w3-border" style="width:61%" name="course_id">
        <option value="" disabled <?php if (!$course_id) {echo 'selected';} ?>>Select Course</option>
        <?php
        //$index = 0;
        foreach ($courseCodes as $courseCode) {
          $index++;
          echo "<option value='$courseCode'>$courseCode</option>";
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
    <input type="file" id="pairing-file" class="w3-input w3-border" style="width:61%" name="pairing-file" placeholder="e.g, data.csv" <?php if ($pairing_file) {echo 'value="' . htmlspecialchars($pairing_file) . '"';} ?>><br>

    <input type="submit" class="w3-button w3-blue" value="Create Survey">
</form>
</body>
</html>


