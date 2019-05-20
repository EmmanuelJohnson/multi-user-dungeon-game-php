<?php
    require './vendor/autoload.php';

    use Google\Cloud\Firestore\FieldValue;
    use Google\Cloud\Firestore\FirestoreClient;
    $db = new FirestoreClient();
    include('services.php');

    #Generate an unique user id
    $uid = generateGuestUserId();
    #Load world in session
    $world = loadWorld($_SESSION["world_id"]);
    $startLocation = $world["start"];
    #Set session values
    $_SESSION['start_game'] = true;
    $_SESSION['user_id'] = $uid;
    $_SESSION['user_location'] = $startLocation;
    #Set user info in firebase
    $docRef = $db->collection('users')->document($uid);
    $docRef->set([
        'userid' => $uid,
        'messages' => []
    ]);
    #Retrieve users in the starting room
    $usersInRoom = getUsersInRoom();
    setUserToRoom();
    #Retrieve the room info for the start location
    $query = getQueryString($startLocation);
    $roomInfo = getRoomInfo($query, $world["floors"]);
    $roomInfo['text'] = generateRoomInfoText($roomInfo);
    #Return the response
    setcookie("user_id", $uid);
    setcookie("user_loc", $startLocation);
    echo json_encode(array("status"=>"success", "roomInfo"=>$roomInfo, "usersInRoom"=>$usersInRoom));
?>