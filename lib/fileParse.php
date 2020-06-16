<?php


function parse_pairings($mode, $data_fields)
{
  
  // return array
  $split = array();
  
  // handle team mode or roster mode
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

?>