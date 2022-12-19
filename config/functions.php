<?php

     // Sanitize Inputs
     function cleanData($data){

	    $data = strip_tags($data);
	    $data = htmlspecialchars($data);
	    $data = stripslashes($data);
	    $data = trim($data);
	    return $data;

	  }
    
