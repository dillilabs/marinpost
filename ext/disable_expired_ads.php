<?php
$host = getenv('MP_DB_HOST');
$user = getenv('MP_DB_USER');
$password = getenv('MP_DB_PASSWORD');
$db = getenv('MP_DB_NAME');
$websiteurl = getenv('MP_WEBSITE_URL');

$mysqli = new mysqli($host , $user, $password, $db);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
echo $mysqli->host_info . "\n";

$res = $mysqli->query("select title, field_adStartDate, field_plan, field_planDurationDays, elementId from craft_content where field_adStartDate IS NOT NULL and field_plan IS NOT NULL");
while ($row = $res->fetch_assoc()) {
    $adStartDate = $row['field_adStartDate'];
    $plan = $row['field_plan'];
    $elementId = $row['elementId'];
    $title = $row['title'];
    $planDurationDays = $row['field_planDurationDays'];

    echo "Ad Start Date: ${adStartDate} | Plan: ${plan} | Element ID: ${elementId} |";
    $now = time();
    // has it already been 1 week since the ad start date?
    $tz = date_default_timezone_get();
    date_default_timezone_set('UTC'); // adStartDate is in UTC
    $adStartDate = strtotime($adStartDate); 
    date_default_timezone_set($tz);
    
    // only if adStartDate is not in future.
    if($now > $adStartDate){
        $datediff = $now - $adStartDate;
        $days = floor($datediff / (60 * 60 * 24));
        echo " Days since start: {$days}\n";
        
        if($days > $planDurationDays){
            // if its already disabled ignore
            $res1 = $mysqli->query("select craft_elements.enabled, slug from craft_elements inner join craft_elements_i18n where craft_elements.id='${elementId}' and craft_elements.id = craft_elements_i18n.elementId");
            while ($row1 = $res1->fetch_assoc()) {
                $enabled = $row1['enabled'];
                $slug = $row1['slug'];
                if($enabled == 1){
                    echo "Ad has expired. Disabling it.\n";
                    $mysqli->query("update craft_elements set enabled=0 where id='${elementId}'") ;
                    
                    // send an email to author with ad expiration
    
                    // get author email
                    $res2 = $mysqli->query("select email, firstName, lastName from craft_entries inner join craft_users where craft_entries.id='${elementId}' and craft_entries.authorId=craft_users.id");
                    while ($row2 = $res2->fetch_assoc()) {
                        $email = $row2['email'];
                        $name = $row2['firstName'] . ' ' . $row2['lastName'];
    
                        echo $email;

                        // retrieve email content from ad/email/abouttoexpire global
                        $res3 = $mysqli->query("select field_message_text from craft_matrixcontent_sitemessage where field_message_path='ad/email/expired'");
                        while ($row3 = $res3->fetch_assoc()) {
                            $message = $row3['field_message_text'];
                            // search/replace NAME, AD_TITLE, LINK with correct values
                            $link = "<a href='{$websiteurl}/edit/${elementId}/${slug}'>here</a>";
                            $message = str_replace("LINK", $link, $message);
                            $message = str_replace("NAME", $name, $message);
                            $message = str_replace("AD_TITLE", $title, $message);

                            // In case any of our lines are larger than 70 characters, we should use wordwrap()
                            $message = wordwrap($message, 70, "\r\n");
                            $headers = "From: MarinPost <support@marinpost.org>\r\n" .
                                "MIME-Version: 1.0" . "\r\n" .
                                "Content-type: text/html; charset=UTF-8" . "\r\n";

                            echo $message;

                            // Send
                            mail($email, "Your Ad {$title} has expired", $message, $headers);                            
                        }
                    }
                }
            }
        } else {
            // Enable if it was disabled.
            $res1 = $mysqli->query("select craft_elements.enabled, slug from craft_elements inner join craft_elements_i18n where craft_elements.id='${elementId}' and craft_elements.id = craft_elements_i18n.elementId");
            while ($row1 = $res1->fetch_assoc()) {
                $enabled = $row1['enabled'];
                $slug = $row1['slug'];
                if($enabled == 0){
                    echo "Ad has been renewed. Enable it.\n";
                    $mysqli->query("update craft_elements set enabled=1 where id='${elementId}'") ;
                }
            }
        }
    }
}

?>
