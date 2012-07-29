<?

header('Content-Type: application/json; charset=utf-8');

$UserID = $LoggedUser['ID'];
$TagID = db_string($_POST['tagid']);
$GroupID = db_string($_POST['groupid']);
$Way = db_string($_POST['way']);

if(!is_number($TagID) || !is_number($GroupID)) {
	error(0,true);
}
if(!in_array($Way, array('up', 'down'))) {
	error(0,true);
}

$DB->query("SELECT Way FROM torrents_tags_votes WHERE TagID='$TagID' AND GroupID='$GroupID' AND UserID='$UserID'");
if($DB->record_count() > 0) list($LastVote)=$DB->next_record();

// if not voted before or changing vote
if($LastVote!=$Way){ 
    if($LastVote){
        $DB->query("DELETE FROM torrents_tags_votes WHERE TagID='$TagID' AND GroupID='$GroupID' AND UserID='$UserID'");
        $msg = "Removed $LastVote vote for tag '";
    } else {
        $DB->query("INSERT IGNORE INTO torrents_tags_votes (GroupID, TagID, UserID, Way) VALUES ('$GroupID', '$TagID', '$UserID', '$Way')");
        $msg = "Voted $Way for tag '";
    }
    
    if($Way == 'down') {
        $Change = "NegativeVotes=NegativeVotes+1";
        echo json_encode (array(-1, $msg));
    } else {
        $Change = "PositiveVotes=PositiveVotes+1";
        echo json_encode (array(1, $msg));
    }
    $DB->query("UPDATE torrents_tags SET $Change WHERE TagID='$TagID' AND GroupID='$GroupID'");

    $DB->query("DELETE FROM torrents_tags WHERE TagID='$TagID' AND GroupID='$GroupID' AND NegativeVotes>PositiveVotes");
    if ($DB->affected_rows()>0){
        update_hash($GroupID);
    }
    $Cache->delete_value('torrents_details_'.$GroupID); // Delete torrent group cache
} else 
    echo json_encode (array(0,"You have already $Way voted for tag '"));

?>
