<?php
require './vendor/autoload.php';

use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\FirestoreClient;

session_start();

$config = include('config.php');

/*Generates a guest user id.

    Keyword arguments: None
    Returns: String - user id of the form guest-xxxxx
*/
function generateGuestUserId(){
    $uid = "guest-";
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 5; $i++) {
        $uid .= $characters[rand(0, $charactersLength - 1)];
    }
    return $uid;
}

/*Loads the given world.

    Keyword arguments:
    worldName -- The name of the world to load
    Returns: JSON - The entire game world
*/
function loadWorld($worldName){
    try{
        $world = json_decode(file_get_contents("worlds/".$worldName.".json"), true);
    }
    catch(Exception $e) {
        $world = NULL;
    }
    return $world;
}

function getQueryString($location){
    /*Form the query string (array) using the given location

    Keyword arguments:
    location -- A String of length 3 (Ex. '010')
    Returns: Array of length 3
    */
    $split = str_split($location, 1);
    $splitInt = array_map(function($x) { return (int)$x; }, $split);
    return $splitInt;
}

function getRoomInfo($query, $floors){
    /*Gets all the information about the room.
    Contains the room id, monsters, items,
    room type (transparent or solid)

    Keyword arguments:
    query -- An array of length 3 [floor, i-row, j-column]
    floors -- All the floors of the given world
    Returns: JSON - A json object with information about the room
    */
    return $floors[$query[0]]["rooms"][$query[1]][$query[2]];
}

function getUsersInRoom(){
    /*Gets all users in the room except the current user

    Keyword arguments:
    Returns: Array - A list of user ids
    */
    $usersInRoom = array();
    $db = new FirestoreClient();
    #Get the user list for a particular location in the world
    $worldRef = $db->collection('world')->document($_SESSION['world_id']);
    $snapshot = $worldRef->snapshot();
    // return var_dump($snapshot->data());
    // foreach ($snapshot->data() as $user) {
    //     return $var_dump(user);
    //     $usersList[] = $user;
    // }
    $usersList = $snapshot->data();
    $userLocation = $_SESSION['user_location'];
    $uid = $_SESSION['user_id'];
    foreach ($usersList as $key => $val) {
        if((int)$key == (int)$userLocation){
            $usersInRoom = $usersList[$key];
        }
    }

    foreach ($usersInRoom as $key => $val) {
        if($key == $uid){
            unset($usersInRoom[$key]);
        }
    }
    return $usersInRoom;
}

function setUserToRoom(){
    /*Sets the user to the current location in the firebase map
    This helps in determining the list of users in a particular location
    */
    $userLocation = $_SESSION['user_location'];
    $uid = $_SESSION['user_id'];
    $db = new FirestoreClient();
    $worldRef = $db->collection('world')->document($_SESSION['world_id']);
    $worldRef->update([
        ['path' =>  $userLocation, 'value' => FieldValue::arrayUnion([$uid])]
    ]);
    return "done";
}


function handleNavigationCommand($cmd, $query){
    /*Handles all user navigation commands

    Keyword arguments:
    cmd -- the command to be executed (up, north etc.)
    query -- The query string
    Returns: the status, room information, users in the room
    */
    #Initialize some temporary variables
    $text = "";
    $progress = false;#Check if the user has actually progressed or not in the end
    $negIndex = false;
    $roomInfo = array();
    $usersInRoom = array();
    $status = "success";
    if($cmd == "up")#Move one position up
        $query[0] += 1;
    elseif ($cmd == "down")#Move one position down
        if ($query[0]-1 >= 0)#Should always be greater than zero (no negative indexes)
            $query[0] -= 1;
        else
            $negIndex = true;
    elseif ($cmd == "north")#Move one position forward
        $query[1] += 1;
    elseif ($cmd == "south")#Move one position backward
        if ($query[1]-1 >= 0)#Should always be greater than zero (no negative indexes)
            $query[1] -= 1;
        else
            $negIndex = true;
    elseif ($cmd == "east")#Move one position to right
        $query[2] += 1;
    elseif ($cmd == "west")#Move one position to left
        if ($query[2]-1 >= 0)#Should always be greater than zero (no negative indexes)
            $query[2] -= 1;
        else
            $negIndex = true;
    if($negIndex == true){
        $roomInfo['text'] = "Umm...you cant make that move...try something else.";
        return array("Progress Failed", $roomInfo, $usersInRoom);
    }
    $db = new FirestoreClient();
    $world = loadWorld($_SESSION["world_id"]);
    $old_location = $_SESSION['user_location'];
    try{
        #Get the room info of the new room
        $roomInfo = getRoomInfo($query, $world["floors"]);
        if ($roomInfo["type"] == "solid"){#You can't enter a room of type solid
            $text = "Oops...this room is locked (solid room) ! try something else.";
            $status = "Progress Failed";
        }
        elseif($roomInfo["type"] == "transparent"){
            $progress = true;
            $_SESSION["user_location"] = $roomInfo["id"];
        }
        else{
            $text = "Umm...you cant make that move...try something else.";
            $status = "Progress Failed";
            $progress = false;
        }
    }
    catch(Exception $e) {
        $text = "Umm...you cant make that move...try something else.";
        $status = "Progress Failed";
        $progress = false;
    }
    if($progress == true){#If the user has progressed
        $new_location = $_SESSION['user_location'];
        $usersInRoom = getUsersInRoom();
        $worldRef = $db->collection('world')->document($_SESSION['world_id']);
        #Remove user from current map location in db and relocate the user to the new location in db
        $worldRef->update([
            ['path' =>  $old_location, 'value' => FieldValue::arrayRemove([$_SESSION['user_id']])]
        ]);
        $worldRef = $db->collection('world')->document($_SESSION['world_id']);
        $worldRef->update([
            ['path' =>  $new_location, 'value' => FieldValue::arrayUnion([$_SESSION['user_id']])]
        ]);
        $roomInfo['text'] = generateRoomInfoText($roomInfo);
    }
    else
        $roomInfo['text'] = $text;
    return array($status, $roomInfo, $usersInRoom);
}


