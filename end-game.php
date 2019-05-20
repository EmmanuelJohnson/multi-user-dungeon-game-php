<?php
    require './vendor/autoload.php';

    use Google\Cloud\Firestore\FieldValue;
    use Google\Cloud\Firestore\FirestoreClient;
    $db = new FirestoreClient();
    include('services.php');
    $config = include('config.php');
    session_start();

    if(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $currentLocation = $_SESSION['user_location'];
        $worldRef = $db->collection('world')->document($_SESSION['world_id']);
        $worldRef->update([
            ['path' =>  $currentLocation, 'value' => FieldValue::arrayRemove([$_SESSION['user_id']])]
        ]);
        session_destroy();
        return "success";
    }
    else{
        return "error";
    }
?>