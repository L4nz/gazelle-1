<?
enforce_login();
if(!check_perms('site_upload')) { error(403); }
if($LoggedUser['DisableUpload']) {
	error('Your upload privileges have been revoked.');
}

if(!empty($_POST['submit'])) {  
           // $Err ="upload";
    include(SERVER_ROOT.'/sections/upload/upload_handle.php');   
    
} else {
    
    switch ($_GET['action']){
          case 'add_template': // ajax call
                include(SERVER_ROOT.'/sections/upload/add_template.php');
                break;
          case 'delete_template': // ajax call
                include(SERVER_ROOT.'/sections/upload/delete_template.php');
                break;
            
        default:
           // $Err ="default";
                include(SERVER_ROOT.'/sections/upload/upload.php');
    }
}
?>
