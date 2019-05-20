<?php
    include('services.php');
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    @$worldId = $request->worldId;
    $world = loadWorld($worldId);
    if($world == NULL)
        echo json_encode(array("status" => "error"));
    $_SESSION["world_id"] = $worldId;
    $_SESSION["world"] = $world;
    setcookie("user_loc", '', 0);
    if(isset($_SESSION['user_location']) && !empty($_SESSION['user_location'])) {
        unset($_SESSION['user_location']);
    }
    echo json_encode(array("status"=>"success", "intro"=>$world["intro"]));
?>