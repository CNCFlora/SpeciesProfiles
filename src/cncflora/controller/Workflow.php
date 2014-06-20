<?php

namespace cncflora\controller;

use cncflora\View;
use cncflora\Utils;

class Workflow implements \Rest\Controller {

    public function getAccess($user) {
        $access = array();
        foreach($user->roles as $role) {
            if($role->role == 'Analyst' || $role->role == 'Validator') {
                foreach($role->entities as $ent) {
                    $access[] = $ent->value;
                }
            }
        }
        return $access;
    }

    public function execute(\Rest\Server $r) {
        $access = $this->getAccess($r->getParameter("user"));

        $repo = new \cncflora\repository\Species;
        $docs = $repo->getFamilies();
        $families = array();
        foreach($docs as $f) {
            foreach($access as $a) {
                if(preg_match('/:'.$f.'/i',$a)) {
                    $families[] = $f;
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
        $access = $this->getAccess($r->getParameter("user"));

        if($status != "empty") {
            $key = rawurlencode(json_encode(array($family,$status)));
            $profiles = json_decode(file_get_contents(Utils::$couchdb."/_design/species_profiles/_view/by_family_and_status?key=".$key."&reduce=false"))->rows;
            $docs = array();
            foreach($profiles as $p) {
                $docs[] = $p->value;
            }
        } else {
            $key = json_encode($family);
            $taxons = json_decode(file_get_contents(Utils::$couchdb."/_design/taxonomy/_view/species_by_family?key=".$key."&reduce=false"))->rows;
            $profiles = json_decode(file_get_contents(Utils::$couchdb."/_design/species_profiles/_view/by_family?key=".$key."&reduce=false"))->rows;
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

        $spps = array();
        foreach($docs as $doc) {
            foreach($access as $a) {
                if(preg_match('/'.$doc->taxon->family.'/i',$a)) {
                    if(\cncflora\Utils::taxonOk($doc->taxon->family." ".$doc->taxon->scientificName)) {
                        $spps[] = $doc;
                        break;
                    }
                }
            }
        }

        return new \Rest\View\JSon($spps);
    }

    function changeStatus($r) {
        $id = $r->GetRequest()->getparameter("id");
        $status = $r->GetRequest()->getparameter("status");
        $user = $r->getParameter("user");
        $repo = new \cncflora\repository\Profiles($user);
        $doc = $repo->get($id);
        $doc->metadata->status= $status;
        if($status == 'done') $doc->metadata->valid = true;
        $r = $repo->update($doc);
        if(isset($r->error)) {
            $j = json_decode(substr($r->reason,strpos( $r->reason,":" ) + 1));
            $err = "Error: ".$j->message." at ".substr($j->dataPath,1);
            echo $err;
            exit;
        }
        return new \Rest\Controller\Redirect('/'.BASE.'profile/'.$doc->_id);
    }

    function changeStatusForce($r) {
        $id = $r->GetRequest()->getparameter("id");
        $status = $r->getRequest()->getPost("status");
        $user = $r->getParameter("user");
        $repo = new \cncflora\repository\Profiles($user);
        $doc = $repo->get($id);
        $doc->metadata->status= $status;
        if($status == 'done') $doc->metadata->valid = true;
        $r = $repo->update($doc,false);
        if(isset($r->error)) {
            $j = json_decode(substr($r->reason,strpos( $r->reason,":" ) + 1));
            $err = "Error: ".$j->message." at ".substr($j->dataPath,1);
            echo $err;
            exit;
        }
        return new \Rest\Controller\Redirect('/'.BASE.'profile/'.$doc->_id);
    }

    public function control(\Rest\Server $r) {

        $data = array('empty'=>array(),'open'=>array(),'sig'=>array(),'validation'=>array(),'review'=>array(),'review-sig'=>array(),'done'=>array());

        $couchdb = new \Nano\Nano('http://'.COUCH_USER.":".COUCH_PASS."@".COUCH_HOST.":".COUCH_PORT);
        $db = $couchdb->db->use(COUCH_BASE);

        $docs = $db->view('species_profiles','by_family_and_status',array("reduce"=>false));
        $profiles = array();
        foreach($docs->rows as $doc) {
            $profiles[$doc->value->taxon->lsid] = $doc->value->metadata->status;
        }

        $sppRepo = new \cncflora\repository\Species;
        $families = $sppRepo->getFamilies();

        foreach($families as $family) {
            $species = $sppRepo->getSpecies($family);
            foreach($species as $spp) {
                $notOpen = !isset($profiles[$spp->_id]);
                if($notOpen) {
                    $status = 'empty';
                } else {
                    $status = $profiles[$spp->_id];
                }
                $found = false;
                foreach($data[$status] as $k=>$list) {
                    if($list['family'] == $spp->family) {
                        $found = true;
                        $data[$status][$k]['count']++;
                        $data[$status][$k]['species'][] = array(
                            'taxon'=>array( 'lsid'=>$spp->_id,'scientificName'=>$spp->scientificName)
                        );
                    }
                }
                if(!$found) {
                    $data[$status][] = array(
                        'family'=>$family,
                        'total'=>count($species),
                        'count'=>1,
                        'species'=> array( 
                            array('taxon'=>array('lsid'=>$spp->_id,'scientificName'=>$spp->scientificName))
                        )
                    );
                }
            }
        }

        $statuses  =array();
        foreach($data as $status=>$list) {
            $statuses[] = array('status'=>$status,'list'=>$list);
        }

        return new View('control.html',array('statuses'=> $statuses ));
    }

}

