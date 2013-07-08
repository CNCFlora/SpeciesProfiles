<?php
session_start();

require '../vendor/autoload.php';
require 'Utils.php';
require 'View.php';

$rest = new Rest\Server($_GET['q']);

if(($user = $rest->getRequest()->getSession('user')) != null) {
    $rest->setParameter("user",$user);
    $rest->setParameter("logged",true);
} else {
    $rest->setParameter("logged",false);
}

$rest->setAccept(array("*"));

$data = Utils::$config;
foreach($data as $k=>$v) {
    $rest->setParameter($k,$v);
}

$rest->setParameter("strings",Utils::$strings);

$rest->addMap("GET","/",function($r) {
    $result = array();
    $query  = null;
    if(isset($_GET["query"])) {
        $query = $r->GetRequest()->getGet("query");
        $q = array(
            "size"=>25,
            "from"=>0,
            "facets"=>new StdClass,
            "query"=> array(
                "constant_score"=> array(
                    "query"=> array(
                        "query_string"=> array(
                            "query"=> $query
                        )
                    )
                )
            )
        );
        $r  = json_decode(file_get_contents(ES."/_search?source=".rawurlencode(json_encode($q))))->hits->hits;
        foreach($r as $rr) {
            $result[] = $rr->_source;
        }
    }
    return new View("index.html",array("result"=>$result,"query"=>$query));
});

$rest->addMap("GET","/families",function($r) {
    $docs = Utils::$couchdb->getView("taxonomy","species_by_family",null,array("reduce"=>true,"group"=>true));
    $families = array();
    foreach($docs[ 'rows' ] as $r) {
        $families[] = array('family'=> $r['key'] ,'count'=>$r['value']);
    }
    return new View('families.html',array('families'=>$families));
});

$rest->addMap('POST',"/login",function($r) {
    $u = json_decode($r->getRequest()->getBody());
    $r->getRequest()->setSession('user',$u);
    return new Rest\View\JSon($u);
});

$rest->addMap('POST',"/logout",function($r) {
    $r->getRequest()->setSession('user',null);
    return new Rest\View\JSon(null);
});

$rest->addMap("GET","/family/:family",function($r) {
    $family = $r->getRequesT()->getParameter('family');
    $docs = Utils::$couchdb->getView("species_profiles","by_taxon_lsid");
    $profiles = array();
    foreach($docs['rows'] as $r) {
        $profiles[$r['key']] = true; 
    }

    $docs = Utils::$couchdb->getView("taxonomy","species_by_family",$family,array("reduce"=>false));
    $species = array();
    foreach($docs['rows'] as $r) {
        $s = $r['value'];
        if(isset( $profiles[$r['value']['_id']] )) {
            $s[ 'have' ]=true;
        }
        $species[] = $s;
    }
    return new View('family.html',array('species'=>$species,'family'=>$family));
});

$rest->addMap("GET","/specie/:id",function($r) {
    $id = $r->getRequest()->getParameter('id');
    $spp = Utils::$couchdb->get($id);
    $docs = Utils::$couchdb->asDocuments()->getView("species_profiles","by_taxon_lsid",$id);
    if(isset($docs[0])) {
        $doc = $docs[0];
        foreach($docs as $d) {
            if($d->metadata['modified'] > $doc->metadata['modified']) {
                $doc = $d;
            }
        }
    }
    if(!isset($doc)) {
        return new View('specie.html',array('specie'=>$spp));
    } else {
        return new Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
    }
});

$rest->addMap("POST","/profile",function($r) {
    $id = $r->GetRequest()->getPost("lsid");
    $spp = Utils::$couchdb->asDocuments()->get($id);
    $user = $r->getParameter("user");

    $taxon = new StdClass;
    $taxon->lsid   = $spp->_id;
    $taxon->fbid   = $spp->fbid."";
    $taxon->ipni   = $spp->ipni."";
    $taxon->family = $spp->family;
    $taxon->scientificName = $spp->scientificName;
    $taxon->scientificNameAuthorship = $spp->scientificNameAuthorship;

    $metadata = new StdClass;
    $metadata->status = "open";
    $metadata->contributor = $user->name;
    $metadata->contact = $user->email;
    $metadata->creator = $user->name;
    $metadata->created = time();
    $metadata->modified = time();
    $metadata->description = "Profile for ".$taxon->scientificName;
    $metadata->title  = "Profile for ".$taxon->scientificName;
    $metadata->source = "";
    $metadata->type = "profile";
    $metadata->valid = false;
    $metadata->identifier = "urn:lsid:cncflora.jbrj.gov.br:profile:".str_replace(' ',':',strtolower($taxon->scientificName)).":".time();

    $doc = new Chill\Document(Utils::$couchdb);
    $doc->taxon = $taxon;
    $doc->metadata = $metadata;
    $doc->_id = $metadata->identifier;
    $doc->save();

    return new Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id.'/edit');
});

