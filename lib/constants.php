<?php
define("SITE_HOME","https://www-student.cse.buffalo.edu/CSE442-542/2020-Summer/cse-442b/production/");
define("OTP_EXPIRATION_SECONDS", 60 * 15);
define("SESSIONS_SALT", "session-salt");
define("PBKDF2_ITERS", 50000);
define("TOKEN_SIZE", 32);
define("INIT_AUTH_TOKEN_EXPIRATION_SECONDS", 60 * 60);
define("INIT_AUTH_COOKIE_NAME", "init-auth");
define("SESSION_COOKIE_NAME", "session-token");
define("SESSION_TOKEN_EXPIRATION_SECONDS", 60 * 60 * 12);
define("SEMESTER_MAP", array('winter' => 1, 'spring' => 2, 'summer' => 3, 'fall' => 4));
?>
