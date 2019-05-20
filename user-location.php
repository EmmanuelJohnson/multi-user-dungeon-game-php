<?php
    include('services.php');

    if(isset($_SESSION['user_location']) && !empty($_SESSION['user_location'])) {
        echo $_SESSION['user_location'];
    }
    else{
        echo "error";
    }
?>