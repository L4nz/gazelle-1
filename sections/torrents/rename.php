<?
authorize();

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewName = db_string($_POST['name']);

if(!$GroupID || !is_number($GroupID)) { error(404); }

if(empty($NewName)) {
	error("Torrents cannot have a blank name");
}

if(!check_perms('torrents_edit')) { error(403); }

$DB->query("SELECT Name FROM torrents_group WHERE ID = ".$GroupID);
list($OldName) = $DB->next_record();

$DB->query("UPDATE torrents_group SET Name='$NewName' WHERE ID='$GroupID'");
$Cache->delete_value('torrents_details_'.$GroupID);

update_hash($GroupID);

write_log("Torrent Group ".$GroupID." (".$OldName.")  was renamed to '".$NewName."' by ".$LoggedUser['Username']);
write_group_log($GroupID, 0, $LoggedUser['ID'], "renamed to ".$NewName." from ".$OldName, 0);

header('Location: torrents.php?id='.$GroupID."&did=2");
