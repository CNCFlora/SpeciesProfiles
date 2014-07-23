<?php
session_start();

require '../vendor/autoload.php';


$rest = new \Rest\Server($_GET['q']);
$rest->setAccept(array("*"));
$rest->setParameter("strings",\cncflora\Utils::$strings);

foreach(\cncflora\Utils::$config as $k=>$v)
    $rest->setParameter($k,$v);

if(($user = $rest->getRequest()->getSession('user')) != null) {
    $rest->setParameter("user",$user);
    $rest->setParameter("logged",true);
    foreach($user->roles as $r) {
        $rest->setParameter("role-".strtolower($r->role),true);
    }
} else {
    $rest->setParameter("user",null);
    $rest->setParameter("logged",false);
}

$rest->addMap('POST',"/login",function($r) {
    $preuser = json_decode($r->getRequest()->getBody());
    if(ENV=='test') {
        $r->getRequest()->setSession('user',$preuser);
    } else {
        $user = \cncflora\Utils::http_get(CONNECT_URL."/api/token?token=".$preuser->token);
        $r->getRequest()->setSession('user',$user);
    }
    return new Rest\View\JSon($user);
});

$rest->addMap('POST',"/logout",function($r) {
    $r->getRequest()->setSession('user',null);
    return new Rest\View\JSon(null);
});


$rest->addMap("GET","/",function($r){
    return new \cncflora\View('index.html',array());
});


$rest->addMap("GET","/families",'\cncflora\controller\Species');
$rest->addMap("GET","/family/:family",'\cncflora\controller\Species::family');
$rest->addMap("GET","/specie/:name",'\cncflora\controller\Species::specie'); 

$rest->addMap("POST","/profile",'\cncflora\controller\Profile::createProfile');
$rest->addMap("POST","/profile/:id","\cncflora\controller\Profile::save");
$rest->addMap("GET","/profile/:id",'\cncflora\controller\Profile');
$rest->addMap("GET","/profile/:id/view",'\cncflora\controller\Profile::view');
$rest->addMap("GET","/profile/:id/sig",'\cncflora\controller\Profile::sig');
$rest->addMap("GET","/profile/:id/edit",'\cncflora\controller\Profile::edit');

$rest->addMap("GET","/profile/:id/validate","\cncflora\controller\Validation::validateForm");
$rest->addMap("POST","/profile/:id/validate","\cncflora\controller\Validation::validate");

$rest->addMap("GET","/profile/:id/review","\cncflora\controller\Review");
$rest->addMap("POST","/profile/:id/validate/:created/done","\cncflora\controller\Review::markDone");

$rest->addMap("POST","/profile/:id/send/:status","\cncflora\controller\Workflow::changeStatus");
$rest->addMap("POST","/profile/:id/sendTo","\cncflora\controller\Workflow::changeStatusForce");

$rest->addMap("GET","/workflow","\cncflora\controller\Workflow");
$rest->addMap("GET","/workflow/:family","\cncflora\controller\Workflow::family");

$rest->addMap("GET","/habitats2fito",'\cncflora\controller\Profile::habitats2fito');

$rest->addMap("GET",'.*',function($r) {
    $uri = $r->getRequest()->getURI();
    if(strpos($uri,'resources') === false) return new Rest\Controller\NotFound;
    $file = substr($uri,strpos($uri,'resources'));
    return new Rest\Controller\Redirect(BASE.$file);
});


$rest->execute();

