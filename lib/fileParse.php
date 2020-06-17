<?php


function parse_pairings($mode, $data_fields)
{
  
  // return array
  $split = NULL;
  
  // handle team mode or roster mode
  if ($mode == "1")
  {
    
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
  }
  
}

?>