<?php
$host = getenv('MP_DB_HOST');
$user = getenv('MP_DB_USER');
$password = getenv('MP_DB_PASSWORD');
$db = getenv('MP_DB_NAME');

$mysqli = new mysqli($host , $user, $password, $db);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
echo $mysqli->host_info . "\n";

$res = $mysqli->query("select field_adStartDate, field_plan, elementId from craft_content where field_adStartDate IS NOT NULL and field_plan IS NOT NULL");
while ($row = $res->fetch_assoc()) {
    $adStartDate = $row['field_adStartDate'];
    $plan = $row['field_plan'];
    $elementId = $row['elementId'];

    echo "Ad Start Date: ${adStartDate} | Plan: ${plan} | Element ID: ${elementId} |";
    $now = time();
    // has it already been 1 week since the ad start date?
    $adStartDate = strtotime($adStartDate);
    $datediff = $now - $adStartDate;
    $days = round($datediff / (60 * 60 * 24));
    echo " Days since start: {$days}\n";

    $shouldDisable = false;
    
    switch ($plan) {
        case 'week':
            $shouldDisable = ($days > 7)? true : false;
            break;
        case 'month':
            $shouldDisable = ($days > 31)? true : false;
            break;
        case 'quarter':
            $shouldDisable = ($days > 93)? true : false;
            break;
        case 'year':
            $shouldDisable = ($days > 366)? true : false;
            break;
        default:
            # code...
            break;
    }
    if($shouldDisable){
        echo "Ad has expired. Disabling it.\n";
        $mysqli->query("update craft_elements set enabled=0 where id='${elementId}'") ;
    } else {
        // ensure its enabled
        $mysqli->query("update craft_elements set enabled=1 where id='${elementId}'") ;
    }
}

?>