<?php

namespace cncflora\controller;

use cncflora\Utils;
use cncflora\View;

class Profile implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $id = $r->getRequest()->getParameter("id");
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
    }

    function view($r) {
        $id = $r->getRequest()->getParameter("id");
        return new \Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id);
    }

    function createProfile($r) {
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

        $doc = new \Chill\Document(Utils::$couchdb);
        $doc->taxon = $taxon;
        $doc->metadata = $metadata;
        $doc->_id = $metadata->identifier;
        $doc->save();

        return new \Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id.'/edit');
    }

    function edit($r) {
        $id = $r->getRequest()->getParameter("id");
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
    }

    function save($r) {
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
    }

}

