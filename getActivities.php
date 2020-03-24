<?php

include './vendor/autoload.php';

use MyGarmin\Datas;

$credentialsFile = Datas::CACHE_DIR.DIRECTORY_SEPARATOR.".credentials";

echo "CRED: $credentialsFile \n";

$credentials = array( "user" => "", "pass" => "");
if (is_readable($credentialsFile)) {
   $credentials = json_decode(file_get_contents($credentialsFile), TRUE);
}

do {
   $user = readline('Garmin login  ['.$credentials['user'].'] : ');
   if ($credentials['user']!="" && $user=="") {
      $user = $credentials['user'];
   }
} while ($user == '');
$credentials['user'] = $user;

do {
   $pass = readline('Garmin password ['.($credentials['pass']!=""?"******":"").']: ');
   if ($credentials['pass']!="" && $pass=="") {
      $pass = $credentials['pass'];
   }
} while ($pass == '');
$credentials['pass'] = $pass;

file_put_contents($credentialsFile, json_encode($credentials));

try {

   echo "Connection to Garmin as $user \n";
   $datas = new Datas($user, $pass);
   //$datas->reloadForced = TRUE;
   $datas->getYearActivities(2020);
   
} catch (Exception $objException) {
   echo "Oops: " . $objException;
}

