<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-type: application/json');
$dbms = 'mysql';
$host = 'localhost';
$database = 'klitwinczyk_thesis';
$dsn = "$dbms:dbname=$database;host=$host";
$user = 'klitwinczyk';
$password = 'rollwiththepunches';

if(!isset($_POST['action'])){
    die(json_encode(array("Message"=>"No action given! :^(")));
}

try{
    $dbh = new PDO($dsn, $user, $password);
}catch(PDOException $e){
    die(json_encode(array("Message"=>"Connection Failed: " . $e->getMessage())));
}

$action = $_POST['action'];
if($action == "signup"){
    if(!hasPostParams(['username','password','displayName']))
	die(json_encode(array("Message"=>"Missing required parameters")));
    signup($dbh, $_POST['username'], $_POST['password'], $_POST['displayName']);
}elseif($action == "signin"){
    if(!hasPostParams(['username','password']))
	   die(json_encode(array("Message"=>"Missing required parameters")));
    signIn($dbh,$_POST['username'], $_POST['password']);
    
}elseif($action == "signout"){
    signout();
    
}
elseif($action == "passedChallenge"){

}


function passedChallenge($dbh, $challengeTitle){
    
}


function signup($dbh, $username, $password, $displayName){
    $userAvailable = $dbh->prepare('select count(*) from users where username = :username;');
    // $findHandle = $dbh->prepare('select count(*) from users where handle = :handle;');
    $userAvailable->execute(array(':username'=>$username));
    // $findHandle->execute(array(':handle'=>$handle));
    $resUserAvailable = $findUsername->fetch();
    // $resFindHandle = $findHandle->fetch();
    $hashPassword = password_hash($password,PASSWORD_DEFAULT);
    if($resUserAvailable[0] > 0){
	die(json_encode(array("This username is already taken, please pick a different one")));
    }else{
	$addUser = $dbh->prepare('insert into users (username, password, displayName) values(:username, :password, :displayName);');
	$addUser->execute(array(':username'=>$username, ':password'=>$hashPassword,':displayName'=>$displayName));
	$result = $addUser->fetchAll();
	echo json_encode(array("Message"=>"Yay, you're now a blabber user!"));
    }
}


function signIn($dbh,$username, $password){
    session_start();
    $checkCredentials = $dbh->prepare('select password from users where username = :username;');
    $checkCredentials->execute(array(':username'=>$username));
    $result = $checkCredentials->fetch();
    if(password_verify($password, $result[0])){
        if(array_key_exists("loggedIn", $_SESSION)){
	    if($_SESSION["username"]!= $username){
	        session_destroy();
	        $_SESSION["loggedIn"] = true;
	        $_SESSION["username"] = $username;
	    }
	    else{
	      echo json_encode(array("Message"=>"You are already logged in"));
	    }
        }else{
	    $_SESSION["loggedIn"] = true;
	    $_SESSION["username"] = $username;
	       echo json_encode(array("Message"=>"You are now signed in!"));
        }
    }else{
	   echo json_encode(array("Message"=>"Invalid Username or Password"));

    }
}

function signout(){
    session_start();
    session_destroy();
    echo json_encode(array("Message"=>"Good bye"));
}

function hasPostParams($params){
    foreach($params as $param){
	if(!isset($_POST[$param]))
	    return false;
    }
    return true;

}

?>