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
        $rest->setParameter("role-".strtolower( $r->role ),true);
    }
} else {
    $rest->setParameter("logged",false);
}

$rest->addMap('POST',"/login",function($r) {
    $u = json_decode($r->getRequest()->getBody());
    $r->getRequest()->setSession('user',$u);
    return new Rest\View\JSon($u);
});

$rest->addMap('POST',"/logout",function($r) {
    $r->getRequest()->setSession('user',null);
    return new Rest\View\JSon(null);
});

$rest->addMap("GET","/",'\cncflora\controller\Search');
$rest->addMap("GET","/biblio",'\cncflora\controller\Search::biblio');

$rest->addMap("GET","/families",'\cncflora\controller\Species'); 
$rest->addMap("GET","/family/:family",'\cncflora\controller\Species::family'); 
$rest->addMap("GET","/specie/:id",'\cncflora\controller\Species::specie'); 

$rest->addMap("POST","/profile",'\cncflora\controller\Profile::createProfile');
$rest->addMap("GET","/profile/:id",'\cncflora\controller\Profile');
$rest->addMap("GET","/profile/:id/view",'\cncflora\controller\Profile::view');
$rest->addMap("GET","/profile/:id/edit",'\cncflora\controller\Profile::edit');
$rest->addMap("POST","/profile/:id","\cncflora\controller\Profile::save");

$rest->addMap("GET","/profile/:id/validate","\cncflora\controller\Validation::validateForm");
$rest->addMap("POST","/profile/:id/validate","\cncflora\controller\Validation::validate");

$rest->addMap("GET","/profile/:id/review","\cncflora\controller\Review");
$rest->addMap("POST","/profile/:id/validate/:created/done","\cncflora\controller\Review::markDone");

$rest->addMap("POST","/profile/:id/send/:status","\cncflora\controller\Workflow::changeStatus");
$rest->addMap("GET","/work","\cncflora\controller\Workflow");
$rest->addMap("GET","/work/:family/:status","\cncflora\controller\Workflow::family");

$rest->addMap("GET",'.*',function($r) {
    $uri = $r->getRequest()->getURI();
    if(strpos($uri,'resources') === false) return new Rest\Controller\NotFound;
    $file = substr($uri,strpos($uri,'resources'));
    return new Rest\Controller\Redirect("/".BASE_PATH.$file);
});

$rest->execute();

