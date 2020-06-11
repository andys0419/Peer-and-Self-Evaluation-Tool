<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-blue.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/addCourses.css">
    <title>Add Courses</title>
</head>
<body>

    <div class="w3-container w3-center">
        <h2>Course Information</h2>
    </div>

<!--------form action="addCourses.php" once linked-------------------->
<form action="addCourses.php" method ="post" class="w3-container">
    <label for="course-code">Course Code:</label><br>
    <input type="text" maxlength=6 id="course-code" class="w3-input w3-border w3-animate-input" style="width:30%" name="course-code" placeholder="e.g, CSE442"><br>

    <label for="course-name">Course Name:</label><br>
    <input type="text" id="course-name" class="w3-input w3-border w3-animate-input" style="width:30%" name="course-name" placeholder="e.g, Software Engineering Concepts"><br>

    <label for="semester">Course Semester:</label><br>
    <select class="w3-select w3-border" style="width:30%" name="semester">
        <option value="" disabled selected>Choose semester:</option>
        <option value="fall">Fall</option>
        <option value="winter">Winter</option>
        <option value="spring">Spring</option>
        <option value="summer">Summer</option>
    </select><br><br>

    <label for="year">Course Year:</label><br>
    <input type="text" maxlength="4" id="year" class="w3-input w3-border w3-animate-input" style="width:30%" name="course-year" placeholder="e.g, 2020"><br>

    <input type="submit" name="add" value="Add">

</form>
</html>


<?php
    //reports all errors
    //error_reporting(-1); 
    //ini_set("display_errors", "1"); 
    //ini_set("log_errors", 1);

    //starts the session
    //session_start();
    //require "lib/constants.php";

    //checks session id is set
    //if(!isset($_SESSION['id'])) {
      // header("Location: ".SITE_HOME."instructorLogin.php");
       //exit();
    //}

    //allocates fields from this 'addCourse' form into an array
    $courseInfo = array('course-code','course-name','semester','course-year');
   
    $error = false;
    if(isset($_POST['add'])) {
	
    	foreach($courseInfo AS $info) {
		
            //checks fields aren't empty	
		    if(empty($_POST[$info]) || !isset($_POST[$info])) {
                echo "<br>";
                echo "&nbsp;&nbsp Please fill in all required fields.";
               
                $error = true;
		        break; 
            } else {

            //check valid formatting
		    if ($info == 'course-code') {
                
                if (strlen($_POST[$info]) != 6) {
                    echo "<br>";
                    echo "&nbsp;&nbsp Error: Please enter a valid course code.";
                    $error = true;  
                }
                
                
                if (!preg_match("/^[a-zA-Z0-9]*$/",$_POST[$info])) {
                    echo "<br>";
                    echo "&nbsp;&nbsp Error: Please enter a valid course code."; 
                    $error = true;
                }


            } elseif($info == 'course-name') {

                //allowed regex is incorrect. Needs to be fixed in another sprint.
                if (preg_match("/^[a-zA-Z0-9\/&\-',\\s]*$/", $_POST[$info])) {
                    
                } else {
                    echo "<br>";
                    echo "&nbsp;&nbsp Error: Please enter a valid course name."; 
                    $error = true;

                }
            } 

		    else {
                
                if ($info == 'course-year') {
                
                    if(!preg_match("/^[0-9]*$/",$_POST[$info]) || strlen($_POST[$info]) != 4) {
                        echo "<br>";
                        echo "&nbsp;&nbsp Error: Please enter a valid year.";
                        $error = true;
                    }

                }   
            }
		}
        }

        if (!$error) {
            //Code for updating database goes here!
            
            
            //popup "Duplicate"
            
            //popup "Success!"
            echo "<script>alert('Your course was added sucessfully!');</script>";

            
        }
        

    }   
?>



