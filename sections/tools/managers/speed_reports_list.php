<?

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = "desc") {
    global $OrderBy, $OrderWay;
    if ($SortKey == $OrderBy) {
        if ($OrderWay == "desc") {
            $NewWay = "asc";
        } else {
            $NewWay = "desc";
        }
    } else {
        $NewWay = $DefaultWay;
    }

    return "tools.php?action=cheats&amp;order_way=" . $NewWay . "&amp;order_by=" . $SortKey . "&amp;" . get_url(array('order_way', 'order_by'));
}


function format_torrentid($torrentID, $name, $maxlen = 20) {
    if ($torrentID == 0) return 'None';
    if ($name == '') $tname = $torrentID;
    else $tname = $name;
    $str = '<a href="torrents.php?torrentid='.$torrentID.'" title="'.$tname.'">'. cut_string($tname, $maxlen).'</a>';
    if ($name == '') $str = "torrent not found [$str]";
    return $str;
}
function size_span($speed, $text) {
    return '<span style="color:'.($speed>0?'black':'lightgrey').'">'.$text.'</span>';
}
function speed_span($speed, $highlightlimit, $color, $text) {
    if ($speed>=$highlightlimit) $scolor = $color;
    elseif ($speed>0) $scolor = 'black';
    else $scolor = 'lightgrey';
    return '<span style="color:'.$scolor.'">'.$text.'</span>';
}


if(!check_perms('users_manage_cheats')) { error(403); }


if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
    $OrderWay = 'asc'; // For header links
} else {
    $_GET['order_way'] = 'desc';
    $OrderWay = 'desc';
}
                       //     xbt.id, uid, Username, xbt.downloaded, remaining, t.Size, xbt.uploaded, 
                     ////       upspeed, downspeed, timespent, peer_id, xbt.ip, tg.ID, fid, tg.Name, xbt.mtime
                                    
// User 	Remaining 	Uploaded 	UpSpeed 	ClientIPaddress 	date time 	
//	TorrentID 	Total 	Downloaded 	DownSpeed 	                	total time 
                                    
if (empty($_GET['order_by']) || !in_array($_GET['order_by'], array('Username', 'Name', 'remaining', 'Size', 'uploaded', 'downloaded',
                                                                        'upspeed', 'downspeed', 'ip', 'mtime', 'timespent' ))) {
    $_GET['order_by'] = 'mtime';
    $OrderBy = 'mtime'; 
} else {
    $OrderBy = $_GET['order_by'];
}

        
$DB->query("SELECT DeleteRecordsMins, KeepSpeed FROM site_options ");
list($DeleteRecordsMins, $KeepSpeed) = $DB->next_record();
        
$ViewSpeed = isset($_GET['viewspeed'])?(int)$_GET['viewspeed']:$KeepSpeed;
 
show_header('Speed Reports','watchlist');

//---------- user watch

