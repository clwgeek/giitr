

<?php

	// In a real application, these should be in a config file instead
	protected $db_host = 'clwgeekLessons.db.11910712.hostedresource.com';
	protected $db_port = 3306;
	protected $db_user = 'clwgeekLessons';
	protected $db_pass = 'cl8!2000#Mom';
	protected $db_name = 'clwgeekLessons';
		
	
    $db = mysqli::connect($db_host, $db_user, $db_pass, $db_name, $db_port); 
 
    if (!$db) {
        echo "Unable to establish connection to database server";
        exit;
    }
 
    if (!mysql_select_db($db_name, $db)) {
        echo "Unable to connect to database";
        exit;
    }

	 if (mysqli_connect_errno())
			fail('MySQL connect', mysqli_connect_error());
?>