$rest->addMap("GET","/profile/:id",function($r) {
    $id = $r->GetRequest()->getParameter("id");
    $profile = Utils::$couchdb->asDocuments()->get($id);
    $meta = $profile->metadata;
    $meta['modified_date'] = date('d-m-Y',$meta['modified']);
    $meta['created_date'] = date('d-m-Y',$meta['created']);
    $profile->metadata = $meta;
    $can_edit=($meta['status'] == 'open' || $meta['status'] == 'review');


    $synonyms = array();
    $docs = Utils::$couchdb->getView("taxonomy","synonyms",$profile->taxon[ 'lsid' ],array("reduce"=>false));
    foreach($docs as $row) {
        $synonyms[] = $row;
    }
    $profile->synonyms = $synonyms;

    return new View('profile.html',array('profile'=>$profile,'edit'=>$can_edit));
});

$rest->addMap("GET","/profile/:id/view",function($r) {
    $id = $r->GetRequest()->getParameter("id");
    return new Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id);
});

$rest->addMap("GET","/profile/:id/edit",function($r) {
    $id = $r->GetRequest()->getParameter("id");
    $profile = Utils::$couchdb->get($id);
    $user = $r->getParameter('user');

    $taxon = $profile['taxon'];
    $meta = $profile[ 'metadata' ];
    $meta['modified_date'] = date('d-m-Y',$meta['modified']);
    $meta['created_date'] = date('d-m-Y',$meta['created']);
    if(strpos($meta['contact'],$user->email) === false) {
        $meta['contributor'] = "[".$user->name.'] ; '.$meta['contributor'];
        $meta['contact'] = "[".$user->email.'] ;'.$meta['contact'];
    }
    unset($profile[ 'metadata' ]);
    unset($profile[ 'taxon' ]);

    $data = array (
        'profile'=>$profile,
        'data'=>json_encode($profile),
        'schema'=>json_encode(Utils::schema()),
        'metadata'=>$meta,
        'taxon'=>$taxon,
    );
    return new View('edit.html',$data);
});

$rest->addMap("POST","/profile/:id/validate",function($r) {
    $id = $r->getRequest()->getParameter("id");
    $user = $r->getParameter('user');
    $profile = Utils::$couchdb->asDocuments()->get($id);

    $validations = $profile->validations;
    $validations[] = array(
        'metadata'=> array(
            'creator'=> $user->name,
            'created'=> time(),
            'status'=>'open'
        ),
        'field'=>$r->getRequest()->getPost('field'),
        'comment'=>$r->getRequesT()->getPost('comment')
    );
    $profile->validations = $validations;

    $profile->save();

    return new Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."/validate");
});

$rest->addMap("GET","/profile/:id/validate",function($r) {
    $id = $r->GetRequest()->getParameter("id");
    $profile = Utils::$couchdb->asDocuments()->get($id);
    $user = $r->getParameter('user');

    $taxon = $profile->taxon;
    $validations = $profile->validations;
    if(is_array($validations)) {
        foreach($validations as  $k=>$v) {
            $v['metadata']['created_date'] = date('d-m-Y',$v['metadata']['created']);
            $validations[$k] = $v;
        }
    }
    $meta = $profile->metadata;
    $meta['modified_date'] = date('d-m-Y',$meta['modified']);
    $meta['created_date'] = date('d-m-Y',$meta['created']);
    unset($profile->metadata);
    unset($profile->taxon);
    unset($profile->validations);

    $schema = Utils::schema();

    $data = array (
        'profile'=>$profile,
        'metadata'=>$meta,
        'taxon'=>$taxon,
        'schema'=>json_encode($schema),
        'validations'=>$validations
    );

    return new View('validate.html',$data);
});

