<?php

namespace cncflora\controller;

use cncflora\View;
use cncflora\Utils;

class Workflow implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $access = array();
        $user = $r->getParameter("user");
        foreach($user->roles as $role) {
            if($role->role == 'Analyst') {
                foreach($role->entities as $ent) {
                    $access[] = $ent->value;
                }
            }
        }

        $docs = Utils::$couchdb->getView("taxonomy","species_by_family",null,array("reduce"=>true,"group"=>true));
        $families = array();
        foreach($docs[ 'rows' ] as $r) {
            $f = 'taxon:'.$r['key'].'';
            foreach($access as $a) {
                if(strpos($a,strtolower($f)) !== false){
                    $families[] = array('family'=> $r['key'] ,'count'=>$r['value']);
                    break;
                }
            }
        }

        $data = array(
            'families'=>$families
        );

        return new View('work.html',$data);
    }

    function family($r) {
        $family = $r->getRequest()->getParameter('family');
        $status = $r->getRequest()->getParameter('status');

        if($status != "empty") {
            $key = rawurlencode( json_encode( array($family,$status) ) );
            $profiles = json_decode(file_get_contents(Utils::$couch."/_design/species_profiles/_view/by_family_and_status?key=".$key."&reduce=false"))->rows;
            $docs = array();
            foreach($profiles as $p ) {
                $docs[] = $p->value;
            }
        } else {
            $key = json_encode($family);
            $taxons = json_decode(file_get_contents(Utils::$couch."/_design/taxonomy/_view/species_by_family?key=".$key."&reduce=false"))->rows;
            $profiles = json_decode(file_get_contents(Utils::$couch."/_design/species_profiles/_view/by_family?key=".$key."&reduce=false"))->rows;
            $docs= array();
            foreach($taxons as $t) {
                $have = false;
                foreach($profiles as $p) {
                    if($p->value->taxon->lsid == $t->value->_id) {
                        $have = true;
                        break;
                    }
                }
                if(!$have) {
                    $docs[] = $t->value;
                }
            }

        }

        $access = array();
        $user = $r->getParameter("user");
        foreach($user->roles as $role) {
            if($role->role == 'Analyst') {
                foreach($role->entities as $ent) {
                    $access[] = $ent->value;
                }
            }
        }

        $spps = array();
        foreach($docs as $doc) {
            foreach($access as $a) {
                if(isset($doc->taxon)) {
                    if(strpos($doc->taxon->lsid,$a) !== false) {
                        $spps[] = $doc;
                    }
                } else {
                    if(strpos($doc->_id,$a) !== false) {
                        $spps[] = $doc;
                    }
                }
            }
        }

        return new \Rest\View\JSon($spps);
    }

    function changeStatus($r) {
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
    }
}

