<?
include(SERVER_ROOT.'/sections/requests/functions.php'); // get_request_tags()

function get_group_info($GroupID, $Return = true) {
	global $Cache, $DB;
	$TorrentCache=$Cache->get_value('torrents_details_'.$GroupID);
	
	//TODO: Remove LogInDB at a much later date.
	if(!is_array($TorrentCache) || !isset($TorrentCache[1][0]['LogInDB'])) {
		// Fetch the group details

		$SQL = "SELECT
                    g.Body,
                    g.Image,
			g.ID,
			g.Name,
			g.NewCategoryID,
			g.Time,
			GROUP_CONCAT(DISTINCT tags.Name SEPARATOR '|'),
			GROUP_CONCAT(DISTINCT tags.ID SEPARATOR '|'),
			GROUP_CONCAT(tt.UserID SEPARATOR '|'),
			GROUP_CONCAT(tt.PositiveVotes SEPARATOR '|'),
			GROUP_CONCAT(tt.NegativeVotes SEPARATOR '|')
			FROM torrents_group AS g
			LEFT JOIN torrents_tags AS tt ON tt.GroupID=g.ID
			LEFT JOIN tags ON tags.ID=tt.TagID
			WHERE g.ID='".db_string($GroupID)."'
			GROUP BY NULL";

		$DB->query($SQL);
		$TorrentDetails=$DB->to_array();

		// Fetch the individual torrents

		$DB->query("
			SELECT
			t.ID,
			t.FileCount,
			t.Size,
			t.Seeders,
			t.Leechers,
			t.Snatched,
			t.FreeTorrent,
                        t.double_seed,
			t.Time,
			t.FileList,
			t.FilePath,
			t.UserID,
			um.Username,
			t.last_action,
			tbt.TorrentID,
			tbf.TorrentID,
			tfi.TorrentID,
			t.LastReseedRequest,
			tln.TorrentID AS LogInDB,
			t.ID AS HasFile,
                        tr.ID AS ReviewID,
                        tr.Status,
                        tr.ConvID,
                        tr.Time AS StatusTime,
                        tr.KillTime,
                        IF(tr.ReasonID = 0, tr.Reason, rr.Description) AS StatusDescription,
                        tr.UserID AS StatusUserID,
                        su.Username AS StatusUsername
			FROM torrents AS t
			LEFT JOIN users_main AS um ON um.ID=t.UserID
                        LEFT JOIN torrents_reviews AS tr ON tr.GroupID=t.GroupID
                        LEFT JOIN review_reasons AS rr ON rr.ID=tr.ReasonID
                        LEFT JOIN users_main AS su ON su.ID=tr.UserID
			LEFT JOIN torrents_bad_tags AS tbt ON tbt.TorrentID=t.ID
			LEFT JOIN torrents_bad_folders AS tbf on tbf.TorrentID=t.ID
			LEFT JOIN torrents_bad_files AS tfi on tfi.TorrentID=t.ID
			LEFT JOIN torrents_logs_new AS tln ON tln.TorrentID=t.ID
			WHERE t.GroupID='".db_string($GroupID)."' 
                        AND (tr.Time IS NULL OR tr.Time=(SELECT MAX(torrents_reviews.Time) 
                                                              FROM torrents_reviews 
                                                              WHERE torrents_reviews.GroupID=t.GroupID))
			AND flags != 1
			GROUP BY t.ID
			ORDER BY t.ID");

            
		$TorrentList = $DB->to_array();
		if(count($TorrentList) == 0) {
			//error(404,'','','',true);
			if(isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
				error("Cannot find the torrent with the ID ".$_GET['torrentid']);
				header("Location: log.php?search=Torrent+".$_GET['torrentid']);
			} else {
				error(404);
			}
			die();
		}
		if(in_array(0, $DB->collect('Seeders'))) {
			$CacheTime = 600;
		} else {
			$CacheTime = 3600;
		}
		// Store it all in cache
            $Cache->cache_value('torrents_details_'.$GroupID,array($TorrentDetails,$TorrentList),$CacheTime);

	} else { // If we're reading from cache
		$TorrentDetails=$TorrentCache[0];
		$TorrentList=$TorrentCache[1];
	}

	if($Return) {
		return array($TorrentDetails,$TorrentList);
	}
}

function get_status_icon($Status){
    if ($Status == 'Warned' || $Status == 'Pending') return '<span title="This torrent will be automatically deleted unless the uploader fixes it" class="icon icon_warning"></span>';
    elseif ($Status == 'Okay') return '<span title="This torrent has been checked by staff and is okay" class="icon icon_okay"></span>';
    else return '';
}

//Check if a givin string can be validated as a torrenthash
function is_valid_torrenthash($Str) {
	//6C19FF4C 6C1DD265 3B25832C 0F6228B2 52D743D5
	$Str = str_replace(' ', '', $Str);
	if(preg_match('/^[0-9a-fA-F]{40}$/', $Str))
		return $Str;
	return false;
}

function get_group_requests($GroupID) {
	global $DB, $Cache;
	
	$Requests = $Cache->get_value('requests_group_'.$GroupID);
	if ($Requests === FALSE) {
		$DB->query("SELECT ID FROM requests WHERE GroupID = $GroupID AND TimeFilled = '0000-00-00 00:00:00'");
		$Requests = $DB->collect('ID');
		$Cache->cache_value('requests_group_'.$GroupID, $Requests, 0);
	}
	$Requests = get_requests($Requests);
	return $Requests['matches'];
}


function get_tag_synonym($Tag, $Sanitise = true){
        global $Cache, $DB;

        if ($Sanitise) $Tag = sanitize_tag($Tag);

        // Lanz: yeah the caching was a bit too much here imo.
        $DB->query("SELECT t.Name 
                    FROM tag_synomyns AS ts JOIN tags as t ON t.ID = ts.TagID 
                    WHERE Synomyn LIKE '".db_string($Tag)."'");
        if ($DB->record_count() > 0) { // should only ever be one but...
            list($TagName) = $DB->next_record();       
            return $TagName;
        } else {
            return $Tag; 
        }
}


/**
 * Return whether $Tag is a valid tag - more than 2** char long and not a stupid word
 * (** unless is 'hd','dp','bj','ts','sd','69','mf','3d','hj','bi')
 * 
 * @param string $Tag The prospective tag to be evaluated
 * @return Boolean representing whether the tag is valid format (not banned)
 */
function is_valid_tag($Tag){
    static $Good2charTags;
    $len = strlen($Tag);
    if ( $len < 2 ) return false;
    if ( $len == 2 ) {  
        if(!$Good2charTags) $Good2charTags = array('hd','dp','bj','ts','sd','69','mf','3d','hj','bi','tv','dv','da');
        if ( !in_array($Tag, $Good2charTags) ) return false;
    }
    return true;
}

// tag sorting functions
function sort_score($X, $Y){
	return($Y['score'] - $X['score']);
}
function sort_added($X, $Y){
	return($X['id'] - $Y['id']);
}
function sort_az($X, $Y){
	return( strcmp($X['name'], $Y['name']) );
}


/**
 * Returns the inner list elements of the tag table for a torrent
 * (this function calls/rebuilds the group_info cache for the torrent - in theory just a call to memcache as all calls come through the torrent details page)
 * @param int $GroupID The group id of the torrent
 * @return the html for the taglist
 */
function get_taglist_html($GroupID, $tagsort) {
    global $LoggedUser;
    
    $TorrentCache = get_group_info($GroupID, true);
    $TorrentDetails = $TorrentCache[0];
    $TorrentList = $TorrentCache[1];

    // Group details - get tag details
    list(, , , , , , $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs, $TagPositiveVotes, $TagNegativeVotes) = array_shift($TorrentDetails);
 
    if(!$tagsort || !in_array($tagsort, array('score','az','added'))) $tagsort = 'score';

    $Tags = array();
    if ($TorrentTags != '') {
          $TorrentTags=explode('|',$TorrentTags);
          $TorrentTagIDs=explode('|',$TorrentTagIDs);
          $TorrentTagUserIDs=explode('|',$TorrentTagUserIDs);
          $TagPositiveVotes=explode('|',$TagPositiveVotes);
          $TagNegativeVotes=explode('|',$TagNegativeVotes);

          foreach ($TorrentTags as $TagKey => $TagName) {
                $Tags[$TagKey]['name'] = $TagName;
                $Tags[$TagKey]['score'] = ($TagPositiveVotes[$TagKey] - $TagNegativeVotes[$TagKey]);
                $Tags[$TagKey]['id']=$TorrentTagIDs[$TagKey];
                $Tags[$TagKey]['userid']=$TorrentTagUserIDs[$TagKey];
          }
          uasort($Tags, "sort_$tagsort");
    }
    // grab authorID from torrent details
    list(, , , , , , , , , , , $UserID) = $TorrentList[0];
    $IsUploader =  $UserID == $LoggedUser['ID']; 

    ob_start();
   
            foreach($Tags as $TagKey=>$Tag) {

        ?>
                                <li id="tlist<?=$Tag['id']?>">
                                      <a href="torrents.php?taglist=<?=$Tag['name']?>" style="float:left; display:block;"><?=display_str($Tag['name'])?></a>
                                      <div style="float:right; display:block; letter-spacing: -1px;">
        <?		if(check_perms('site_vote_tag') || ($IsUploader && $LoggedUser['ID']==$Tag['userid'])){  ?>
                                      <a title="Vote down tag '<?=$Tag['name']?>'" href="#tags" onclick="Vote_Tag(<?="'{$Tag['name']}',{$Tag['id']},$GroupID,'down'"?>)" style="font-family: monospace;" >[-]</a>
                                      <span id="tagscore<?=$Tag['id']?>" style="width:10px;text-align:center;display:inline-block;"><?=$Tag['score']?></span>
                                      <a title="Vote up tag '<?=$Tag['name']?>'" href="#tags" onclick="Vote_Tag(<?="'{$Tag['name']}',{$Tag['id']},$GroupID,'up'"?>)" style="font-family: monospace;">[+]</a>
      
        <?          
                  } else {  // cannot vote on tags ?>
                                      <span style="width:10px;text-align:center;display:inline-block;" title="You do not have permission to vote on tags"><?=$Tag['score']?></span>
                                      <span style="font-family: monospace;" >&nbsp;&nbsp;&nbsp;</span>
                                      
        <?		} ?>
        <?		if(check_perms('users_warn')){ ?>
                                      <a title="User that added tag '<?=$Tag['name']?>'" href="user.php?id=<?=$Tag['userid']?>" >[U]</a>
        <?		} ?>
        <?		if(check_perms('site_delete_tag') ) { // || ($IsUploader && $LoggedUser['ID']==$Tag['userid']) 
                                  /*    <a title="Delete tag '<?=$Tag['name']?>'" href="torrents.php?action=delete_tag&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" style="font-family: monospace;">[X]</a> */
                                   ?>
                                   <a title="Delete tag '<?=$Tag['name']?>'" href="#tags" onclick="Del_Tag(<?="'{$Tag['id']}',$GroupID,'$tagsort'"?>)"   style="font-family: monospace;">[X]</a>
        <?		} else { ?>
                                      <span style="font-family: monospace;">&nbsp;&nbsp;&nbsp;</span>
        <?		} ?>
                                      </div>
                                      <br style="clear:both" />
                                </li>
        <?
            }
  
    $html = ob_get_contents(); 
    ob_end_clean();

    return $html;
}
