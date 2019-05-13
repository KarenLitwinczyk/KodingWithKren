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
    if(!hasPostParams(['username','password','displayName'])){
	   die(json_encode(array("Message"=>"Missing required parameters")));
    }
    signup($dbh, $_POST['username'], $_POST['password'], $_POST['displayName']);
}elseif($action == "signin"){
    if(!hasPostParams(['username','password'])){
	   die(json_encode(array("Message"=>"Missing required parameters")));
    }
    signIn($dbh,$_POST['username'], $_POST['password']);
    
}elseif($action == "signout"){
    signout();
}elseif($action == "challengeCompleted"){
    challengeCompleted($dbh,$_POST['challenge']);
}elseif($action == "getHPItems"){
	getHPItems($dbh);
}elseif($action == "reset"){
	resetChallenges($dbh);
}

function signup($dbh, $username, $password, $displayName){
    $userAvailable = $dbh->prepare('select count(*) from users where username = :username;');
    $userAvailable->execute(array(':username'=>$username));
    $resUserAvailable = $userAvailable->fetch();
    $hashPassword = password_hash($password,PASSWORD_DEFAULT);
    if($resUserAvailable[0] > 0){
	   die(json_encode(array("Message"=>"This username is already taken, please pick a different one")));
    }else{
	   $addUser = $dbh->prepare('insert into users (username, password, displayName) values(:username, :password, :displayName);');
	   $addUser->execute(array(':username'=>$username, ':password'=>$hashPassword,':displayName'=>$displayName));
	   $result = $addUser->fetchAll();
	   echo json_encode(array("Message"=>"You are now a user!"));
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
	       echo json_encode(array("Message"=>"You are now signed in!","Page"=>"HomePage.html"));
        }
    }else{
	   echo json_encode(array("Message"=>"Invalid Username or Password"));

    }
}

function signout(){
    session_start();
    session_destroy();
    echo json_encode(array("Message"=>"SignIn.html"));
}

function challengeCompleted($dbh,$challenge){
    session_start();
    //get the userName from current session
    $name = $_SESSION["username"];
    // echo json_encode(array("Message"=>"In Function"));
    //find the user id by the username
    $findUserID = $dbh->prepare('select id from users where username = :name;');
    $findUserID->execute(array(':name'=>$name));
    $findUserRes = $findUserID->fetch();
    //find the challenge id with challenge title

    $findChallID = $dbh->prepare('select id from challenges where title = :challenge;');
    $findChallID->execute(array(':challenge'=>$challenge));
    $findChallRes = $findChallID->fetch();
    //make sure row doesn't exist, if does do nothing
    $findRow = $dbh->prepare('select * from completedChallenges where userID = :userID and challengeID = :challID;');
    $findRow->execute(array(':userID'=>$findUserRes[0], ':challID'=>$findChallRes[0]));
    $findRowRes = $findRow->fetch();
    if($findRowRes[0] > 0){

    }else{
        $insertPass = $dbh->prepare('insert into completedChallenges (userID, challengeID) values (:userID, :challengeID);');
        $insertPass->execute(array(':userID'=>$findUserRes[0],':challengeID'=>$findChallRes[0]));
        $res = $insertPass->fetch();
        
    }
   
}

function getHPItems($dbh){
	session_start();
	$currUser = $_SESSION["username"];
	$getUserId = $dbh->prepare('select id from users where username = :currUser;');
	$getUserId->execute(array(':currUser'=>$currUser));
	$getUserRes = $getUserId->fetch();
	
	$getDisplayName = $dbh->prepare('select displayName from users where username = :currUser;');
	$getDisplayName->execute(array(':currUser'=>$currUser));
	$getDNRes = $getDisplayName->fetch();
	
	$getCompletedChall = $dbh->prepare('select c.title from challenges c join completedChallenges d on d.challengeID = c.id join users u on d.userID = u.id where u.id = :userID;');
	$getCompletedChall->execute(array(':userID'=>$getUserRes[0]));
	$completedChalls = $getCompletedChall->fetchALL(PDO::FETCH_ASSOC);
	
	//echo json_encode(array("Message"=>"Hello","userName"=>"karen"));
	echo json_encode(array("displayName"=>$getDNRes[0],"completedChalls"=>$completedChalls));
	
	
}	
function resetChallenges($dbh){
	session_start();
	$currUser = $_SESSION["username"];
	$getcurrID = $dbh->prepare('select id from users where username = :currUser;');
	$getcurrID->execute(array(':currUser'=>$currUser));
	$ID = $getcurrID->fetch();
	
	$eraseProgress = $dbh->prepare('delete from completedChallenges where userID = :ID;');
	$eraseProgress->execute(array(':ID'=>$ID[0]));
	$reset = $eraseProgress->fetch();
	
}

function hasPostParams($params){
    foreach($params as $param){
	if(!isset($_POST[$param]))
	    return false;
    }
    return true;

}

?>