$DB->query("SELECT wl.UserID, um.Username, StaffID, um2.Username AS Staffname, Time, wl.Comment, KeepTorrents,
                             ui.Donor, ui.Warned, um.Enabled, um.PermissionID, um.Title
              FROM users_watch_list AS wl
         LEFT JOIN users_main AS um ON um.ID=wl.UserID
         LEFT JOIN users_info AS ui ON ui.UserID=wl.UserID
         LEFT JOIN users_main AS um2 ON um2.ID=wl.StaffID
          ORDER BY Time DESC");
$Watchlist = $DB->to_array('UserID');
?>
<div class="thin">
    <h2>Speed Reports</h2>
    <div class="head">User watch list &nbsp;<img src="static/common/symbols/watched.png" alt="view" /><span style="float:right;"><a href="#" onclick="$('#uwatchlist').toggle();this.innerHTML=this.innerHTML=='(hide)'?'(view)':'(hide)';">(view)</a></span>&nbsp;</div>
        <table id="uwatchlist" class="hidden">
            <tr class="rowa"> 
                <td colspan="6" style="text-align: left;color:grey"> 
                    Users in the watch list will have their records retained until they are manually deleted. You can use this information to help detect ratio cheaters.<br/>
                    note: use the list sparingly - this can quickly fill the database with a huge number of records.
                </td>
            </tr>
            <tr class="colhead">
                <td class="center"></td>
                <td class="center">User</td>
                <td class="center">Time added</td>
                <td class="center">added by</td>
                <td class="center">comment</td>
                <!--<td class="center" width="100px" title="keep torrent records related to this user">keep torrents</td>-->
                <td class="center" width="240px"></td>
            </tr>
<?
            $row = 'a';
            if(count($Watchlist)==0){
?> 
                    <tr class="rowb">
                        <td class="center" colspan="7">no users on watch list</td>
                    </tr>
<?
            } else {
                foreach ($Watchlist as $Watched) {
                    list($UserID, $Username, $StaffID, $Staffname, $Time, $Comment, $KeepTorrents,
                                       $IsDonor, $Warned, $Enabled, $ClassID, $CustomTitle) = $Watched;
                    $row = ($row === 'b' ? 'a' : 'b');
?> 
    
                    <tr class="row<?=$row?>">
                        <form action="tools.php" method="post">
                            <input type="hidden" name="action" value="edit_userwl" />
                            <input type="hidden" name="viewspeed" value="<?=$ViewSpeed?>" />
                            <input type="hidden" name="userid" value="<?=$UserID?>" />
                            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            <td class="center">
                                <a href="?action=cheats&viewspeed=<?=$ViewSpeed?>&userid=<?=$UserID?>" title="View records for just <?=$Username?>">
                                    [view]
                                </a>
                            </td>
                            <td class="center"><?=format_username($UserID, $Username, $IsDonor, $Warned, $Enabled, $ClassID, $CustomTitle, false)?></td>
                            <td class="center"><?=time_diff($Time, 2, true, false, 1)?></td>
                            <td class="center"><?=format_username($StaffID, $Staffname)?></td>
                            <td class="center" title="<?=$Comment?>"><?=cut_string($Comment, 40)?></td>
                            <!--<td class="center">
                                <input type="checkbox" name="keeptorrent" <?if($KeepTorrents)echo'checked="checked"'?> value="1" title="if checked keep all torrent records this user is on as well" />
                            </td>-->
                            <td class="center">
                                <!--<input type="submit" name="submit" value="Save" title="Save edited value" />-->
                                <input type="submit" name="submit" value="Delete Records" title="Remove all of this users records from the watchlist" /> 
                                <input type="submit" name="submit" value="Remove" title="Remove user from watchlist" /> 
                            </td>
                        </form>
                    </tr>
<?              }
            }
            
    //---------- torrrent watch

    $DB->query("SELECT TorrentID, tg.Name, StaffID, um.Username AS Staffname, tl.Time, tl.Comment
                  FROM torrents_watch_list AS tl
             LEFT JOIN users_main AS um ON um.ID=tl.StaffID
             LEFT JOIN torrents AS t ON t.ID=tl.TorrentID
             LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
              ORDER BY Time DESC");
    $TWatchlist = $DB->to_array('TorrentID');

            ?>
    </table><br/>
    <div class="head">Torrent watch list &nbsp;<img src="static/common/symbols/watched.png" alt="view" /><span style="float:right;"><a href="#" onclick="$('#twatchlist').toggle();this.innerHTML=this.innerHTML=='(hide)'?'(view)':'(hide)';">(view)</a></span>&nbsp;</div>
    <table id="twatchlist" class="hidden">
        <tr class="rowa"> 
                <td colspan="6" style="text-align: left;color:grey"> 
                    Torrents in the watch list will have all their records retained until they are manually deleted. You can use this information to help detect ratio cheaters.<br/>
                    note: use the list sparingly - this can quickly fill the database with a huge number of records.
                </td>
        </tr>
        <tr class="colhead">
                <td class="center"></td>
                <td class="center">Torrent</td>
                <td class="center">Time added</td>
                <td class="center">added by</td>
                <td class="center">comment</td>
                <td class="center" width="100px"></td>
        </tr>
<?
        $row = 'a';
        if(count($TWatchlist)==0){
?> 
            <tr class="rowb">
                <td class="center" colspan="6">no torrents on watch list</td>
            </tr>
<?
        } else {
                foreach ($TWatchlist as $Watched) {
                    list($TorrentID, $TName, $StaffID, $Staffname, $Time, $Comment) = $Watched;
                    $row = ($row === 'b' ? 'a' : 'b');
?> 
                    <tr class="row<?=$row?>">
                        <form action="tools.php" method="post">
                            <input type="hidden" name="action" value="edit_torrentwl" />
                            <input type="hidden" name="viewspeed" value="<?=$ViewSpeed?>" />
                            <input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
                            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            <td class="center">
                                <a href="?action=cheats&viewspeed=<?=$ViewSpeed?>&torrentid=<?=$TorrentID?>" title="View records for just this torrent">
                                    [view]
                                </a>
                            </td>
                            <td class="center"><?=format_torrentid($TorrentID, $TName,40)?></td>
                            <td class="center"><?=time_diff($Time, 2, true, false, 1)?></td>
                            <td class="center"><?=format_username($StaffID, $Staffname)?></td>
                            <td class="center" title="<?=$Comment?>"><?=cut_string($Comment, 40)?></td>
                            <td class="center">
                                <input type="submit" name="submit" value="Remove" title="Remove torrent from watchlist" /> 
                            </td>
                        </form>
                    </tr>
<?              }
        }  ?>
    </table>
    <br/>
    <div class="head">options</div>
<?
        //---------- options 
        
    if (is_number($_GET['userid']) && $_GET['userid']>0) {
        $_GET['userid'] = (int)$_GET['userid'];
        $WHERE = " AND xbt.uid='$_GET[userid]' ";
        $ViewInfo = "User ($_GET[userid]) ". $Watchlist[$_GET[userid]]['Username'] .' &nbsp;&nbsp; ';
    } elseif (is_number($_GET['torrentid']) && $_GET['torrentid']>0) {
        $_GET['torrentid'] = (int)$_GET['torrentid'];
        $WHERE = " AND xbt.fid='$_GET[torrentid]' ";
        $ViewInfo = "Torrent ($_GET[torrentid]) &nbsp;&nbsp; ". $TWatchlist[$_GET[torrentid]]['Name'] .' &nbsp;&nbsp; ';
    } else {
        $ViewInfo = 'all over speed specified';
    }

    $CanManage = check_perms('admin_manage_cheats');
?>
    <table class="box pad">
        <form action="tools.php" method="post">
            <tr class="colhead"><td colspan="3">storage settings: </td></tr>
            <tr>
                <input type="hidden" name="action" value="save_records_options" />
                <input type="hidden" name="userid" value="<?=$_GET['userid']?>" />
                <input type="hidden" name="torrentid" value="<?=$_GET['torrentid']?>" />
                <input type="hidden" name="viewspeed" value="<?=$ViewSpeed?>" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <td class="center">
                            <label for="delrecordmins">Delete unwatched records after </label>
<? if($CanManage) { ?>
                            <select id="delrecordmins" name="delrecordmins" title="Delete unwatched records after this time">
                                <option value="0"<?=($DeleteRecordsMins==0?' selected="selected"':'');?>>&nbsp;asap&nbsp;&nbsp;</option>
<?                              for($i=1;$i<5;$i++){  
                                    $mins = $i * 15;  ?>
                                    <option value="<?=$mins?>" <?=($DeleteRecordsMins==$mins?' selected="selected"':'');?>>&nbsp;<?=time_span($mins*60);?>&nbsp;&nbsp;</option>
<?                              }
                                for($i=1;$i<25;$i++){  
                                    $mins = $i * 120;  ?>
                                    <option value="<?=$mins?>" <?=($DeleteRecordsMins==$mins?' selected="selected"':'');?>>&nbsp;<?=time_span($mins*60);?>&nbsp;&nbsp;</option>
<?                              }  ?>
                            </select>
<? } else { ?>
                            <input name="delrecords" type="text" style="width:130px;color:black;" disabled="disabled" value="<?=time_span($DeleteRecordsMins*60)?>" title="Delete unwatched records after this time" />
<? }  ?>
                </td>
                <td  class="center">    
                            <label for="keepspeed" title="Keep Speed">Keep unwatched records with upload speed over </label>
<? if($CanManage) { ?>
                            <select id="keepspeed" name="keepspeed" title="Keep unwatched records over this speed">
                                <option value="524288"<?=($KeepSpeed==524288?' selected="selected"':'');?>>&nbsp;<?=get_size(524288);?>/s&nbsp;&nbsp;</option>
<?                              for($i=1;$i<21;$i++){  
                                    $speed = $i * 1048576;  ?>
                                    <option value="<?=$speed?>" <?=($KeepSpeed==$speed?' selected="selected"':'');?>>&nbsp;<?=get_size($speed);?>/s&nbsp;&nbsp;</option>
<?                              } ?>
                            </select>
<? } else { ?>
                            <input type="text" name="keepspeed" style="width:130px;color:black;" disabled="disabled" value="<?=get_size($KeepSpeed);?>/s" title="Keep unwatched records over this speed" />
<? }  ?>
                </td>
<? if($CanManage){ ?>   
                <td  class="center">
                    <input type="submit" value="Save Changes" />
                </td>
<? }  ?>
            </tr>
            <tr class="colhead"><td colspan="3">view settings: </td></tr>
            <tr>  
                <td class="center">
                    Viewing: <?=$ViewInfo?> &nbsp; (order: <?="$OrderBy $OrderWay"?>)
<?                  if ($ViewInfo!='all over speed specified') { ?>
                        <a href="?action=cheats&viewspeed=<?=$ViewSpeed?>" title="Removes any user or torrent filters for viewing (still applies speed filter)">View All</a>
<?                  } ?>
                </td>
                <td colspan="2" class="center">
                    <label for="viewspeed" title="View Speed">View records with upload speed over </label>
                    <select id="viewspeed" name="viewspeed" title="Hide records under this speed" onchange="change_view('<?=$_GET['userid']?>','<?=$_GET['torrentid']?>')">
                        <option value="0"<?=($ViewSpeed==0?' selected="selected"':'');?>>&nbsp;0&nbsp;&nbsp;</option>
                        <option value="262144"<?=($ViewSpeed==262144?' selected="selected"':'');?>>&nbsp;<?=get_size(262144);?>/s&nbsp;&nbsp;</option>
                        <option value="524288"<?=($ViewSpeed==524288?' selected="selected"':'');?>>&nbsp;<?=get_size(524288);?>/s&nbsp;&nbsp;</option>
<?                      for($i=1;$i<21;$i++){  
                            $speed = $i * 1048576;  ?>
                            <option value="<?=$speed?>" <?=($ViewSpeed==$speed?' selected="selected"':'');?>>&nbsp;<?=get_size($speed);?>/s&nbsp;&nbsp;</option>
<?                      } ?>
                    </select>
                </td>
            </tr>
        </form>
<? if($CanManage){   ?> 
    <form action="tools.php" method="post">
        <input type="hidden" name="action" value="test_delete_schedule" />
        <input type="hidden" name="viewspeed" value="<?=$ViewSpeed?>" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
            <tr class="rowa">
                <td colspan="8" style="text-align: right;"> 
                    <input type="submit" value="Run auto-delete schedule manually" title="Will run the delete schedule for speed records based on settings above" /> 
                </td>
            </tr>
    </form>
<? }  ?>
    </table>
    <script type="text/javascript">
        function change_view(userid, torrentid){
            var selectObj = $('#viewspeed').raw();
            var selSpeed=selectObj.options[selectObj.selectedIndex].value;
            location.href = "tools.php?action=cheats&viewspeed="+selSpeed+"&userid="+userid+"&torrentid="+torrentid;
        }
    </script>
    <br/>
<?
//---------- print records
            
list($Page,$Limit) = page_limit(50);

$DB->query("SELECT Count(*) FROM xbt_peers_history");
list($TotalResults) = $DB->next_record();

$DB->query("SELECT SQL_CALC_FOUND_ROWS
                            xbt.id, uid, Username, xbt.downloaded, remaining, t.Size, xbt.uploaded, 
                            upspeed, downspeed, timespent, peer_id, xbt.ip, tg.ID, fid, tg.Name, xbt.mtime,
                             ui.Donor, ui.Warned, um.Enabled, um.PermissionID, um.Title
                          FROM xbt_peers_history AS xbt
                     LEFT JOIN users_main AS um ON um.ID=xbt.uid
                     LEFT JOIN users_info AS ui ON ui.UserID=xbt.uid
                     LEFT JOIN torrents AS t ON t.ID=xbt.fid
                     LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
                         WHERE upspeed>='$ViewSpeed' $WHERE
                      ORDER BY $OrderBy $OrderWay
                         LIMIT $Limit");
$Records = $DB->to_array();
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
 
$Pages=get_pages($Page,$NumResults,50,9);

 // array('Username', 'Name', 'remaining', 'Size', 'uploaded', 'downloaded',
                                  //              'upspeed', 'downspeed', 'ip', 'mtime', 'timespent' ) 
?>
    
	<div class="linkbox"><?=$Pages?></div>
     
    <div class="head"><?=" $NumResults / $TotalResults"?> records</div>
        <table>
            <tr class="colhead">
                <td style="width:50px"></td>
                <td class="center"><a href="<?=header_link('Username') ?>">User</a></td>
                <td class="center"><a href="<?=header_link('remaining') ?>">Remaining</a></td>
                <td class="center"><a href="<?=header_link('uploaded') ?>">Uploaded</a></td>
                <td class="center"><a href="<?=header_link('upspeed') ?>">UpSpeed</a></td>
                <td class="center"><span style="color:#777">-clientID-</span> &nbsp;<a href="<?=header_link('ip') ?>">Client IP address</a></td>
                <td class="center"><a href="<?=header_link('mtime') ?>">date time</a></td>
                <td width="10px" rowspan="2" title="toggle selection for all records on this page">
                    <input type="checkbox" onclick="toggleChecks('speedrecords',this)" title="toggle selection for all records on this page" />
                </td>
            </tr>
            <tr class="colhead">
                <td ></td>
                <td class="center"><a href="<?=header_link('Name') ?>"><span style="color:#777">TorrentID</span></a></td>
                <td class="center"><a href="<?=header_link('Size') ?>"><span style="color:#777">Total</span></a></td>
                <td class="center"><a href="<?=header_link('downloaded') ?>"><span style="color:#777">Downloaded</span></a></td>
                <td class="center"><a href="<?=header_link('downspeed') ?>"><span style="color:#777">DownSpeed</span></a></td>
                <td class="center"><span style="color:#777">host</span></td>
                <td class="center"><a href="<?=header_link('timespent') ?>"><span style="color:#777">total time</span></a></td>
            </tr>
    <form id="speedrecords" action="tools.php" method="post">
        <input type="hidden" name="action" value="delete_speed_records" />
        <input type="hidden" name="viewspeed" value="<?=$ViewSpeed?>" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?
            $row = 'a';
            if($NumResults==0){
?> 
                    <tr class="rowb">
                        <td class="center" colspan="12">no speed records</td>
                    </tr>
<?
            } else {
                foreach ($Records as $Record) {
                    list($ID, $UserID, $Username, $Downloaded, $Remaining, $Size, $Uploaded, $UpSpeed, $DownSpeed, 
                                       $Timespent, $ClientPeerID, $IP, $GroupID, $TorrentID, $Name, $Time,
                                       $IsDonor, $Warned, $Enabled, $ClassID, $CustomTitle) = $Record;
                    $row = ($row === 'a' ? 'b' : 'a');
                    $ipcc = geoip($IP);
?> 
                    <tr class="row<?=$row?>">
                        <td>
<?                          if ($_GET['userid']!=$UserID) {   
 ?>                           <a href="?action=cheats&viewspeed=0&userid=<?=$UserID?>" title="View records for just <?=$Username?>">[view]</a> <? 
 }                          if (!array_key_exists($UserID, $Watchlist)) {   
 ?>                           <a onclick="watchlist_add('<?=$UserID?>',true);return false;" href="#" title="Add <?=$Username?> to watchlist"><img src="static/common/symbols/watched.png" alt="view" /></a><?
                            }  ?>
                              <a onclick="remove_records('<?=$UserID?>');return false;" href="#" title="Remove all speed records belonging to <?=$Username?> from watchlist"><img src="static/common/symbols/disabled.png" alt="del records" /></a>
                         </td>
                        <td class="center">
<?                          echo format_username($UserID, $Username, $IsDonor, $Warned, $Enabled, $ClassID, $CustomTitle, false);  ?>
                        </td>
                        <td class="center"><?=get_size($Remaining)?></td>
                        <td class="center"><img src="static/styles/<?= $LoggedUser['StyleName'] ?>/images/seeders.png" title="up"/> <?=size_span($Uploaded, get_size($Uploaded))?></td>
                        <td class="center"><?=speed_span($UpSpeed, $KeepSpeed, 'red', get_size($UpSpeed).'/s')?></td>
                        <td class="center"><span style="color:#555"><?=substr($ClientPeerID,0,8)?></span> &nbsp;<?=display_ip($IP, $ipcc)?></td>
                        <td class="center"><?=time_diff($Time, 2, true, false, 1)?></td>
                        <td rowspan="2">
                            <input class="remove" type="checkbox"  name="rid[]" value="<?=$ID?>" title="check to remove selected records" />
                        </td>
                    </tr>
                    <tr class="row<?=$row?>">
                        <td><span style="color:#555">
<?                          if ($_GET['torrentid']!=$TorrentID) {
                        ?>  <a href="?action=cheats&viewspeed=0&torrentid=<?=$TorrentID?>" title="View records for just this torrent">[view] </a> <? 
                            }
                            if ($GroupID && !array_key_exists($TorrentID, $TWatchlist)) {
                       ?>   <a onclick="twatchlist_add('<?=$GroupID?>','<?=$TorrentID?>',true);" href="#" title="Add torrent to watchlist"><img src="static/common/symbols/watched.png" alt="view" /></a> <? 
                            }  ?>
                        </td>
                        <td class="center">
                            <span style="color:#555"><?=format_torrentid($TorrentID, $Name)?></span> 
                        </td>
                        <td class="center"><span style="color:#555"><?=get_size($Size)?></span></td>
                        <td class="center"><img src="static/styles/<?= $LoggedUser['StyleName'] ?>/images/leechers.png" title="down"/> <?=size_span($Downloaded, get_size($Downloaded))?></td>
                        <td class="center"><?=speed_span($DownSpeed, $KeepSpeed, 'purple', get_size($DownSpeed).'/s')?></td>
                        <td class="center"><span style="color:#555"><?=get_host($IP)?> </span></td>
                        <td class="center"><span style="color:#555" title="<?=time_span($Timespent, 4)?>"><?=time_span($Timespent, 2)?></span></td>
                    </tr>
<?              }
            }
            $row = ($row === 'b' ? 'a' : 'b');
            ?>
            <tr class="row<?=$row?>"> 
                <td colspan="8" style="text-align: right;"> 
                    <input type="submit" name="delselected" value="Delete selected" title="Delete selected speed records" /> 
                </td>
            </tr>
    </form>
        </table>
	<div class="linkbox"><?=$Pages?></div>
</div>
<? show_footer(); ?>