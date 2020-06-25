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


// respond not found on no query string parameters
$sid = NULL;
if (!isset($_GET['survey']))
{
  http_response_code(404);   
  echo "404: Not found.";
  exit();
}
if (!isset($_GET['type']))
{
  http_response_code(404);   
  echo "404: Not found.";
  exit();
}

// make sure the type query is one of the valid types. if not, respond not found
if ($_GET['type'] !== 'raw' and $_GET['type'] !== 'normalized' and $_GET['type'] !== 'average')
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
// also store information about each reviewer and reviewee
$pairings = array();
$reviewers_info = array();
$reviewees_info = array();

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
  
  // initialize reviewer and reviewee info arrays
  if (!isset($reviewers_info[$pair_info['reviewer_id']]))
  {
    $reviewers_info[$pair_info['reviewer_id']] = array('running_sum' => 0, 'num_of_evals' => 0);
  }
  if (!isset($reviewees_info[$pair_info['reviewee_id']]))
  {
    $reviewees_info[$pair_info['reviewee_id']] = array('teammate_email' => $pair_info['teammate_email'], 'average_normalized_score' => NO_SCORE_MARKER, 'num_of_evals' => 0, 'running_sum' => 0);
  }
}

// now get the scores for each pairing
$stmt_scores = $con->prepare('SELECT score1, score2, score3, score4, score5 FROM scores WHERE reviewers_id=?');

// generate output strings on the fly
$raw_output = "";
$normal_output = "";
$average_output = "";

$size = count($pairings);
for ($i = 0; $i < $size; $i++)
{
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
    $pairings[$i]['normalized'] = NO_SCORE_MARKER;
  }
  else
  {
    $pairings[$i]['score1'] = $data_scores[0]['score1'];
    $pairings[$i]['score2'] = $data_scores[0]['score2'];
    $pairings[$i]['score3'] = $data_scores[0]['score3'];
    $pairings[$i]['score4'] = $data_scores[0]['score4'];
    $pairings[$i]['score5'] = $data_scores[0]['score5'];
    
    // now add the scores and adjust the number of evaluations for reviewer and reviewee
    $reviewers_info[$pairings[$i]['reviewer_id']]['running_sum'] += ($pairings[$i]['score1'] + $pairings[$i]['score2'] + $pairings[$i]['score3'] + $pairings[$i]['score4'] + $pairings[$i]['score5']);
    $reviewers_info[$pairings[$i]['reviewer_id']]['num_of_evals'] += 1;
    $reviewees_info[$pairings[$i]['reviewee_id']]['num_of_evals'] += 1; 
  } 
  
  if ($_GET['type'] === 'raw')
  {
    $raw_output .= $pairings[$i]['reviewer_email'] . ', ' . $pairings[$i]['teammate_email'] . ', ' . $pairings[$i]['score1'] . ', ' . $pairings[$i]['score2'] . ', ' . $pairings[$i]['score3'] . ', ' . $pairings[$i]['score4'] . ', ' . $pairings[$i]['score5'] . ",\n";
  }
}

// now generate the raw scores output
if ($_GET['type'] === 'raw')
{
  // remove the trailing comma
  if (strlen($raw_output) > 1)
  {
    $raw_output[-2] = " ";
  }
  
  // start the download
  // generate the correct headers for the file download
  header('Content-Type: text/plain; charset=UTF-8');
  header('Content-Disposition: attachment; filename="survey-' . $sid . '-raw-results.txt"');

  // ouput the data
  echo $raw_output;
  exit();
}

// now generate the normalized scores
// now, loop through the pairings again to generate the normalized pairing scores and the running sum for average normalized score
for ($i = 0; $i < $size; $i++)
{
  
  // generate initial part of normal output
  if ($_GET['type'] === 'normalized')
  {
    $normal_output .= $pairings[$i]['reviewer_email'] . ", " . $pairings[$i]['teammate_email'] . ", ";
  }
  
  // skip over pairings that do not have scores and mark this on the line
  if ($pairings[$i]['score1'] === NO_SCORE_MARKER)
  {
    if ($_GET['type'] === 'normalized')
    {
      $normal_output .= $pairings[$i]['normalized'] . ",\n";
    }
    continue;
  }
  
  // calculate normalized pairing score
  // set normalized score to one divided by number of evals if reviewer has not given any points to anyone
  if ($reviewers_info[$pairings[$i]['reviewer_id']]['running_sum'] !== 0)
  {
    $pairings[$i]['normalized'] = ($pairings[$i]['score1'] + $pairings[$i]['score2'] + $pairings[$i]['score3'] + $pairings[$i]['score4'] + $pairings[$i]['score5']) / $reviewers_info[$pairings[$i]['reviewer_id']]['running_sum'];
  }
  else
  {
    $pairings[$i]['normalized'] = 1 / $reviewers_info[$pairings[$i]['reviewer_id']]['num_of_evals'];
  }
  
  // now add the normalized score to the reviewees running sum after multiplying by number of evaluations made by reviewer
  $reviewees_info[$pairings[$i]['reviewee_id']]['running_sum'] += ($pairings[$i]['normalized'] * $reviewers_info[$pairings[$i]['reviewer_id']]['num_of_evals']);
  
  // generate normal raw output
  if ($_GET['type'] === 'normalized')
  {
    $normal_output .= $pairings[$i]['normalized'] . ",\n";
  }
}

// now generate the normalized scores output
if ($_GET['type'] === 'normalized')
{
  // remove the trailing comma
  if (strlen($normal_output) > 1)
  {
    $normal_output[-2] = " ";
  }
  
  // start the download
  // generate the correct headers for the file download
  header('Content-Type: text/plain; charset=UTF-8');
  header('Content-Disposition: attachment; filename="survey-' . $sid . '-normalized-results.txt"');

  // ouput the data
  echo $normal_output;
  exit();
}

?>