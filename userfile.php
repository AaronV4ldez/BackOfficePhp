
<?php

$uploaddir = '/var/www/html/upload/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

if (!is_writable($uploaddir)) {
    die('sorry the web server does not have permission to write to ' . $uploaddir);
}


echo $uploadfile . "\n";
echo $_FILES['userfile']['tmp_name'], "\n";
echo "err: ", $_FILES['userfile']['error'], "\n";

// echo shell_exec("ls -l /tmp");
// echo shell_exec("cp " . $_FILES['userfile']['tmp_name'] . " " . $uploadfile);
// echo shell_exec("ls -l " . $uploaddir);

// try {
//     $handle = fopen($_FILES['userfile']['tmp_name'], "rb");
//     $contents = fread($handle, filesize($filename));
//     fclose($handle);
// } catch (Exception $e) {
//     echo $e->getMessage();
// }

// print_r($contents);


// move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);

// echo '<pre>';
 if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
     echo "File is valid, and was successfully uploaded.\n";
 } else {
     echo "Possible file upload attack!\n";
 }
echo "tmp>>", shell_exec("ls -l /tmp");
echo "nada>>", shell_exec("ls -l /tmp/nada");

// echo 'Here is some more debugging info:';
print_r($_FILES);

// print "</pre>";

?>
