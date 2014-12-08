<?php
global $DB;
enforce_login();
authorize();
if ( !check_perms('site_give_specialgift') ) {
    error(404);
}

/* We should validate these.*/
$DONATE   = (int) $_POST['donate'];
$CLASS    = (int) $_POST['class'];
$RATIO    = $_POST['ratio'];
$CREDITS  = $_POST['credits'];
$LASTSEEN = $_POST['last_seen'];


$DB->query("SELECT
                ID AS UserID
            FROM
                users_main
            WHERE
                PermissionID <= $CLASS
                AND (Uploaded / Downloaded) $RATIO
                AND Credits $CREDITS
                AND LastAccess >= DATE_SUB(NOW(), INTERVAL $LASTSEEN HOUR)
                AND Enabled = '1'");
$Eligible_Users = array_column($DB->to_array(), 'UserID');
$Recipient = array_rand($Eligible_Users,1);
$Recipient = $Eligible_Users[$Recipient];
if(empty($Recipient)) {
/*    echo '<div class ="box pad">';
    echo "Eligible Users: ";
    print_r($Eligible_Users);
    echo "<br />";
    echo "Recipient: ";
    print_r($Recipient);
    echo "</div>";*/
    error("No users match this criteria");
}

$DB->query("SELECT
                PermissionID as Current_Class,
                (Uploaded / Downloaded) AS Current_Ratio,
                Credits AS Current_Credits,
                LastAccess AS Current_LastAccess
            FROM
                users_main
            WHERE
                ID = $Recipient");

list($Current_Class, $Current_Ratio, $Current_Credits, $Current_LastAccess) = $DB->next_record();

$DB->query("UPDATE users_main
            SET
                Credits = Credits+$DONATE
            WHERE
                ID = $Recipient");
$Summary = sqltime().' | +'.ucfirst(number_format($DONATE)." credits | You received a special gift of ".number_format($DONATE)." credits from an anonymous perv");
$DB->query("UPDATE users_info
            SET
                BonusLog = CONCAT_WS('\n', '$Summary', BonusLog)
            WHERE
                UserID = $Recipient");

$DB->query("UPDATE users_main
            SET
                Credits = Credits-$DONATE
            WHERE
                ID = $UserID");
$Summary = sqltime().' | - '.ucfirst(number_format($DONATE)." credits | You gave a special gift of ".number_format($DONATE)." credits to an anonymous perv");
$DB->query("UPDATE users_info
            SET
                BonusLog = CONCAT_WS('\n', '$Summary', BonusLog)
            WHERE
                UserID = $UserID");

$DB->query("INSERT INTO users_special_gifts (UserID, CreditsGiven, Recipient)
                                    VALUES('$UserID', '$DONATE', '$Recipient')");

send_pm($Recipient, 0, "Special Gift - You received an anonymous gift of credits",
                            "[br]You received a gift of ".number_format ($DONATE)." credits from an anonymous user.");

$ResultMessage="Your gift has been given and gratefully received.\n\n".
               "The recipient has the rank of ".$Classes[$Current_Class]['Name']." and a ratio of $Current_Ratio,\n".
               "he had $Current_Credits credits and was last seen at $Current_LastAccess";
header("Location: bonus.php?action=msg&". (!empty($ResultMessage) ? "result=" .urlencode($ResultMessage):"")."&retsg");