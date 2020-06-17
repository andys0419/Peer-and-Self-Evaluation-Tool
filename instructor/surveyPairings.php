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
    </table>
    <br />
    <button>Download Pairings as CSV File</button>
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
    

</body>
</html> 