$rest->addMap("GET","/profile/:id/review",function($r) {
    $id = $r->GetRequest()->getParameter("id");
    $profile = Utils::$couchdb->asDocuments()->get($id);
    $user = $r->getParameter('user');

    $taxon = $profile->taxon;
    $validations = $profile->validations;
    $meta = $profile->metadata;
    $meta['modified_date'] = date('d-m-Y',$meta['modified']);
    $meta['created_date'] = date('d-m-Y',$meta['created']);
    unset($profile->metadata);
    unset($profile->taxon);
    unset($profile->validations);

    if(is_array($validations)) {
        foreach($validations as  $k=>$v) {
            $v['metadata']['created_date'] = date('d-m-Y',$v['metadata']['created']);
            $validations[$k] = $v;
        }
        foreach($validations as $k=>$v) {
            if($v['metadata']['status'] == 'open') {
                $v[ 'open' ] = true;
                $validations[$k] = $v;
            }
        }
    }

    $schema = Utils::schema();

    $data = array (
        'profile'=>$profile,
        'data'=>json_encode($profile),
        'schema'=>json_encode($schema),
        'metadata'=>$meta,
        'taxon'=>$taxon,
        'validations'=>$validations
    );

    return new View('review.html',$data);
});

$rest->addMap("POST","/profile/:id/validate/:created/done",function($r) {
    $id = $r->getRequest()->getParameter("id");
    $created = $r->getRequest()->getParameter("created");
    $profile = Utils::$couchdb->asDocuments()->get($id);

    $validations = $profile->validations;
    foreach( $validations as $k=>$v ) {
        if($v['metadata']['created'] == $created) {
            $v['metadata']['status'] = 'done';
            $validations[$k] = $v;
        }
    }
    $profile->validations = $validations;

    $profile->save();

    return new Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."/review");
});

$rest->addMap("POST","/profile/:id/send/:status",function($r){
    $id = $r->GetRequest()->getparameter("id");
    $status = $r->GetRequest()->getparameter("status");
    $doc = Utils::$couchdb->asDocuments()->get($id);
    $meta = $doc->metadata;
    $meta['status']= $status;
    $meta['modified'] = time();
    if($status == 'done') $meta['valid'] = true;
    $doc->metadata = $meta;
    $doc->save();
    return new Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
});

$rest->addMap("POST","/profile/:id",function($r){
    $id = $r->GetRequest()->getparameter("id");
    $doc = Utils::$couchdb->asDocuments()->get($id);
    $user = $r->getParameter("user");
    $data  = $r->getRequest()->getBody();
    $obj = json_decode($data);

    $metadata = $doc->metadata;
    if(strpos($metadata['contact'],$user->email) === false) {
        $metadata['contributor'] = $user->name." ; ".$metadata['contributor'];
        $metadata['contact'] = $user->email." ; ".$metadata['contact'];
    }
    $metadata['modified'] = time();
    $doc->metadata = $metadata;

    foreach($obj as $k=>$v) {
        $doc->$k = $v;
    }

    $doc->save();

    $obj->metadata = $metadata;
    $obj->taxon = $doc->taxon;
    return new Rest\View\JSon($obj);

    //return new Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
});

$rest->addMap("GET","/work",function($r) {
    $docs = Utils::$couchdb->getView("taxonomy","species_by_family",null,array("reduce"=>true,"group"=>true));
    $families = array();
    foreach($docs[ 'rows' ] as $r) {
        $families[] = array('family'=> $r['key'] ,'count'=>$r['value']);
    }
    $data = array(
        'families'=>$families
    );
    return new View('work.html',$data);
});

$rest->addMap("GET","/biblio",function($r) {
    $term=$r->getRequest()->getGet("term");
    $start = rawurlencode($term);
    $end = rawurlencode($term."99999999999999999");
    $docs = Utils::$couchdb->getView("bibliography","short_citation",null,array('startkey'=>$start,'endkey'=>$end));
    $r = array();
    foreach($docs['rows'] as $row) {
        $r[] = array("label"=>$row['value']['fullCitation'],'value'=>$row['value']['_id']);
    }
    return new \Rest\View\JSon($r);
});


$rest->addMap("GET",'.*',function($r) {
    $uri = $r->getRequest()->getURI();
    if(strpos($uri,'resources') === false) return new Rest\Controller\NotFound;
    $file = substr($uri,strpos($uri,'resources'));
    return new Rest\Controller\Redirect("/".BASE_PATH.$file);
});

$rest->execute();

