<?php

require 'vendor/autoload.php';

use KmData\Factory;

ob_implicit_flush(true); //Flush buffer after each echo

$starttime = microtime(true); //Start time
echo "Querying...\n\n";

if(strcmp($argv[1], "COE274")==0){
    $appointments = Factory::search('appointment', array('building_code' => '274', 'q' => '14___'), array('person')); //Find all appointments in Hitchcock
} else if (strcmp($argv[1], "COEFAC")==0) {
    $appointments = Factory::search('appointment', array('sal_admin_plan' => 'FAC', 'q' => '14___'), array('person')); //Find all COE Faculty
} else if (strcmp($argv[1], "COESTAFF")==0) {
    $appointments = Factory::search('appointment', array('sal_admin_plan' => array('FOP', 'CW1', 'GCC', 'FOPO', 'CCS', 'BCP', 'P%26T', 'ONA', 'A%26P', 'CW2'), 'q' => '14___'), array('person')); //Find all COE Staff
} else {
    $appointments = Factory::search('appointment', array('q' => '14___'), array('person')); //Find all appointments
}

//$appointments = Factory::search('department', array('q' => '14___')); //Find all appointments in Hitchcock

echo "* Found " . count($appointments) . " appointments.\n";

$results = fopen('results.csv', 'w') or die("Unable to open file!"); //Open csv for writing

$unique = []; //keep track of unique departments
$departments = 0; //number of unique departments
foreach ($appointments as $coe) {
    $dep_id = $coe->department_id;
    if(($dep_id >= 14000) && ($dep_id <= 14999)){
        if(!array_key_exists($dep_id, $unique)){
            $departments++; //Increment count if new department added
        }
        $person = $coe -> person; //Person record
        $unique[$dep_id][] = $person; //Array of persons sorted by department id
    }
}

echo "* Found {$departments} engineering departments between 14000 and 14999.\n\n";
echo "Filtering query results...\n\n";

ksort($unique); //Sort by department number
$dep_num = key($unique); //First department number
$num_fac = 0;
$num_staff = 0;
$total = 0;

foreach($unique as $dep){
    $num_people = max(array_keys($dep)) + 1; //Number of people in department
    $dep_list = Factory::search('department', array('deptid' => "{$dep_num}"), array('appointments')); //Find department record

    if($num_people == 1){ //Plural or singular form
        echo "* Found {$num_people} person in {$dep_num} - {$dep_list[0]->dept_name}\n";
    } else {
        echo "* Found {$num_people} people in {$dep_num} - {$dep_list[0]->dept_name}\n";
    }

    $dep_title = array($dep_list[0]->dept_name);
    fputcsv($results, $dep_title);
    foreach($dep as $emp){
        $personinfo = Factory::search('person', array('id' => "{$emp->id}"), array('emails', 'appointments', 'phone_numbers'));
        $emp_rec = array($emp->id, $emp->display_name, $personinfo[0]->emails[0]->email, $personinfo[0]->appointments[0]->title, $personinfo[0]->phone_numbers[0]->formatted, $personinfo[0]->appointments[0]->sal_admin_plan);
        fputcsv($results, $emp_rec);

        if(strcmp($personinfo[0]->appointments[0]->sal_admin_plan, "FAC")==0){
            $num_fac++;
        } else {
            $num_staff++;
        }
    }

    $total += $num_people;
    $dep_num = key($unique);
    next($unique);
}

$endtime = microtime(true);
$timediff = round($endtime - $starttime, 2);
echo "\n* Staff: {$num_staff}\n";
echo "* Faculty: {$num_fac}\n";
echo "* Total: {$total}\n";
echo "\n* DONE - finished in {$timediff} seconds\n\n";

fclose($results);
