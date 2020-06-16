<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-blue.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/surveys.css">
    <title>Surveys</title>
</head>
<body>
    <div class="w3-container w3-center">
        <h2>Instructor Surveys</h2>
    </div>

    <table class=w3-table border=1.0 style=width:100%>
        <tr>
        <th>Course</th>
        <th>Questions</th>
        <th>Completed Surveys</th>
        <th>Start</th>
        <th>End</th>
        </tr>
    <!------------------------PHP code to create table implemented here-------------------------->
    </table>
<body>

<div class = "center">
    <!---Redirect to addSurveys.html. Once backend linked, then addSurveys.php------------------->
    <input type='submit' name = "addSurveys" onclick="window.location.href = 'addSurveys.php';" class="w3-button w3-dark-grey" value="+ Add Survey"/></input>
</div> 

</html> 