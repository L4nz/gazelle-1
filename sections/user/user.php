<?

include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

include(SERVER_ROOT.'/sections/requests/functions.php');

if (empty($_GET['id']) || !is_numeric($_GET['id'])) { error(0); }
$UserID = $_GET['id'];



if($UserID == $LoggedUser['ID']) { 
	$OwnProfile = true;
} else { 
	$OwnProfile = false;
}

if(check_perms('users_mod')) { // Person viewing is a staff member
	$DB->query("SELECT
		m.Username,
		m.Email,
		m.LastAccess,
		m.IP,
		p.Level AS Class,
		m.Uploaded,
		m.Downloaded,
		m.RequiredRatio,
		m.Title,
		m.torrent_pass,
            m.PermissionID AS ClassID,
		m.Enabled,
		m.Paranoia,
		m.Invites,
		m.can_leech,
		m.Visible,
		i.JoinDate,
		i.Info,
		i.Avatar,
		i.Country,
		i.AdminComment,
		i.Donor,
		i.Warned,
		i.SupportFor,
		i.RestrictedForums,
		i.PermittedForums,
		i.Inviter,
		inviter.Username,
		COUNT(posts.id) AS ForumPosts,
		i.RatioWatchEnds,
		i.RatioWatchDownload,
		i.DisableAvatar,
		i.DisableInvites,
		i.DisablePosting,
		i.DisableForums,
		i.DisableTagging,
		i.DisableUpload,
		i.DisablePM,
		i.DisableIRC,
		i.DisableRequests,
		i.HideCountryChanges,
		m.FLTokens,
		SHA1(i.AdminComment),
                m.Credits,
                i.BonusLog,
                p.MaxAvatarWidth,
                p.MaxAvatarHeight
		FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
		LEFT JOIN permissions AS p ON p.ID=m.PermissionID
		LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
		WHERE m.ID = '".$UserID."' GROUP BY AuthorID");

	if ($DB->record_count() == 0) { // If user doesn't exist
		header("Location: log.php?search=User+".$UserID);
	}

	list($Username,$Email,$LastAccess,$IP,$Class, $Uploaded, $Downloaded, $RequiredRatio, $CustomTitle, $torrent_pass, $ClassID, $Enabled, $Paranoia, $Invites, $DisableLeech, $Visible, $JoinDate, $Info, $Avatar, $Country, $AdminComment, $Donor, $Warned, $SupportFor, $RestrictedForums, $PermittedForums, $InviterID, $InviterName, $ForumPosts, $RatioWatchEnds, $RatioWatchDownload, $DisableAvatar, $DisableInvites, $DisablePosting, $DisableForums, $DisableTagging, $DisableUpload, $DisablePM, $DisableIRC, $DisableRequests, $DisableCountry, $FLTokens, $CommentHash,$BonusCredits,$BonusLog,$MaxAvatarWidth, $MaxAvatarHeight) = $DB->next_record(MYSQLI_NUM, array(12));

} else { // Person viewing is a normal user
	$DB->query("SELECT
		m.Username,
		m.Email,
		m.LastAccess,
		m.IP,
		p.Level AS Class,
		m.Uploaded,
		m.Downloaded,
		m.RequiredRatio,
            m.PermissionID AS ClassID,
		m.Enabled,
		m.Paranoia,
		m.Invites,
		m.Title,
		m.torrent_pass,
		m.can_leech,
		i.JoinDate,
		i.Info,
		i.Avatar,
		m.FLTokens,
		i.Country,
		i.Donor,
		i.Warned,
		COUNT(posts.id) AS ForumPosts,
		i.Inviter,
		i.DisableInvites,
		inviter.username,
                m.Credits,
                i.BonusLog,
                p.MaxAvatarWidth,
                p.MaxAvatarHeight
		FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		LEFT JOIN permissions AS p ON p.ID=m.PermissionID
		LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
		LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
		WHERE m.ID = $UserID GROUP BY AuthorID");

	if ($DB->record_count() == 0) { // If user doesn't exist
		header("Location: log.php?search=User+".$UserID);
	}

	list($Username, $Email, $LastAccess, $IP, $Class, $Uploaded, $Downloaded, $RequiredRatio, $ClassID, $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass, $DisableLeech, $JoinDate, $Info, $Avatar, $FLTokens, $Country, $Donor, $Warned, $ForumPosts, $InviterID, $DisableInvites, $InviterName,$BonusCredits,$BonusLog,$MaxAvatarWidth,$MaxAvatarHeight, $RatioWatchEnds, $RatioWatchDownload) = $DB->next_record(MYSQLI_NUM, array(10));
}
 

// Image proxy CTs
$DisplayCustomTitle = $CustomTitle;
if(check_perms('site_proxy_images') && !empty($CustomTitle)) {
	$DisplayCustomTitle = preg_replace_callback('~src=("?)(http.+?)(["\s>])~', function($Matches) {
																		return 'src='.$Matches[1].'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&amp;i='.urlencode($Matches[2]).$Matches[3];
																	}, $CustomTitle);
}

      
$Paranoia = unserialize($Paranoia);
if(!is_array($Paranoia)) {
	$Paranoia = array();
}
$ParanoiaLevel = 0;
foreach($Paranoia as $P) {
	$ParanoiaLevel++;
	if(strpos($P, '+')) {
		$ParanoiaLevel++;
	}
}

$JoinedDate = time_diff($JoinDate);
$LastAccess = time_diff($LastAccess);

function check_paranoia_here($Setting) {
	global $Paranoia, $Class, $UserID;
	return check_paranoia($Setting, $Paranoia, $Class, $UserID);
}

$Badges=($Donor) ? '<a href="donate.php"><img src="'.STATIC_SERVER.'common/symbols/donor.png" alt="Donor" /></a>' : '';


$Badges.=($Warned!='0000-00-00 00:00:00') ? '<img src="'.STATIC_SERVER.'common/symbols/warned.png" alt="Warned" />' : '';
$Badges.=($Enabled == '1' || $Enabled == '0' || !$Enabled) ? '': '<img src="'.STATIC_SERVER.'common/symbols/disabled.png" alt="Banned" />';


$UserBadges = get_user_badges($UserID);

show_header($Username,'user,bbcode,requests');
?>
<div class="thin">
	<h2><?=format_username($UserID, $Username, false, $Warned, $Enabled == 2 ? false : true, $ClassID, $CustomTitle, true)?></h2>
	<div class="linkbox">
<? if (!$OwnProfile) { ?>
		[<a href="inbox.php?action=compose&amp;to=<?=$UserID?>" title="Send a Private Message to <?=$Username?>">Send PM</a>]
<? 	$DB->query("SELECT Type FROM friends WHERE UserID='$LoggedUser[ID]' AND FriendID='$UserID'");
      if($DB->record_count() > 0) list($FType)=$DB->next_record();
	if(!$FType || $FType != 'friends' ) { ?>
		[<a href="friends.php?action=add&amp;friendid=<?=$UserID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Add to friends</a>]
<?	} 
      if(!$FType || $FType != 'blocked' ) { ?>
		[<a href="friends.php?action=add&amp;friendid=<?=$UserID?>&amp;type=blocked&amp;auth=<?=$LoggedUser['AuthKey']?>">Block User</a>]
<?	}?> 
            
		[<a href="reports.php?action=report&amp;type=user&amp;id=<?=$UserID?>">Report User</a>] 
<?

}

if (check_perms('users_edit_profiles', $Class)) {
?>
		[<a href="user.php?action=edit&amp;userid=<?=$UserID?>">Settings</a>]
<? }
if (check_perms('users_view_invites', $Class)) {
?>
		[<a href="user.php?action=invite&amp;userid=<?=$UserID?>">Invites</a>]
<? }
if (check_perms('admin_manage_permissions', $Class)) {
?>
		[<a href="user.php?action=permissions&amp;userid=<?=$UserID?>">Permissions</a>]
<? }
if (check_perms('users_logout', $Class) && check_perms('users_view_ips', $Class)) {
?>
		[<a href="user.php?action=sessions&amp;userid=<?=$UserID?>">Sessions</a>]
<? }
if (check_perms('admin_reports')) {
?>
		[<a href="reportsv2.php?view=reporter&amp;id=<?=$UserID?>">Reports</a>]
<? }
if (check_perms('users_mod')) {
?>
		[<a href="userhistory.php?action=token_history&amp;userid=<?=$UserID?>">Slots</a>]
<? } ?>
	</div>
      
	<div class="sidebar">
<?	if (empty($HeavyInfo['DisableAvatars'])) {
		if(check_perms('site_proxy_images') && !empty($Avatar)) {
			$Avatar = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&avatar='.$UserID.'&i='.urlencode($Avatar);
		}  
?> 
		<div class="head colhead_dark">Avatar</div>
                <div class="box">
			<div align="center">
			<? if ($Avatar) { ?>
					<img src="<?=$Avatar?>" class="avatar" style="<?=get_avatar_css($MaxAvatarWidth, $MaxAvatarHeight)?>" alt="<?=$Username?>'s avatar" />
			<? } else { ?>
					<img src="<?=STATIC_SERVER?>common/avatars/default.png" class="avatar" style="<?=get_avatar_css(100, 120)?>" alt="Default avatar" />
			<? } ?>
                  </div>
            </div>
<? } ?>
		<div class="head colhead_dark">Stats</div>
		<div class="box">
			<ul class="stats nobullet">
				<li>Joined: <?=$JoinedDate?></li>
<? if (check_paranoia_here('lastseen')) { ?>
				<li>Last Seen: <?=$LastAccess?></li>
<? } ?>
<? if (check_paranoia_here('uploaded')) { ?>
				<li>Uploaded: <?=get_size($Uploaded)?></li>
<? } ?>
<? if (check_paranoia_here('downloaded')) { ?>
				<li>Downloaded: <?=get_size($Downloaded)?></li>
<? } ?>
<? if (check_paranoia_here('ratio')) { ?>
				<li>Ratio: <?=ratio($Uploaded, $Downloaded)?></li>
<? } ?>
<? if (check_paranoia_here('requiredratio') && isset($RequiredRatio)) { ?>
				<li>Required ratio: <?=number_format((double)$RequiredRatio, 2)?></li>
<? } ?>
<? if ($OwnProfile || check_paranoia_here(false)) { //if ($OwnProfile || check_perms('users_mod')) { ?>
				<li><a href="userhistory.php?action=token_history&amp;userid=<?=$UserID?>">Slots</a>: <?=number_format($FLTokens)?></li>
<? } ?>
			</ul>
		</div>
<?

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
	$DB->query("SELECT COUNT(DISTINCT r.ID), SUM(rv.Bounty) FROM requests AS r LEFT JOIN requests_votes AS rv ON r.ID=rv.RequestID WHERE r.FillerID = ".$UserID);
	list($RequestsFilled, $TotalBounty) = $DB->next_record();
} else {
	$RequestsFilled = $TotalBounty = 0;
}

if (check_paranoia_here('requestsvoted_count') || check_paranoia_here('requestsvoted_bounty')) {
	$DB->query("SELECT COUNT(rv.RequestID), SUM(rv.Bounty) FROM requests_votes AS rv WHERE rv.UserID = ".$UserID);
	list($RequestsVoted, $TotalSpent) = $DB->next_record();
} else {
	$RequestsVoted = $TotalSpent = 0;
}

if(check_paranoia_here('uploads+')) {
	$DB->query("SELECT COUNT(ID) FROM torrents WHERE UserID='$UserID'");
	list($Uploads) = $DB->next_record();
} else {
	$Uploads = 0;
}

include(SERVER_ROOT.'/classes/class_user_rank.php');
$Rank = new USER_RANK;

$UploadedRank = $Rank->get_rank('uploaded', $Uploaded);
$DownloadedRank = $Rank->get_rank('downloaded', $Downloaded);
$UploadsRank = $Rank->get_rank('uploads', $Uploads);
$RequestRank = $Rank->get_rank('requests', $RequestsFilled);
$PostRank = $Rank->get_rank('posts', $ForumPosts);
$BountyRank = $Rank->get_rank('bounty', $TotalSpent);

if($Downloaded == 0) {
	$Ratio = 1;
} elseif($Uploaded == 0) {
	$Ratio = 0.5;
} else {
	$Ratio = round($Uploaded/$Downloaded, 2);
}
$OverallRank = $Rank->overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $Ratio);

?>
		<div class="head colhead_dark">Percentile Rankings (Hover for values)</div>
		<div class="box">
			<ul class="stats nobullet">
<? if (check_paranoia_here('uploaded')) { ?>
				<li title="<?=get_size($Uploaded)?>">Data uploaded: <?=number_format($UploadedRank)?></li>
<? } ?>
<? if (check_paranoia_here('downloaded')) { ?>
				<li title="<?=get_size($Downloaded)?>">Data downloaded: <?=number_format($DownloadedRank)?></li>
<? } ?>
<? if (check_paranoia_here('uploads+')) { ?>
				<li title="<?=$Uploads?>">Torrents uploaded: <?=number_format($UploadsRank)?></li>
<? } ?>
<? if (check_paranoia_here('requestsfilled_count')) { ?>
				<li title="<?=$RequestsFilled?>">Requests filled: <?=number_format($RequestRank)?></li>
<? } ?>
<? if (check_paranoia_here('requestsvoted_bounty')) { ?>
				<li title="<?=get_size($TotalSpent)?>">Bounty spent: <?=number_format($BountyRank)?></li>
<? } ?>
				<li title="<?=$ForumPosts?>">Posts made: <?=number_format($PostRank)?></li>
<? if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty'))) { ?>
				<li><strong>Overall rank: <?=number_format($OverallRank)?></strong></li>
<? } ?>
			</ul>
		</div>
