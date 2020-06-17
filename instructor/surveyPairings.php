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


// check for the query string parameter
// respond not found on no query string parameter
if (!isset($_GET['survey']))
{
  http_response_code(404);   
  echo "404: Not found.";
  exit();
}

// make sure the query string is an integer, reply 404 otherwise
$sid = intval($_GET['survey']);

if ($sid === 0)
{
  http_response_code(404);   
  echo "404: Not found.";
  exit();
}

// try to look up info about the requested survey
$survey_info = array();

$stmt = $con->prepare('SELECT course_id, start_date, expiration_date, rubric_id FROM surveys WHERE id=?');
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();

$survey_info = $result->fetch_all(MYSQLI_ASSOC);

// reply not found on no match
if ($result->num_rows == 0)
{
  http_response_code(404);   
  echo "404: Not found.";
  exit();
}

// make sure the survey is for a course the current instructor actually teaches
$stmt = $con->prepare('SELECT year FROM course WHERE id=? AND instructor_id=?');
$stmt->bind_param('ii', $survey_info[0]['course_id'], $instructor->id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// reply forbidden if instructor did not create survey
if ($result->num_rows == 0)
{
  http_response_code(403);   
  echo "403: Forbidden.";
  exit();
}

// finally, store information about survey pairings as array of array
$pairings = array();

// get information about the pairings
$stmt = $con->prepare('SELECT reviewer_email, teammate_email FROM reviewers WHERE survey_id=? ORDER BY id');
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
  $pair_info = array();
  $pair_info['reviewer_email'] = $row['reviewer_email'];
  $pair_info['teammate_email'] = $row['teammate_email'];
  array_push($pairings, $pair_info);
}

// now get the names for each pairing
$stmt = $con->prepare('SELECT name FROM students WHERE email=?');

$size = count($pairings);
for ($i = 0; $i < $size; $i++)
{
  $stmt->bind_param('s', $pairings[$i]['reviewer_email']);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);
  $pairings[$i]['reviewer_name'] = $data[0]['name'];
  
  $stmt->bind_param('s', $pairings[$i]['teammate_email']);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);
  $pairings[$i]['teammate_name'] = $data[0]['name'];
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
    <title>Survey Pairings</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Survey Pairings</h2>
    </div>
    
    <table style="width:100%;border:1px solid black;border-collapse:collapse;">
      <tr>
      <th>Reviewer</th>
      <th>Reviewee</th>
      </tr>
      <?php
        foreach ($pairings as $pair)
        {
          echo '<tr><td>' . htmlspecialchars($pair['reviewer_name']) . ' (' . htmlspecialchars($pair['reviewer_email']) . ')</td><td>' . htmlspecialchars($pair['teammate_name']) . ' (' . htmlspecialchars($pair['teammate_email']) . ')</td></tr>';
        }
      ?>
    </table>
    <br />
    <a href="pairingDownload.php?survey=<?php echo $sid; ?>" target="_blank"><button class="w3-button w3-blue">Download Pairings as CSV File</button></a>
    <hr />
    <div class="w3-container w3-center">
        <h2>Modify Survey Pairings</h2>
    </div>
    <form action="surveyPairings.php" method ="post" enctype="multipart/form-data" class="w3-container">
      <span class="w3-card w3-red"></span><br />
      <label for="pairing-mode">Pairing File Mode:</label><br>
      <select id="pairing-mode" class="w3-select w3-border" style="width:61%" name="pairing-mode">
          <option value="1" >Raw</option>
          <option value="2" >Team</option>
      </select><br><br>
      
      <span class="w3-card w3-red"></span><br />
      <label for="pairing-file">Pairings (CSV File):</label><br>
      <input type="file" id="pairing-file" class="w3-input w3-border" style="width:61%" name="pairing-file" placeholder="e.g, data.csv"><br>
      
      <span class="w3-card w3-red"></span><br />
      <input type="checkbox" id="agreement" name="agreement" value="1">
      <label for="agreement">I understand that modifying survey pairings will overwrite all previously supplied pairings for this survey. In addition, any scores associated with these prior pairings will be lost.</label><br>
    </form>
    <br />

</body>
</html> 
