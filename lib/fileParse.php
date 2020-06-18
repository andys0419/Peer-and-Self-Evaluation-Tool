<?php


function parse_pairings($mode, $data_fields)
{
  
  // return array
  $split = array();
  
  // handle team mode or raw mode
  if ($mode == "1")
  {
    
    // check for blank file
    if (empty(trim($data_fields)))
    {
      $split['error'] = 'Input CSV file cannot be blank.';
      return $split;
    }
    
    // split on the comma
    $split = explode(",", $data_fields);
    
    // reject if there are not an even number of fields
    $size = count($split);
    if ($size % 2 !== 0)
    {
      $split['error'] = 'Input CSV file must have an even number of fields for raw mode.';
      return $split;
    }
    
    // remove whitespace from each entry and reject if any fields are blank
    for ($i = 0; $i < $size; $i++)
    {
      $split[$i] = trim($split[$i]);
      
      if (empty($split[$i]))
      {
        $split['error'] = 'Input CSV file cannot have any blank fields.';
        return $split;
      }
    }
    
    return $split;
    
  }
  else if ($mode == "2")
  {
    
    // check for blank file
    if (empty(trim($data_fields)))
    {
      $split['error'] = 'Input CSV file cannot be blank.';
      return $split;
    }
    
    // split be newlines
    $lines = explode("\n", $data_fields);
    
    // examine each line or team
    $line_count = count($lines);
    
    for ($j = 0; $j < $line_count; $j++)
    {
      
      // skip over blank lines
      $lines[$j] = trim($lines[$j]);
      if (empty($lines[$j]))
      {
        continue;
      }
      
      // split on the comma
      $temp_split = explode(",", $lines[$j]);
      $size = count($temp_split);
      
      // remove whitespace from each entry and reject if any fields are blank
      for ($i = 0; $i < $size; $i++)
      {
        $temp_split[$i] = trim($temp_split[$i]);
        
        if (empty($temp_split[$i]))
        {
          $split['error'] = 'Input CSV file cannot have any blank fields.';
          return $split;
        }
      }
      
      // add the team to the array
      array_push($split, $temp_split);
    
    }
    
    return $split;
    
  }
  
}

function check_pairings($mode, $pairings, $course_id, $db_connection)
{
  
  // return array
  $ids = array();
  
  // prepare statement
  $stmt = $db_connection->prepare('SELECT roster.student_id FROM roster JOIN students ON roster.student_id=students.student_id WHERE students.email=? AND roster.course_id=?');
  
  // handle team or raw mode
  if ($mode == "1")
  {
    // loop over each email address
    foreach ($pairings as $email)
    {

      $stmt->bind_param('si', $email, $course_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $data = $result->fetch_all(MYSQLI_ASSOC);

      // make sure the student is enrolled in the course
      if ($result->num_rows == 0)
      {
        $ids['error'] = 'The student with the following email address is not in the course roster: ' . htmlspecialchars($email) . '<br />The course roster page is <a href="courseRoster.php?course=' . $course_id . '">here</a>.';
        return $ids;
      }

      // store the id in the array
      array_push($ids, $data[0]['student_id']);
      
    }
    
    return $ids;
    
  }
  else if ($mode == "2")
  {
    // loop over each team
    foreach ($pairings as $team)
    {
      // use a temp team member array
      $member_array = array();
      
      foreach($team as $email)
      {
        $stmt->bind_param('si', $email, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // make sure the student is enrolled in the course
        if ($result->num_rows == 0)
        {
          $ids['error'] = 'The student with the following email address is not in the course roster: ' . htmlspecialchars($email) . '<br />The course roster page is <a href="courseRoster.php?course=' . $course_id . '">here</a>.';
          return $ids;
        }

        // store the id in the array
        array_push($member_array, $data[0]['student_id']);
      }
      
      // add the temp array to the main array
      array_push($ids, $member_array);
    }
    
    return $ids;
    
  }
  
}

function add_pairings($mode, $emails, $ids, $survey_id, $db_connection) 
{
  
  // prepare SQL statements
  $stmt_check = $db_connection->prepare('SELECT id FROM reviewers WHERE survey_id=? AND reviewer_id=? AND reviewee_id=?');
  $stmt_add = $db_connection->prepare('INSERT INTO reviewers (survey_id, reviewer_email, teammate_email, reviewer_id, reviewee_id) VALUES (?, ?, ?, ?, ?)');
  
  // handle team or raw mode
  if ($mode == "1")
  {
    // loop over each array
    $size = count($emails);

    // loop over each pairing
    for ($i = 0; $i < $size; $i += 2)
    {
      // check if the pairing already exists
      $stmt_check->bind_param('iii', $survey_id, $ids[$i], $ids[$i + 1]);
      $stmt_check->execute();
      $result = $stmt_check->get_result();
      $data = $result->fetch_all(MYSQLI_ASSOC);
      
      // add the pairing if it does not exist
      if ($result->num_rows == 0)
      {
        $stmt_add->bind_param('issii', $survey_id, $emails[$i], $emails[$i + 1], $ids[$i], $ids[$i + 1]);
        $stmt_add->execute();
      }
    }
  }
  else if ($mode == "2")
  {
    // loop over each team
    $upper_size = count($emails);
    
    for ($i = 0; $i < $upper_size; $i++)
    {
      // now loop over the members in a team
      $lower_size = count($emails[$i]);
     
      for ($j = 0; $j < $lower_size; $j++)
      {
        // now loop over the team members again to generate pairings
        for ($k = 0; $k < $lower_size; $k++)
        {
          // check if the pairing already exists
          $stmt_check->bind_param('iii', $survey_id, $ids[$i][$j], $ids[$i][$k]);
          $stmt_check->execute();
          $result = $stmt_check->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);

          // add the pairing if it does not exist
          if ($result->num_rows == 0)
          {
            $stmt_add->bind_param('issii', $survey_id, $emails[$i][$j], $emails[$i][$k], $ids[$i][$j], $ids[$i][$k]);
            $stmt_add->execute();
          }
        }
      }
    }
  }
  
}

?>
