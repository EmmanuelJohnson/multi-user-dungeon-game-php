<?php
    require './vendor/autoload.php';

    use Google\Cloud\Firestore\FieldValue;
    use Google\Cloud\Firestore\FirestoreClient;
    $db = new FirestoreClient();
    include('services.php');
    $config = include('config.php');
    session_start();

    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    @$command = $request->command;
    #Check if the game has started
    #If the game is not started we won't receive other commands
    if ($_SESSION['start_game'] != true){
        echo json_encode(array("status"=>"Not Started"));
        return; #Return game not started status
    }
    $cSplit = explode(" ", $command);
    $text = "";
    #Command to be executed
    $command = strtolower($cSplit[0]);
    #Check to which type the command belongs to
    if (in_array($command , $config['NAVIGATION'])){
        $currentLocation = $_SESSION['user_location'];
        $query = getQueryString($currentLocation);
        list($status, $roomInfo, $usersInRoom) = handleNavigationCommand($command, $query);
        echo json_encode(array("status"=>$status, "roomInfo"=>$roomInfo, "usersInRoom"=>$usersInRoom));
        return;
    }
    elseif(in_array($command , $config['COMMUNICATION'])){
        $status = handleCommunicationCommand($command, $cSplit);
        echo $status;
        return;
    }
    else{
        echo json_encode(array("status"=>"Command Not Found"));
        return;
    }
?>