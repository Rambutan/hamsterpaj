<?php
    require('../include/core/common.php');
    require(PATHS_INCLUDE . 'libraries/photoblog.lib.php');
    
    if ( ! isset($_GET['id']) || ! is_numeric($_GET['id']) )
    {
        echo 'Faulty ID.';
        die;
    }
    
    // fetch a single image
    if ( ! isset($_GET['month']) )
    {
        $options = array(
            'id' => $_GET['id']
        );
    }
    // fetch an entire month
    else
    {
        if ( ! is_numeric($_GET['month']) )
        {
            die('Faulty month');
        }
        
        $options = array(
            'user' => $_GET['id'],
            'month' => $_GET['month']
        );
    }
    
    $photo = photoblog_photos_fetch($options);
    echo json_encode($photo);