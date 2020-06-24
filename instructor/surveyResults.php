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


// respond not found on no query string parameter
$sid = NULL;
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

// now, get information about survey pairings and scores as array of array
$pairings = array();

// get information about the pairings
$stmt = $con->prepare('SELECT id, reviewer_id, reviewee_id, reviewer_email, teammate_email FROM reviewers WHERE survey_id=? ORDER BY reviewee_id');
$stmt->bind_param('i', $sid);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
  $pair_info = array();
  $pair_info['reviewer_email'] = $row['reviewer_email'];
  $pair_info['teammate_email'] = $row['teammate_email'];
  $pair_info['id'] = $row['id'];
  $pair_info['reviewer_id'] = $row['reviewer_id'];
  $pair_info['reviewee_id'] = $row['reviewee_id'];
  array_push($pairings, $pair_info);
}

// now get the names for each pairing and scores for each pairing
$stmt = $con->prepare('SELECT name FROM students WHERE email=?');
$stmt_scores = $con->prepare('SELECT score1, score2, score3, score4, score5 FROM scores WHERE reviewers_id=?');

$size = count($pairings);
for ($i = 0; $i < $size; $i++)
{
  // first, get names
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
  
  // now, get score information
  $stmt_scores->bind_param('i', $pairings[$i]['id']);
  $stmt_scores->execute();
  $result_scores = $stmt_scores->get_result();
  $data_scores = $result_scores->fetch_all(MYSQLI_ASSOC);
  
  if ($result_scores->num_rows == 0)
  {
    $pairings[$i]['score1'] = NO_SCORE_MARKER;
    $pairings[$i]['score2'] = NO_SCORE_MARKER;
    $pairings[$i]['score3'] = NO_SCORE_MARKER;
    $pairings[$i]['score4'] = NO_SCORE_MARKER;
    $pairings[$i]['score5'] = NO_SCORE_MARKER;
  }
  else
  {
    $pairings[$i]['score1'] = $data_scores[0]['score1'];
    $pairings[$i]['score2'] = $data_scores[0]['score2'];
    $pairings[$i]['score3'] = $data_scores[0]['score3'];
    $pairings[$i]['score4'] = $data_scores[0]['score4'];
    $pairings[$i]['score5'] = $data_scores[0]['score5'];
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
    <title>Survey Results</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Download Survey Results</h2>
        <a href="resultsDownload.php?survey_id=<?php echo $sid; ?>&type=raw"><button class="w3-button w3-blue">Download Raw Survey Results</button></a>
        <a href="resultsDownload.php?survey_id=<?php echo $sid; ?>&type=normalized"><button class="w3-button w3-blue">Download Normalized Survey Results</button></a>
    </div>
    <hr />
    <div class="w3-container w3-center">
        <h2>View Survey Results</h2>
    </div>
    <table class="w3-table" border=1.0 style="width:100%">
        <tr>
        <th>Reviewer Email (Name)</th>
        <th>Reviewee Email (Name)</th>
        <th>Score 1</th>
        <th>Score 2</th>
        <th>Score 3</th>
        <th>Score 4</th>
        <th>Score 5</th>
        </tr>
        <?php 
          foreach ($pairings as $pair)
          { 
            echo '<tr><td>' . htmlspecialchars($pair['reviewer_email'] . ' (' . $pair['reviewer_name'] . ')') . '</td><td>' . htmlspecialchars($pair['teammate_email'] . ' (' . $pair['teammate_name'] . ')') . '</td>';
            
            if ($pair['score1'] === NO_SCORE_MARKER)
            {
              echo '<td>Data Missing</td><td>Data Missing</td><td>Data Missing</td><td>Data Missing</td><td>Data Missing</td></tr>';
            }
            else
            {
              echo '<td>' . $pair['score1'] . '</td><td>' . $pair['score2'] . '</td><td>' . $pair['score3'] . '</td><td>' . $pair['score4'] . '</td><td>' . $pair['score5'] . '</td></tr>';
            }
          }
          ?>
    </table>
</body>
</html>