<?
	if (check_perms('users_mod', $Class) || check_perms('users_view_ips',$Class) || check_perms('users_view_keys',$Class)) {
		$DB->query("SELECT COUNT(*) FROM users_history_passwords WHERE UserID='$UserID'");
		list($PasswordChanges) = $DB->next_record();
		if (check_perms('users_view_keys',$Class)) {
			$DB->query("SELECT COUNT(*) FROM users_history_passkeys WHERE UserID='$UserID'");
			list($PasskeyChanges) = $DB->next_record();
		}
		if (check_perms('users_view_ips',$Class)) {
			$DB->query("SELECT COUNT(DISTINCT IP) FROM users_history_ips WHERE UserID='$UserID'");
			list($IPChanges) = $DB->next_record();
			$DB->query("SELECT COUNT(DISTINCT IP) FROM xbt_snatched WHERE uid='$UserID' AND IP != ''");
			list($TrackerIPs) = $DB->next_record();
		}
		if (check_perms('users_view_email',$Class)) {
			$DB->query("SELECT COUNT(*) FROM users_history_emails WHERE UserID='$UserID'");
			list($EmailChanges) = $DB->next_record();
		}
?>
	<div class="head colhead_dark">History</div>
	<div class="box">
		<ul class="stats nobullet">
<?	if (check_perms('users_view_email',$Class)) { ?>
<li>Emails: <?=number_format($EmailChanges)?> [<a href="userhistory.php?action=email2&amp;userid=<?=$UserID?>">View</a>]&nbsp;[<a href="userhistory.php?action=email&amp;userid=<?=$UserID?>">Legacy view</a>]</li>
<?
	}
	if (check_perms('users_view_ips',$Class)) {
?>
	<li>IPs: <?=number_format($IPChanges)?> [<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>">View</a>]&nbsp;[<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>&amp;usersonly=1">View Users</a>]</li>
<?		if (check_perms('users_view_ips',$Class) && check_perms('users_mod',$Class)) { ?>
	<li>Tracker IPs: <?=number_format($TrackerIPs)?> [<a href="userhistory.php?action=tracker_ips&amp;userid=<?=$UserID?>">View</a>]</li>
<?		} ?>
<?
	}
	if (check_perms('users_view_keys',$Class)) {
?>
			<li>Passkeys: <?=number_format($PasskeyChanges)?> [<a href="userhistory.php?action=passkeys&amp;userid=<?=$UserID?>">View</a>]</li>
<?
	}
	if (check_perms('users_mod', $Class)) {
?>
			<li>Passwords: <?=number_format($PasswordChanges)?> [<a href="userhistory.php?action=passwords&amp;userid=<?=$UserID?>">View</a>]</li>
			<li>Stats: N/A [<a href="userhistory.php?action=stats&amp;userid=<?=$UserID?>">View</a>]</li>
<?
			
	}
?>
		</ul>
	</div>
<?	} ?>
		<div class="head colhead_dark">Personal</div>
		<div class="box">
			<ul class="stats nobullet">
				<li>Class: <?=$ClassLevels[$Class]['Name']?></li>
