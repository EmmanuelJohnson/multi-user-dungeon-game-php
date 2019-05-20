<?php
require './vendor/autoload.php';

use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\FirestoreClient;
$db = new FirestoreClient();
session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- App Favicon -->
        <link rel="icon" type="image/png" href="/static/assets/images/logo.png" sizes="32x32" />

        <title>Multi User Dungeon</title>
        
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <link href="https://fonts.googleapis.com/css?family=Bangers|Press+Start+2P" rel="stylesheet">
        <link href="app/assets/styles/custom.css" rel="stylesheet">

        <script src="app/assets/js/modernizr.min.js"></script>
    </head>


    <body ng-app="mud">
        <div ng-view></div>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
        
        <script src="app/assets/js/angular.min.js" type="text/javascript"></script>
        <script src="app/assets/js/angular-sanitize.min.js"></script>
        <script src="app/assets/js/angular-resource.min.js"></script>
        <script src="app/assets/js/angular-route.min.js"></script>
        <script src="https://www.gstatic.com/firebasejs/5.9.3/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/5.9.3/firebase-firestore.js"></script>
        
        <?php 
                $configs = include('config.php');
                echo '<script>var worlds = '.json_encode($configs["WORLDS"]) .';</script>'; 
        ?>

        <script src="app/assets/js/ang/mainAng.js" type="text/javascript"></script>
        <script src="app/assets/js/ang/controllers.js" type="text/javascript"></script>
        <script src="app/assets/js/ang/services.js" type="text/javascript"></script>
        <script src="app/assets/js/ang/filters.js" type="text/javascript"></script>
    </body>
</html>
