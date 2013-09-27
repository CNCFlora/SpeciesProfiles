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
                    $spps[] = $doc;
                    break;
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
        $repo->update($doc);
        return new \Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
    }
}