<?
// An easy way for people to measure the paranoia of a user, for e.g. contest eligibility
if($ParanoiaLevel == 0) {
	$ParanoiaLevelText = 'Off';
} elseif($ParanoiaLevel == 1) {
	$ParanoiaLevelText = 'Very Low';
} elseif($ParanoiaLevel <= 5) {
	$ParanoiaLevelText = 'Low';
} elseif($ParanoiaLevel <= 20) {
	$ParanoiaLevelText = 'High';
} else {
	$ParanoiaLevelText = 'Very high';
}
?>
				<li>Paranoia level: <span title="<?=$ParanoiaLevel?>"><?=$ParanoiaLevelText?></span></li>
<?	if (check_perms('users_view_email',$Class) || $OwnProfile) { ?>
				<li>Email: <a href="mailto:<?=display_str($Email)?>"><?=display_str($Email)?></a>
<?		if (check_perms('users_view_email',$Class)) { ?>
					[<a href="user.php?action=search&amp;email_history=on&amp;email=<?=display_str($Email)?>" title="Search">S</a>]
<?		} ?>
				</li>
<?	}

if (check_perms('users_view_ips',$Class)) {
?>
				<li>IP: <?=display_ip($IP)?></li>
				<li>Host: <?=get_host($IP)?></li>
<?
}

if (check_perms('users_view_keys',$Class) || $OwnProfile) {
?>
				<li>Passkey: <?=display_str($torrent_pass)?></li>
<? }
if (check_perms('users_view_invites')) {
	if (!$InviterID) {
		$Invited="<i>Nobody</i>";
	} else {
		$Invited='<a href="user.php?id='.$InviterID.'">'.$InviterName.'</a>';
	}
	
?>
				<li>Invited By: <?=$Invited?></li>
				<li>Invites: <? 
				$DB->query("SELECT count(InviterID) FROM invites WHERE InviterID = '$UserID'");
				list($Pending) = $DB->next_record();
				if($DisableInvites) { 
					echo 'X'; 
				} else { 
					echo number_format($Invites); 
				} 
				echo " (".$Pending.")"
				?></li>
<?
}

if (!isset($SupportFor)) {
	$DB->query("SELECT SupportFor FROM users_info WHERE UserID = ".$LoggedUser['ID']);
	list($SupportFor) = $DB->next_record();
}
if (check_perms('users_mod') || $OwnProfile || !empty($SupportFor)) {
	?>
		<li>Clients: <?
		$DB->query("SELECT DISTINCT useragent FROM xbt_files_users WHERE uid = ".$UserID);
		while(list($Client) = $DB->next_record()) {
			if (strlen($Clients) > 0) {
				$Clients .= "; ".$Client;
			} else {
				$Clients = $Client;
			}
		}
		echo $Clients;
		?></li>
<?
}
?>
			</ul>
		</div>
<?
// These stats used to be all together in one UNION'd query
// But we broke them up because they had a habit of locking each other to death.
// They all run really quickly anyways.
$DB->query("SELECT COUNT(x.uid), COUNT(DISTINCT x.fid) FROM xbt_snatched AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID'");
list($Snatched, $UniqueSnatched) = $DB->next_record();

$DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE AuthorID='$UserID'");
list($NumComments) = $DB->next_record();

$DB->query("SELECT COUNT(ID) FROM collages WHERE Deleted='0' AND UserID='$UserID'");
list($NumCollages) = $DB->next_record();

$DB->query("SELECT COUNT(DISTINCT CollageID) FROM collages_torrents AS ct JOIN collages ON CollageID = ID WHERE Deleted='0' AND ct.UserID='$UserID'");
list($NumCollageContribs) = $DB->next_record();

$DB->query("SELECT COUNT(DISTINCT GroupID) FROM torrents WHERE UserID = '$UserID'");
list($UniqueGroups) = $DB->next_record();

