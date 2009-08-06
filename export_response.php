<?php
$data = $_POST['data'];
header('Content-Description: File Transfer');
header('Content-type: application/json');
header('Content-Disposition: attachment; filename="comments_policy.json"');
if (get_magic_quotes_gpc()){
    echo stripslashes($data);
}else{
    echo $data;
}




?>
