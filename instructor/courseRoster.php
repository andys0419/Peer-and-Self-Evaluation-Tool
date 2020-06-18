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
require_once "../lib/fileParse.php";


// query information about the requester
$con = connectToDatabase();

// try to get information about the instructor who made this request by checking the session token and redirecting if invalid
$instructor = new InstructorInfo();
$instructor->check_session($con, 0);


// check for the query string or post parameter
$cid = NULL;
if($_SERVER['REQUEST_METHOD'] == 'GET')
{
  // respond not found on no query string parameter
  if (!isset($_GET['course']))
  {
    http_response_code(404);   
    echo "404: Not found.";
    exit();
  }

  // make sure the query string is an integer, reply 404 otherwise
  $cid = intval($_GET['course']);

  if ($cid === 0)
  {
    http_response_code(404);   
    echo "404: Not found.";
    exit();
  }
}
else
{
  // respond bad request if bad post parameters
  if (!isset($_POST['course']))
  {
    http_response_code(400);
    echo "Bad Request: Missing parmeters.";
    exit();
  }

  // make sure the post survey id is an integer, reply 400 otherwise
  $cid = intval($_POST['course']);

  if ($cid === 0)
  {
    http_response_code(400);
    echo "Bad Request: Invalid parameters.";
    exit();
  }
  
}

// try to look up info about the requested course and make sure the current instructor teaches it
$stmt = $con->prepare('SELECT code, name, semester, year FROM course WHERE id=? AND instructor_id=?');
$stmt->bind_param('ii', $cid, $instructor->id);
$stmt->execute();
$result = $stmt->get_result();
$course_info = $result->fetch_all(MYSQLI_ASSOC);

// reply forbidden if instructor did not create course or course does not exist
if ($result->num_rows == 0)
{
  http_response_code(403);   
  echo "403: Forbidden.";
  exit();
}

// now perform the possible roster modification functions // start here
// first set some flags
$errorMsg = array();

// check if the survey's pairings can be modified

/* 
if ($current_date > $stored_start_date)
{
  $errorMsg['modifiable'] = 'Survey already past start date.';
} */

/* // now perform the validation and parsing
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
  
  // now check for the agreement checkbox
  if (!isset($_POST['agreement']))
  {
    $errorMsg['agreement'] = 'Please read the statement next to the checkbox and check it if you agree.';
  }
  else if ($_POST['agreement'] != "1")
  {
    $errorMsg['agreement'] = 'Please read the statement next to the checkbox and check it if you agree.';
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
        $student_ids = check_pairings($pairing_mode, $emails, $survey_info[0]['course_id'], $con);
        
        // check for any errors
        if (isset($student_ids['error']))
        {
          $errorMsg['pairing-file'] = $student_ids['error'];
        }
        else
        {
          // finally delete the old pairings from the database and then add the new pairings to the database if no other error message were set so far
          if (empty($errorMsg))
          {
            $stmt = $con->prepare('DELETE FROM reviewers WHERE survey_id=?');
            $stmt->bind_param('i', $cid);
            $stmt->execute();
            
            add_pairings($pairing_mode, $emails, $student_ids, $cid, $con);
          }
        }
        
      }
    }
  }
  
} */

// finally, store information about course roster as array of array
$students = array();

// get information about the pairings
$stmt = $con->prepare('SELECT roster.student_id, students.name, students.email FROM roster JOIN students ON roster.student_id=students.student_id WHERE roster.course_id=? ORDER BY roster.id');
$stmt->bind_param('i', $cid);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
  $stu_info = array();
  $stu_info['name'] = $row['name'];
  $stu_info['email'] = $row['email'];
  array_push($students, $stu_info);
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
    <title>Course Roster</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Course Roster</h2>
        <p><?php echo $course_info[0]['code'] . ' ' . $course_info[0]['name'] . ' - ' . SEMESTER_MAP_REVERSE[$course_info[0]['semester']] . ' ' . $course_info[0]['year'] ?></p>
    </div>
    
    <?php
      // indicate any error messages
      if ($_SERVER['REQUEST_METHOD'] == 'POST')
      {
        if (!empty($errorMsg))
        {
          echo '<div class="w3-container w3-center w3-red">Course Roster Modification Failed. <br /> See error messages at the bottom of the page for more details.</div><br />';
        }
        else
        {
          echo '<div class="w3-container w3-center w3-green">Course Roster Modification Successful</div><br />';
        }
      }
    ?>
    
    <table style="width:100%;border:1px solid black;border-collapse:collapse;">
      <tr>
      <th>Name</th>
      <th>Email</th>
      </tr>
      <?php
        foreach ($students as $student)
        {
          echo '<tr><td>' . htmlspecialchars($student['name']) . '</td><td>' . htmlspecialchars($student['email']) . '</td></tr>';
        }
      ?>
    </table>
    <br />
    <a href="rosterDownload.php?course=<?php echo $cid; ?>" target="_blank"><button class="w3-button w3-blue">Download Course Roster as CSV File</button></a>
    <hr />
    <div class="w3-container w3-center">
        <h2>Modify Course Roster</h2>
    </div>
    <?php if (!isset($errorMsg['modifiable'])): ?>
      <form action="courseRoster.php?course=<?php echo $cid; ?>" method ="post" enctype="multipart/form-data" class="w3-container">
        
        <span class="w3-card w3-red"><?php if(isset($errorMsg["roster-file"])) {echo $errorMsg["roster-file"];} ?></span><br />
        <label for="roster-file">Roster (CSV File):</label><br>
        <input type="file" id="roster-file" class="w3-input w3-border" style="width:61%" name="roster-file"><br>
        
        <span class="w3-card w3-red"><?php if(isset($errorMsg["agreement"])) {echo $errorMsg["agreement"];} ?></span><br />
        <input type="checkbox" id="agreement" name="agreement" value="1">
        <label for="agreement">I understand that modifying the course roster will overwrite all previously supplied roster information for this course.</label><br /><br />
        
        <input type="hidden" name="course" value="<?php echo $cid; ?>" />
        
        <input type="submit" class="w3-button w3-blue" value="Modify Course Roster" />
      </form>
    <?php else: ?>
      <div class="w3-container w3-red">
        <p>Course rosters cannot be modified once surveys have been created for this course.</p>
      </div>
    <?php endif; ?>
    <br />

</body>
</html> 