?>
		<div class="head colhead_dark">Community</div>
		<div class="box">
			<ul class="stats nobullet">
				<li>Forum Posts: <?=number_format($ForumPosts)?> [<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>" title="View">View</a>]</li>
<? if (check_paranoia_here('torrentcomments')) { ?>
				<li>Torrent Comments: <?=number_format($NumComments)?> [<a href="comments.php?id=<?=$UserID?>" title="View">View</a>]</li>
<? } elseif (check_paranoia_here('torrentcomments+')) { ?>
				<li>Torrent Comments: <?=number_format($NumComments)?></li>
<? } ?>
<? if (check_paranoia_here('collages')) { ?>
				<li>Collages started: <?=number_format($NumCollages)?> [<a href="collages.php?userid=<?=$UserID?>" title="View">View</a>]</li>
<? } elseif (check_paranoia_here('collages+')) { ?>
				<li>Collages started: <?=number_format($NumCollages)?></li>
<? } ?>
<? if (check_paranoia_here('collagecontribs')) { ?>
				<li>Collages contributed to: <?=number_format($NumCollageContribs)?> [<a href="collages.php?userid=<?=$UserID?>&amp;contrib=1" title="View">View</a>]</li>
<? } elseif(check_paranoia_here('collagecontribs+')) { ?>
				<li>Collages contributed to: <?=number_format($NumCollageContribs)?></li>
<? } ?>
<? if (check_paranoia_here('requestsfilled_list')) { ?>
				<li>Requests filled: <?=number_format($RequestsFilled)?> for <?=get_size($TotalBounty)?> [<a href="requests.php?type=filled&amp;userid=<?=$UserID?>" title="View">View</a>]</li>
<? } elseif (check_paranoia_here(array('requestsfilled_count', 'requestsfilled_bounty'))) { ?>
				<li>Requests filled: <?=number_format($RequestsFilled)?> for <?=get_size($TotalBounty)?></li>
<? } elseif (check_paranoia_here('requestsfilled_count')) { ?>
				<li>Requests filled: <?=number_format($RequestsFilled)?></li>
<? } elseif (check_paranoia_here('requestsfilled_bounty')) { ?>
				<li>Requests filled: <?=get_size($TotalBounty)?> collected</li>
<? } ?>
<? if (check_paranoia_here('requestsvoted_list')) { ?>
				<li>Requests voted: <?=number_format($RequestsVoted)?> for <?=get_size($TotalSpent)?> [<a href="requests.php?type=voted&amp;userid=<?=$UserID?>" title="View">View</a>]</li>
<? } elseif (check_paranoia_here(array('requestsvoted_count', 'requestsvoted_bounty'))) { ?>
				<li>Requests voted: <?=number_format($RequestsVoted)?> for <?=get_size($TotalSpent)?></li>
<? } elseif (check_paranoia_here('requestsvoted_count')) { ?>
				<li>Requests voted: <?=number_format($RequestsVoted)?></li>
<? } elseif (check_paranoia_here('requestsvoted_bounty')) { ?>
				<li>Requests voted: <?=get_size($TotalSpent)?> spent</li>
<? } ?>
<? if (check_paranoia_here('uploads')) { ?>
				<li>Uploaded: <?=number_format($Uploads)?> [<a href="torrents.php?type=uploaded&amp;userid=<?=$UserID?>" title="View">View</a>]<? if(check_perms('zip_downloader')) { ?> [<a href="torrents.php?action=redownload&amp;type=uploads&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]<? } ?></li>
<? } elseif (check_paranoia_here('uploads+')) { ?>
				<li>Uploaded: <?=number_format($Uploads)?></li>
<? } ?>
<?

if (check_paranoia_here('seeding+') || check_paranoia_here('leeching+')) {
	$DB->query("SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(x.uid) FROM xbt_files_users AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID' AND x.active=1 GROUP BY Type");
	$PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
	$Seeding = isset($PeerCount['Seeding'][1]) ? $PeerCount['Seeding'][1] : 0;
	$Leeching = isset($PeerCount['Leeching'][1]) ? $PeerCount['Leeching'][1] : 0;
}
?>
<? if (check_paranoia_here('seeding')) { ?>
				<li>Seeding: <?=number_format($Seeding)?> <?=($Snatched && ($OwnProfile || check_paranoia_here(false)))?'(' . 100*min(1,round($Seeding/$UniqueSnatched,2)).'%) ':''?>[<a href="torrents.php?type=seeding&amp;userid=<?=$UserID?>" title="View">View</a>]<? if (check_perms('zip_downloader')) { ?> [<a href="torrents.php?action=redownload&amp;type=seeding&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]<? } ?></li>
<? } elseif (check_paranoia_here('seeding+')) { ?>
				<li>Seeding: <?=number_format($Seeding)?></li>
<? } ?>
<? if (check_paranoia_here('leeching')) { ?>
				<li>Leeching: <?=number_format($Leeching)?> [<a href="torrents.php?type=leeching&amp;userid=<?=$UserID?>" title="View">View</a>]<?=($DisableLeech == 0 && check_perms('users_view_ips')) ? "<strong> (Disabled)</strong>" : ""?></li>
<? } elseif (check_paranoia_here('leeching+')) { ?>
				<li>Leeching: <?=number_format($Leeching)?></li>
<? } 
?>
<? if (check_paranoia_here('snatched+')) { ?>
				<li>Snatched: <?=number_format($Snatched)?> 
<? 	if(check_perms('site_view_torrent_snatchlist', $Class)) { ?>
					(<?=number_format($UniqueSnatched)?>)
<?	} ?>
<? } ?>
<? if (check_paranoia_here('snatched')) { ?>
				[<a href="torrents.php?type=snatched&amp;userid=<?=$UserID?>" title="View">View</a>]<? if(check_perms('zip_downloader')) { ?> [<a href="torrents.php?action=redownload&amp;type=snatches&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]<? } ?>
 				</li>
<? }

if(check_perms('site_view_torrent_snatchlist', $Class)) {
	$DB->query("SELECT COUNT(ud.UserID), COUNT(DISTINCT ud.TorrentID) FROM users_downloads AS ud INNER JOIN torrents AS t ON t.ID=ud.TorrentID WHERE ud.UserID='$UserID'");
	list($NumDownloads, $UniqueDownloads) = $DB->next_record();
?>
				<li>Downloaded: <?=number_format($NumDownloads)?> (<?=number_format($UniqueDownloads)?>) [<a href="torrents.php?type=downloaded&amp;userid=<?=$UserID?>" title="View">View</a>]</li>
<?
}

if(check_paranoia_here('invitedcount')) {
	$DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
	list($Invited) = $DB->next_record();
?>
				<li>Invited: <?=number_format($Invited)?></li>
<?
} ?>
			</ul>
		</div>
	</div>
	<div class="main_column">
<?
if ($RatioWatchEnds!='0000-00-00 00:00:00'
		&& (time() < strtotime($RatioWatchEnds))
		&& ($Downloaded*$RequiredRatio)>$Uploaded
		) {
?>
                <div class="head">Ratio watch</div>
		<div class="box">			
			<div class="pad">This user is currently on ratio watch, and must upload <?=get_size(($Downloaded*$RequiredRatio)-$Uploaded)?> in the next <?=time_diff($RatioWatchEnds)?>, or their leeching privileges will be revoked. Amount downloaded while on ratio watch: <?=get_size($Downloaded-$RatioWatchDownload)?></div>
		</div>
<? } ?>
                <div class="head">
                        <span style="float:left;">Profile<? if ($CustomTitle) { echo " - ".display_str(html_entity_decode($DisplayCustomTitle)); } ?></span>
                        <span style="float:right;"><?=!empty($Badges)?"$Badges&nbsp;&nbsp;":''?><a href="#" onclick="$('#profilediv').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(Hide)</a></span>&nbsp;
                </div>
                <div class="box">
                    <div id="profilediv">
                <div class="pad">
<?      if (!$Info) { ?>
				This profile is currently empty.
<?      } else { 
                        echo $Text->full_format($Info, get_permissions_advtags($UserID)); 
        }   ?>
                </div><div><? print_r($Paranoia)?></div>
<?     
          if ($UserBadges) {  ?>
                    <div class="badgesrow badges">
    <?
            print_badges_array($UserBadges);
   ?>
                    </div>
                           
<?        }   ?>
                  </div>
		</div>
<?

if (check_perms('users_view_bonuslog',$Class) || $OwnProfile) { ?>
                <div class="head">
                        <span style="float:left;">Bonus Credits</span>
                        <span style="float:right;"><a href="#" onclick="$('#bonusdiv').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(Hide)</a></span>&nbsp;
                </div>
		<div class="box">
			<div class="pad" id="bonusdiv">
                      <h4 class="center">Credits: <?=(!$BonusCredits ? '0.00' : number_format($BonusCredits,2))?></h4>
                      <span style="float:right;"><a href="#" onclick="$('#bonuslogdiv').toggle(); this.innerHTML=(this.innerHTML=='(Show Log)'?'(Hide Log)':'(Show Log)'); return false;">(Show Log)</a></span>&nbsp;

                      <div class="hidden" id="bonuslogdiv" style="padding-top: 10px;">
                          <div id="bonuslog" class="box pad">
                                <?=(!$BonusLog ? 'no bonus history' :$Text->full_format($BonusLog))?>
                          </div>
                      </div>
                  </div>
		</div>
<?
}

if ($Snatched > 4 && check_paranoia_here('snatched')) {
	$RecentSnatches = $Cache->get_value('recent_snatches_'.$UserID);
	if(!is_array($RecentSnatches)){
		$DB->query("SELECT
		g.ID,
		g.Name,
		g.Image
		FROM xbt_snatched AS s
		INNER JOIN torrents AS t ON t.ID=s.fid
		INNER JOIN torrents_group AS g ON t.GroupID=g.ID
		WHERE s.uid='$UserID'
		AND g.Image <> ''
		GROUP BY g.ID
		ORDER BY s.tstamp DESC
		LIMIT 5");
		$RecentSnatches = $DB->to_array();
				$Cache->cache_value('recent_snatches_'.$UserID, $RecentSnatches, 0); //inf cache
	}
?>
	<table class="recent" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5" style="padding:4px;">
                      	<span style="float:left;">Recent Snatches</span>
                        <span style="float:right;"><a href="#" onclick="$('#recentsnatches').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(Hide)</a></span>&nbsp;
			</td>
		</tr>
		<tr id="recentsnatches">
<?		
		foreach($RecentSnatches as $RS) { ?>
			<td>
				<a href="torrents.php?id=<?=$RS['ID']?>" title="<?=display_str($RS['Name'])?>"><img src="<?=$RS['Image']?>" alt="<?=display_str($RS['Name'])?>" width="107" /></a>
			</td>
<?		} ?>
		</tr>
	</table>
<?
}

if(!isset($Uploads)) { $Uploads = 0; }
if ($Uploads > 0 && check_paranoia_here('uploads')) {
	$RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
	if(!is_array($RecentUploads)){
		$DB->query("SELECT 
		g.ID,
		g.Name,
		g.Image
		FROM torrents_group AS g
		INNER JOIN torrents AS t ON t.GroupID=g.ID
		WHERE t.UserID='$UserID'
		GROUP BY g.ID
		ORDER BY t.Time DESC
		LIMIT 5");
		$RecentUploads = $DB->to_array();
		$Cache->cache_value('recent_uploads_'.$UserID, $RecentUploads, 0); //inf cache
	}
      if(count($RecentUploads)>0){
?>
	<table class="recent" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5" style="padding:4px;">     	
                        <span style="float:left;">Recent Uploads</span>
                        <span style="float:right;"><a href="#" onclick="$('#recentuploads').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(Hide)</a></span>
			</td>
		</tr>
		<tr id="recentuploads">
<?		foreach($RecentUploads as $RU) { ?>
			<td>
				<a href="torrents.php?id=<?=$RU['ID']?>" title="<?=$RU['Name']?>"><img src="<?=$RU['Image']?>" alt="<?=$RU['Name']?>" width="107" /></a>
			</td>
<?		} ?>
		</tr>
	</table>
<?
      }
}

$DB->query("SELECT ID, Name FROM collages WHERE UserID='$UserID' AND CategoryID='0' AND Deleted='0' ORDER BY Featured DESC, Name ASC");
$Collages = $DB->to_array();
$FirstCol = true;
foreach ($Collages as $CollageInfo) {
	list($CollageID, $CName) = $CollageInfo;
	$DB->query("SELECT ct.GroupID,
		tg.Image,
		tg.NewCategoryID
		FROM collages_torrents AS ct
		JOIN torrents_group AS tg ON tg.ID=ct.GroupID
		WHERE ct.CollageID='$CollageID'
		ORDER BY ct.Sort LIMIT 5");
	$Collage = $DB->to_array();
?>
	<table class="recent" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5" style="padding:4px;">
				<span style="float:left;">
					<?=display_str($CName)?> - <a href="collages.php?id=<?=$CollageID?>">see full</a>
				</span>
				<span style="float:right;">
					<a href="#" onclick="$('#collage<?=$CollageID?>').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;"><?=$FirstCol?'(Hide)':'(View)'?></a>
				</span>
			</td>
		</tr>
		<tr id="collage<?=$CollageID?>" <?=$FirstCol?'':'class="hidden"'?>>
<?	foreach($Collage as $C) {
			$Group = get_groups(array($C['GroupID']));
			$Group = array_pop($Group['matches']);
			list($GroupID, $GroupName, $TagList, $Torrents) = array_values($Group);
			
			$Name = $GroupName;
?>
			<td>
				<a href="torrents.php?id=<?=$GroupID?>" title="<?=$Name?>"><img src="<?=$C['Image']?>" alt="<?=$Name?>" width="107" /></a>
			</td>
<?	} ?>
		</tr>
	</table>
<?
	$FirstCol = false;
}



// Linked accounts
if(check_perms('users_mod')) {
	include(SERVER_ROOT.'/sections/user/linkedfunctions.php');
	user_dupes_table($UserID);
}

if ((check_perms('users_view_invites')) && $Invited > 0) {
	include(SERVER_ROOT.'/classes/class_invite_tree.php');
	$Tree = new INVITE_TREE($UserID, array('visible'=>false));
?>
                <div class="head">
			<span style="float:left;">Invite Tree</span>
                        <span style="float:right;"><a href="#" onclick="$('#invitetree').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(View)</a></span>&nbsp;
		</div>
		<div class="box">
                         <div id="invitetree" class="hidden">
				<? $Tree->make_tree(); ?>
			</div>
		</div> 
<?
}

// Requests
if (check_paranoia_here('requestsvoted_list')) {
	$DB->query("SELECT
			r.ID,
			r.CategoryID,
			r.Title,
			r.TimeAdded,
			COUNT(rv.UserID) AS Votes,
			SUM(rv.Bounty) AS Bounty
		FROM requests AS r
			LEFT JOIN users_main AS u ON u.ID=UserID
			LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
		WHERE r.UserID = ".$UserID."
			AND r.TorrentID = 0
		GROUP BY r.ID
		ORDER BY Votes DESC");
	
	if($DB->record_count() > 0) {
		$Requests = $DB->to_array();
?>
            <div class="head">
                    <span style="float:left;">Requests</span>
                    <span style="float:right;"><a href="#" onclick="$('#requests').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(View)</a></span>&nbsp;
            </div>                
            <div class="box">			
                    <div id="requests" class="hidden">
				<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
					<tr class="colhead_dark">
						<td style="width:48%;">
							<strong>Request Name</strong>
						</td>
						<td>
							<strong>Vote</strong>
						</td>
						<td>
							<strong>Bounty</strong>
						</td>
						<td>
							<strong>Added</strong>
						</td>
					</tr>
<?
		foreach($Requests as $Request) {
			list($RequestID, $CategoryID, $Title, $TimeAdded, $Votes, $Bounty) = $Request;

			$Request = get_requests(array($RequestID));
			$Request = $Request['matches'][$RequestID];
			if(empty($Request)) {
				continue;
			}

			list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Image, $Description,
			$FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;
					
                        $FullName ="<a href='requests.php?action=view&amp;id=".$RequestID."'>".$Title."</a>";
			
			$Row = (empty($Row) || $Row == 'a') ? 'b' : 'a';
?>
					<tr class="row<?=$Row?>">
						<td>
							<?=$FullName?>
                                                        <? if ($LoggedUser['HideTagsInLists'] !== 1) { ?>
							<div class="tags">
                                                        <? } ?>
<?			
			$Tags = $Request['Tags'];
			$TagList = array();
			foreach($Tags as $TagID => $TagName) {
				$TagList[] = "<a href='requests.php?tags=".$TagName."'>".display_str($TagName)."</a>";
			}
			$TagList = implode(', ', $TagList);
?>
								<?=$TagList?>
							</div>
						</td>
						<td>
							<span id="vote_count_<?=$RequestID?>"><?=$Votes?></span>
<?		  	if(check_perms('site_vote')){ ?>
							<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							&nbsp;&nbsp; <a href="javascript:Vote(0, <?=$RequestID?>)"><strong>(+)</strong></a>
<?			} ?> 
						</td>
						<td>
							<span id="bounty_<?=$RequestID?>"><?=get_size($Bounty)?></span>
						</td>
						<td>
							<?=time_diff($TimeAdded)?>
						</td>
					</tr>
<?		} ?>
				</table>
			</div>
		</div>
<?
	}
}

include_once(SERVER_ROOT.'/sections/staff/functions.php');
$FLS = get_fls();
$IsFLS = false;
foreach($FLS as $F) {
	if($LoggedUser['ID'] == $F['ID']) {
		$IsFLS = true;
		break;
	}
}
if (check_perms('users_mod', $Class) || $IsFLS) { 
	$UserLevel = $LoggedUser['Class'];
	$DB->query("SELECT 
					SQL_CALC_FOUND_ROWS
					ID, 
					Subject, 
					Status, 
					Level, 
					AssignedToUser, 
					Date, 
					ResolverID 
				FROM staff_pm_conversations 
				WHERE UserID = $UserID AND (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."')
				ORDER BY Date DESC");
	if ($DB->record_count()) {
		$StaffPMs = $DB->to_array();
?>
                <div class="head">
                        <span style="float:left;">Staff PMs</span>
                        <span style="float:right;"><a href="#" onclick="$('#staffpms').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(View)</a></span>&nbsp;
                </div>
		<div class="box">
                    <table width="100%" class="hidden" id="staffpms">
				<tr class="colhead">
					<td>Subject</td>
					<td>Date</td>
					<td>Assigned To</td>
					<td>Resolved By</td>
				</tr>
<?		foreach($StaffPMs as $StaffPM) {
			list($ID, $Subject, $Status, $Level, $AssignedTo, $Date, $ResolverID) = $StaffPM;
			// Get assigned
			if ($AssignedToUser == '') {
				// Assigned to class
				$Assigned = ($Level == 0) ? "First Line Support" : $ClassLevels[$Level]['Name'];
				// No + on Sysops
				if ($Assigned != 'Sysop') { $Assigned .= "+"; }
					
			} else {
				// Assigned to user
				$UserInfo = user_info($AssignedToUser);
				$Assigned = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);	
			} 
			
			if ($ResolverID) {
				$UserInfo = user_info($ResolverID);
				$Resolver = format_username($ResolverID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
			} else {
				$Resolver = "(unresolved)";
			}
			
			?>
				<tr>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
					<td><?=$Resolver?></td>
				</tr>
<?		} ?>				
			</table>
		</div>
<?	}
}

if (check_perms('users_mod', $Class)) { ?>
        <form id="form" action="user.php" method="post">
		<input type="hidden" name="action" value="moderate" />
		<input type="hidden" name="userid" value="<?=$UserID?>" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />

                <div class="head">
                        <span style="float:left;">Staff Notes</span>
                        <span style="float:right;"><a href="#" onclick="$('#staffnotes').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(Hide)</a></span>&nbsp;
                </div>               
		<div class="box">		
                  <div class="pad" id="staffnotes">
				<input type="hidden" name="comment_hash" value="<?=$CommentHash?>">
				<div id="admincommentlinks" class="AdminComment box pad"><?=$Text->full_format($AdminComment)?></div>
				<textarea id="admincomment" onkeyup="resize('admincomment');" class="AdminComment hidden" name="AdminComment" cols="65" rows="26" style="width:98%;"><?=display_str($AdminComment)?></textarea>
				<a href="#" name="admincommentbutton" onclick="ChangeTo('text'); return false;">Toggle Edit</a>
				<script type="text/javascript">
					resize('admincomment');
				</script>
			</div>
		</div>
            
                <div class="head">User Info</div>
		<table>
<?	if (check_perms('users_edit_usernames', $Class)) { ?>
			<tr>
				<td class="label">Username:</td>
				<td><input type="text" size="20" name="Username" value="<?=display_str($Username)?>" /></td>
			</tr>
<?
	}
	if (check_perms('users_edit_titles')) {
?>
			<tr>
				<td class="label">CustomTitle:<br/>(max 32)</td>
				<td><input class="long" type="text" name="Title" value="<?=display_str($CustomTitle)?>" /></td>
			</tr>
<?
	}

	if (check_perms('users_promote_below', $Class) || check_perms('users_promote_to', $Class-1)) {
?>
			<tr>
				<td class="label">Class:</td>
				<td>
					<select name="Class">
<?
		foreach ($ClassLevels as $CurClass) {
			if (check_perms('users_promote_below', $Class) && $CurClass['ID']>=$LoggedUser['Class']) { break; }
			if ($CurClass['ID']>$LoggedUser['Class']) { break; }
			if ($Class===$CurClass['Level']) { $Selected='selected="selected"'; } else { $Selected=""; }
?>
						<option value="<?=$CurClass['ID']?>" <?=$Selected?>><?=$CurClass['Name'].' ('.$CurClass['Level'].')'?></option>
<?		} ?>
					</select>
				</td>
			</tr>
<?
	}

	if (check_perms('users_give_donor')) {
?>
			<tr>
				<td class="label">Donor:</td>
				<td><input type="checkbox" name="Donor" <? if ($Donor == 1) { ?>checked="checked" <? } ?> /></td>
			</tr>
<?
	}
	if (check_perms('users_make_invisible')) {
?>
			<tr>
				<td class="label">Visible:</td>
				<td><input type="checkbox" name="Visible" <? if ($Visible == 1) { ?>checked="checked" <? } ?> /></td>
			</tr>
<?
	}

	if ((check_perms('users_edit_ratio',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_ratio') && $UserID == $LoggedUser['ID'])) {
?>
			<tr>
				<td class="label">Adjust Upload:</td>
				<td>
					<input type="hidden" name="OldUploaded" value="<?=$Uploaded?>" />
                              <input type="text" size="5" name="adjustupvalue" id="adjustupvalue" value="" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" title="Use '-' to remove from Upload" /> &nbsp;&nbsp;
                              <input name="adjustup" value="mb" type="radio"  onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" /> MB&nbsp;&nbsp;
                              <input name="adjustup" value="gb" type="radio" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" checked="checked" /> GB&nbsp;&nbsp;
                              <input name="adjustup" value="tb" type="radio" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" /> TB
                              
					<span style="margin-left:40px;" title="Current Upload"><?=get_size($Uploaded, 2)?></span>
                              <span style="margin-left: 10px;" id="adjustupresult" name="adjustupresult" title="Preview of Total Upload after adjustment"></span>
				</td>
			</tr>
			<tr>
				<td class="label">Adjust Download:</td>
				<td>
					<input type="hidden" name="OldDownloaded" value="<?=$Downloaded?>" />
                              
                              <input type="text" size="5" name="adjustdownvalue" id="adjustdownvalue" value="" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" title="Use '-' to remove from Download" /> &nbsp;&nbsp;
                              <input name="adjustdown" value="mb" type="radio"  onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" /> MB&nbsp;&nbsp;
                              <input name="adjustdown" value="gb" type="radio" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" checked="checked" /> GB&nbsp;&nbsp;
                              <input name="adjustdown" value="tb" type="radio" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" /> TB
                              
                              <span style="margin-left: 40px;" title="Current Download"><?=get_size($Downloaded, 2)?></span>
				      <span style="margin-left: 10px;" id="adjustdownresult" name="adjustdownresult" title="Preview of Total Download after adjustment"></span>
				</td>
			</tr>
                    <script type="text/javascript">
                        CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>);
                        CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>);
                    </script>
			<tr>
				<td class="label">Merge Stats <strong>From:</strong></td>
				<td>
					<input class="long" type="text" name="MergeStatsFrom" />
				</td>
			</tr>
<?
	}

	if ((check_perms('users_edit_tokens',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_tokens') && $UserID == $LoggedUser['ID'])) {
?>
			<tr>
				<td class="label">Slots:</td>
				<td>
					<input type="text" size="5" name="FLTokens" value="<?=$FLTokens?>" />
				</td>
			</tr>
<?
	}

	if ((check_perms('users_edit_credits',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_credits') && $UserID == $LoggedUser['ID'])) {
?>
			<tr>
				<td class="label">Bonus Credits</td>
				<td>
					<input type="text" size="5" name="BonusCredits" value="<?=$BonusCredits?>" />
				</td>
			</tr>
<?
	}

	if (check_perms('users_edit_invites')) {
?>
			<tr>
				<td class="label">Invites:</td>
				<td><input type="text" size="5" name="Invites" value="<?=$Invites?>" /></td>
			</tr>
<?
	}

	if (check_perms('admin_manage_fls') || (check_perms('users_mod') && $OwnProfile)) {
?>
			<tr>
				<td class="label">First Line Support:</td>
				<td><input class="long" type="text" name="SupportFor" value="<?=display_str($SupportFor)?>" /></td>
			</tr>
<?
	}

	if (check_perms('users_edit_reset_keys')) {
?>
			<tr>
				<td class="label">Reset:</td>
				<td>
					<input type="checkbox" name="ResetRatioWatch" id="ResetRatioWatch" /> <label for="ResetRatioWatch">Ratio Watch</label> |
					<input type="checkbox" name="ResetPasskey" id="ResetPasskey" /> <label for="ResetPasskey">Passkey</label> |
					<input type="checkbox" name="ResetAuthkey" id="ResetAuthkey" /> <label for="ResetAuthkey">Authkey</label> |
					<input type="checkbox" name="ResetIPHistory" id="ResetIPHistory" /> <label for="ResetIPHistory">IP History</label> |
					<input type="checkbox" name="ResetEmailHistory" id="ResetEmailHistory" /> <label for="ResetEmailHistory">Email History</label>
					<br />
					<input type="checkbox" name="ResetSnatchList" id="ResetSnatchList" /> <label for="ResetSnatchList">Snatch List</label> | 
					<input type="checkbox" name="ResetDownloadList" id="ResetDownloadList" /> <label for="ResetDownloadList">Download List</label>
				</td>
			</tr>
<?
	}

	if (check_perms('users_edit_password')) {
?>
			<tr>
				<td class="label">New Password:</td>
				<td>
					<input class="long" type="text" id="change_password" name="ChangePassword" />
				</td>
			</tr>
<?	} ?>
		</table>

            
            
            
<?	if (check_perms('users_edit_badges')) { ?>

                <div class="head">
                        <span style="float:left;">Manage User Badges</span>
                        <span style="float:right;"><a href="#" onclick="$('#badgesadmin').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;">(View)</a></span>&nbsp;
                </div>                
		<div class="box">
                  <div class="pad hidden" id="badgesadmin">
<?
                      $UserBadgesIDs = array(); // used in a mo to determine what badges user has for admin 
                      if ($UserBadges){
?>
                      <div class="pad"><h3>Current user badges (select to remove)</h3>
<?
                            foreach ($UserBadges as $UBadge) {
                                list($ID, $BadgeID, $Tooltip, $Name, $Image, $Auto, $Type ) = $UBadge;
                                $UserBadgesIDs[] = $BadgeID;
?>
                            <div class="badge">
                                <?='<img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" />'?>
                                <br />
                                <input type="checkbox" name="delbadge[]" value="<?=$ID?>" />
                                        <label for="delbadge[]"> <?=$Name.'<br/>';
                                                if($Type=='Unique') echo " *(unique)";
                                                elseif ($Auto) echo " (automatically awarded)";
                                                else echo " ($Type)";  ?></label>
                            </div>
<?
                            }
?>
                      </div><hr />
<?
                      }
?>
                      <div class="pad addbadges"><h3>Add user badges (select to add)</h3>
                          <p>Shop and single type items can be owned once by each user, multiple type items many times, and unique items only by one user at once</p>
                          <table class="noborder">
<? 
                        $DB->query("SELECT
                                    b.ID As Bid,
                                    b.Badge,
                                    b.Rank,
                                    b.Type,
                                    b.Title,
                                    b.Description,
                                    b.Image,
                                    IF(b.Type != 'Unique', TRUE, 
                                                        (SELECT COUNT(*) FROM users_badges 
                                                            WHERE users_badges.BadgeID=b.ID)=0) AS Available,
                                (SELECT Max(b2.Rank) 
                                        FROM users_badges AS ub2
                                   LEFT JOIN badges AS b2 ON b2.ID=ub2.BadgeID
                                       WHERE b2.Badge = b.Badge
                                         AND ub2.UserID = $UserID) As MaxRank
                                
                               FROM badges AS b
                               LEFT JOIN badges_auto AS ba ON b.ID=ba.BadgeID
                               WHERE b.Type != 'Shop' 
                                 AND ba.ID IS NULL
                               ORDER BY b.Sort");
 
                        $AvailableBadges = $DB->to_array();
                     
                    foreach($AvailableBadges as $ABadge){ // = $DB->next_record()
                        list($BadgeID, $Badge, $Rank, $Type, $Name, $Tooltip, $Image, $Available, $MaxRank) = $ABadge; 

                        if (!in_array($Type, array('Single','Shop')) || !$MaxRank || $MaxRank < $Rank ) {
                        ?>
                        <tr>
                            <td width="60px">
                            <div class="badge">
    <? 
                                echo '<img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" />';

                                if (!$Available ||  
                                     ($Type != 'Multiple' && in_array($BadgeID, $UserBadgesIDs) )  )
                                            $Disabled =' disabled="disabled" title="award is unavailable"';  
                                else $Disabled='';                        
    ?> 
                            </div>
                            </td>
                            <td>
                                <input  type="checkbox" name="addbadge[]" value="<?=$BadgeID?>"<?=$Disabled?> />
                                        <label for="addbadge[]"> <?=$Name; 
                                                if($Type=='Unique') echo " *(unique)";
                                                elseif ($Auto) echo " (automatically awarded)";
                                                else echo " ($Type)";?></label>
                                <br />
                                <input class="long" type="text" id="addbadge<?=$BadgeID?>" name="addbadge<?=$BadgeID?>"<?=$Disabled?> value="<?=$Tooltip?>" />
                            </td>
                        </tr>
<?                      }
                    }
?>                      </table>
                      </div>
			</div>
		</div>
<?	}




    if (check_perms('users_warn')) { ?>
		<div class="head">Warn User</div>
                <table>
			<tr>
				<td class="label">Warned:</td>
				<td>
					<input type="checkbox" name="Warned" <? if ($Warned != '0000-00-00 00:00:00') { ?>checked="checked"<? } ?> />
				</td>
			</tr>
<?		if ($Warned=='0000-00-00 00:00:00') { // user is not warned ?>
			<tr>
				<td class="label">Expiration:</td>
				<td>
					<select name="WarnLength">
						<option value="">---</option>
						<option value="1"> 1 Week</option>
						<option value="2"> 2 Weeks</option>
						<option value="4"> 4 Weeks</option>
						<option value="8"> 8 Weeks</option>
					</select>
				</td>
			</tr>
<?		} else { // user is warned ?>
			<tr>
				<td class="label">Extension:</td>
				<td>
					<select name="ExtendWarning">
						<option>---</option>
						<option value="1"> 1 Week</option>
						<option value="2"> 2 Weeks</option>
						<option value="4"> 4 Weeks</option>
						<option value="8"> 8 Weeks</option>
					</select>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Reason:</td>
				<td>
					<input class="long" type="text" name="WarnReason" />
				</td>
			</tr>
<?	} ?>
		</table>
                <div class="head">User Privileges</div>
		<table>
<?	if (check_perms('users_disable_posts') || check_perms('users_disable_any')) {
		$DB->query("SELECT DISTINCT Email, IP FROM users_history_emails WHERE UserID = ".$UserID." ORDER BY Time ASC");
		$Emails = $DB->to_array();
?>
			<tr>
				<td class="label">Disable:</td>
				<td>
					<input type="checkbox" name="DisablePosting" id="DisablePosting"<? if ($DisablePosting==1) { ?>checked="checked"<? } ?> /> <label for="DisablePosting">Posting</label>
<?		if (check_perms('users_disable_any')) { ?>  |
					<input type="checkbox" name="DisableAvatar" id="DisableAvatar"<? if ($DisableAvatar==1) { ?>checked="checked"<? } ?> /> <label for="DisableAvatar">Avatar</label> |
					<input type="checkbox" name="DisableInvites" id="DisableInvites"<? if ($DisableInvites==1) { ?>checked="checked"<? } ?> /> <label for="DisableInvites">Invites</label> |
					
					<input type="checkbox" name="DisableForums" id="DisableForums"<? if ($DisableForums==1) { ?>checked="checked"<? } ?> /> <label for="DisableForums">Forums</label> |
					<input type="checkbox" name="DisableTagging" id="DisableTagging"<? if ($DisableTagging==1) { ?>checked="checked"<? } ?> /> <label for="DisableTagging">Tagging</label> |
					<input type="checkbox" name="DisableRequests" id="DisableRequests"<? if ($DisableRequests==1) { ?>checked="checked"<? } ?> /> <label for="DisableRequests">Requests</label>
					<br />
					<input type="checkbox" name="DisableUpload" id="DisableUpload"<? if ($DisableUpload==1) { ?>checked="checked"<? } ?> /> <label for="DisableUpload">Upload</label> |
					<input type="checkbox" name="DisableLeech" id="DisableLeech"<? if ($DisableLeech==0) { ?>checked="checked"<? } ?> /> <label for="DisableLeech">Leech</label> |
					<input type="checkbox" name="DisablePM" id="DisablePM"<? if ($DisablePM==1) { ?>checked="checked"<? } ?> /> <label for="DisablePM">PM</label> |
					<input type="checkbox" name="DisableIRC" id="DisableIRC"<? if ($DisableIRC==1) { ?>checked="checked"<? } ?> /> <label for="DisableIRC">IRC</label>
				</td>
			</tr>
			<tr>
				<td class="label">Hacked:</td>
				<td>
					<input type="checkbox" name="SendHackedMail" id="SendHackedMail" /> <label for="SendHackedMail">Send hacked account email</label> to 
					<select name="HackedEmail">
<?
			foreach($Emails as $Email) {
				list($Address, $IP) = $Email;
?>
						<option value="<?=display_str($Address)?>"><?=display_str($Address)?> - <?=display_str($IP)?></option>
<?			} ?>
					</select>
				</td>
			</tr>

<?		} ?>
<?
	}

	if (check_perms('users_disable_any')) {
?>
			<tr>
				<td class="label">Account:</td>
				<td>
					<select name="UserStatus">
						<option value="0" <? if ($Enabled=='0') { ?>selected="selected"<? } ?>>Unconfirmed</option>
						<option value="1" <? if ($Enabled=='1') { ?>selected="selected"<? } ?>>Enabled</option>
						<option value="2" <? if ($Enabled=='2') { ?>selected="selected"<? } ?>>Disabled</option>
<?		if (check_perms('users_delete_users')) { ?>
						<optgroup label="-- WARNING --"></optgroup>
						<option value="delete">Delete Account</option>
<?		} ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">User Reason:</td>
				<td>
					<input class="long" type="text" name="UserReason" />
				</td>
			</tr>
			<tr>
				<td class="label">Restricted Forums (comma-delimited):</td>
				<td>
                            <input class="long" type="text" name="RestrictedForums" value="<?=display_str($RestrictedForums)?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Extra Forums (comma-delimited):</td>
				<td>
                            <input class="long" type="text" name="PermittedForums" value="<?=display_str($PermittedForums)?>" />
				</td>
			</tr>

<?	} ?>
		</table><!--<br />-->
<?	if(check_perms('users_logout')) { ?>
                <div class="head">Session</div>
		<table>
			<tr>
				<td class="label">Reset session:</td>
				<td><input type="checkbox" name="ResetSession" id="ResetSession" /></td>
			</tr>
			<tr>
				<td class="label">Log out:</td>
				<td><input type="checkbox" name="LogOut" id="LogOut" /></td>
			</tr>

		</table>
<?	} ?>
                <div class="head">Submit</div>
		<table>
			<tr>
				<td class="label">Reason:</td>
				<td>
				   <textarea rows="8" class="long" name="Reason" id="Reason" onkeyup="resize('Reason');"></textarea>
				</td>
			</tr>

			<tr>
				<td align="right" colspan="2">
					<input type="submit" value="Save Changes" />
				</td>
			</tr>
		</table>
        </form>
<? } ?>
	</div>
</div>
<? show_footer(); ?>