function handleCommunicationCommand($cmd, $cargs){
    /*Handles all user communication commands

    Keyword arguments:
    cmd -- the command to be executed (say, tell, yell)
    cargs -- The arguments for the command
    Returns: JSON - A status after executing the command
    */
    $db = new FirestoreClient();
    if($cmd == "tell"){#Send message to a specific user
        if (sizeof(cargs) < 3)
            return json_encode(array("status"=>"Command Not Found"));
        $user = $cargs[1];
        $msg = join(" ", array_slice($cargs,2));
        $usersInRoom = getUsersInRoom();
        if (in_array($user , $usersInRoom)){#Check if the target user is in the same room (a user might leave the room at anytime)
            $userRef = $db->collection('users')->document($user);
            $ins_msg = array("from"=>$_SESSION['user_id'],"message"=>$msg,"type"=>"tell");
            $userRef->update([
                ['path' =>  "messages", 'value' => FieldValue::arrayUnion([$ins_msg])]
            ]); 
            return json_encode(array("status"=>"success", "roomInfo"=>array('text'=> 'message sent')));
        }
        else
            return json_encode(array("status"=>"User Not Visible"));
    }
    elseif($cmd == "yell"){#Send message to all the users in the world
        if (sizeof($cargs) < 2)
            return json_encode(array("status"=>"Command Not Found"));
        $msg = join(" ", array_slice($cargs,1));
        $allUsersRef = $db->collection('users');
        $allUsers = $allUsersRef->documents();
        $ins_msg = array("from"=>$_SESSION['user_id'],"message"=>$msg,"type"=>"yell");
        foreach ($allusers as $user) {
            $user_id = $user->id();
            $userRef = $db->collection('users')->document($user_id);
            $userRef->update([
                ['path' =>  "messages", 'value' => FieldValue::arrayUnion([$ins_msg])]
            ]);      
        }
        return json_encode(array("status"=>"success", "roomInfo"=>array('text'=> 'message sent')));
    }
    elseif($cmd == "say"){#Send message to all the users in the room
        if (sizeof($cargs) < 2)
            return json_encode(array("status"=>"Command Not Found"));
        $msg = join(" ", array_slice($cargs,1));
        $usersInRoom = getUsersInRoom();
        foreach ($usersInRoom as $user){
            $userRef = $db->collection('users')->document($user);
            $ins_msg = array("from"=>$_SESSION['user_id'],"message"=>$msg,"type"=>"say");
            $userRef->update([
                ['path' =>  "messages", 'value' => FieldValue::arrayUnion([$ins_msg])]
            ]); 
        }
        return json_encode(array("status"=>"success", "roomInfo"=>array('text'=> 'message sent')));
    }
    return "done";
}



function generateRoomInfoText($room){
    /*Generates a room description for the given room

    Keyword arguments:
    room -- The room object for which description should be created
    Returns: String - A text based on the room contents
    */
    $text = "";
    if (array_key_exists("monsters" , $room) && sizeof($room["monsters"])>0) {
        $monster = $room["monsters"][0];
        $text = $text."Whoa! You see a <b class='user'>level ".$monster['level']." ".$monster['type']."</b> in the room.";
    }
    if (array_key_exists("items" , $room) && sizeof($room["items"])>0) {
        $items = $room["items"][0];
        if($items["type"] == "object"){
            $text = $text."You see a ".$items["name"]." in the room.";
        }
        elseif($items["type"] == "weapon"){
            $text = $text."You see a <b class='weapon'>".$items["name"]." (level ".(string)$items["level"].+")</b> in the room.";
        }
    }
    if(sizeof($room["monsters"]) == 0 && sizeof($room["items"]) == 0){
        $text = $text."The room is has no items.";
    }
    return $text;
}
?>