-- course table
-- each row defines a specific course that uses this system
CREATE TABLE `course` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `code` TEXT NOT NULL,
  `name` TEXT NOT NULL,
  
  PRIMARY KEY (`id`)
  
) ENGINE=InnoDB;


-- surveys TABLE
-- each row represents a use of this system for a course. Students must only be able to submit evaluations between
-- the start date and end date listed
CREATE TABLE `surveys` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `course_id` INT NOT NULL,
  `start_date` DATETIME NOT NULL,
  `expiration_date` DATETIME NOT NULL,
  `rubric_id` INT NOT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `survey_course_idx` (`course_id`),
  CONSTRAINT `survey_course_constraint` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
  
) ENGINE=InnoDB;


-- students TABLE
-- each row is a distinct student who has been added to this system. Each student must only appear once EVEN IF they are registered in multiple classes
CREATE TABLE `students` (

  `student_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `name` TEXT NOT NULL,
  
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
  
) ENGINE=InnoDB;

-- eval table
-- each row defines a single peer- or self-evaluation. Rows are added/updated only as students complete their evaluations
CREATE TABLE `eval` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `survey_id` INT NOT NULL,
  `submitter_id` INT NOT NULL,
  `teammate_id` INT NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `eval_survey_id` (`survey_id`),
  KEY `eval_submitter_id` (`submitter_id`),
  KEY `eval_teammate_id` (`teammate_id`),
  CONSTRAINT `eval_submitter_id_constraint` FOREIGN KEY (`submitter_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `eval_teammate_id_constraint` FOREIGN KEY (`teammate_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `eval_survey_id_constraint` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
 
) ENGINE=InnoDB;

-- instructor table
-- each row defines a single instructor who could use this system
CREATE TABLE `instructors` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `init_auth_id` VARCHAR(255),
  `otp` TEXT,
  `otp_expiration` INT,
  `session_token` VARCHAR(255),
  `session_expiration` INT,
  `csrf_token` VARCHAR(255),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `init_auth` (`init_auth_id`),
  UNIQUE KEY `session_token` (`session_token`),
  UNIQUE KEY `csrf_token` (`csrf_token`)
  
) ENGINE=InnoDB;

-- reviewers TABLE
-- each row represents a single peer- or self-evalution that must be completed
-- before each evaluation, MHz uploads the files containing all of the pairings into this table
CREATE TABLE `reviewers` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `survey_id` INT NOT NULL,
  `reviewer_email` TEXT NOT NULL,
  `teammate_email` TEXT NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `reviewers_survey_id` (`survey_id`),
--  KEY `reviewers_reviewer_constraint` (`reviewer_email`),
--  KEY `reviewers_teammate_constraint` (`teammate_email`),
  CONSTRAINT `reviewers_survey_id_constraint` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
--  CONSTRAINT `reviewers_reviewer_constraint` FOREIGN KEY (`reviewer_email`) REFERENCES `students` (`email`),
--  CONSTRAINT `reviewers_teammate_constraint` FOREIGN KEY (`teammate_email`) REFERENCES `students` (`email`)
  
) ENGINE=InnoDB;


-- scores TABLE
-- each row represents the scores submitted. These scores are for the evaluation the row connects to
-- in the eval table using the eval_id field
CREATE TABLE `scores` (

  `eval_id` INT NOT NULL,
  `score1` INT NOT NULL,
  `score2` INT NOT NULL,
  `score3` INT NOT NULL,
  `score4` INT NOT NULL,
  `score5` INT NOT NULL,
  
  PRIMARY KEY (`eval_id`),
  UNIQUE KEY `eval_id` (`eval_id`),
  CONSTRAINT `scores_eval_id_constraint` FOREIGN KEY (`eval_id`) REFERENCES `eval` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
  
) ENGINE=InnoDB;

-- student_login TABLE
-- each row represents a student being able to login to the system. this is used to support the 2-step login approach
-- that relies on UBIT to provide the necessary requirements under FERPA ;)
CREATE TABLE `student_login` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `expiration_time` INT NOT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`,`password`),
  CONSTRAINT `student_login_email_constraint` FOREIGN KEY (`email`) REFERENCES `students` (`email`)
  
) ENGINE=InnoDB;

-- FUTURE EXPANSION OPTION
-- these tables were created so that we can allow faculty to tailor the questions & answers with each survey
-- FIXME: MHz is uncertain if they get used
CREATE TABLE `rubrics` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `description` TEXT NOT NULL,
  
  PRIMARY KEY (`id`)
  
) ENGINE=InnoDB;

CREATE TABLE `rubric_questions` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `rubric_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `fk_rubric` (`rubric_id`),
  CONSTRAINT `rubric_questions_ibfk_1` FOREIGN KEY (`rubric_id`) REFERENCES `rubrics` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
  
) ENGINE=InnoDB;

CREATE TABLE `rubric_responses` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `response` TEXT NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `fk_questions` (`question_id`),
  CONSTRAINT `rubric_responses_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `rubric_questions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
  
) ENGINE=InnoDB;

-- this is a replacement for the scores table which allows for variable numbers of questions
-- it would be
CREATE TABLE `scores2` (

  `id` INT NOT NULL AUTO_INCREMENT,
  `eval_id` INT NOT NULL,
  `score` INT NOT NULL,
  `question_number` INT NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `fk_eval` (`eval_id`),
  KEY `quest_num` (`question_number`),
  CONSTRAINT `scores2_ibfk_1` FOREIGN KEY (`eval_id`) REFERENCES `eval` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `scores2_ibfk2_1` FOREIGN KEY (`question_number`) REFERENCES `rubric_questions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
  
) ENGINE=InnoDB;
