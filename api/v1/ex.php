<?php



// outputs the username that owns the running php/httpd process
// (on a system with the "whoami" executable in the path)
$output=null;
$retval=null;
exec('/usr/bin/http https://www.google.com', $output, $retval);
echo "Returned with status $retval and output:\n";

// $output = shell_exec('http https://www.google.com');
$output = var_export($output, true);
echo $output;


?>
