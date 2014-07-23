<?php

namespace cncflora\controller;

use cncflora\View;
use cncflora\Utils;

class Workflow implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $user =$r->getParameter("user");
        if($user==null) {
            return new View('index.html',array());
        }

        $repo = new \cncflora\repository\Species;
        $docs = $repo->getFamilies();
        $families = array();
        foreach($docs as $f) {
            foreach($user->roles as $role) {
                if(strtolower($role->role) == 'admin') {
                    $families[] = $f;
                    break;
                } else {
                    foreach($role->entities as $ent) {
                        if(strtolower($f) == strtolower($ent)) {
                            $families[] = $f;
                            break;
                        }
                    }
                }
            }
        }

        $statuses=['empty','open','sig','validation','review','review-sig','done'];

        $data = array();
        foreach($families as $f) {
            $spps = $repo->getSpecies($f);

            $s = array(
                "family"=>$f,
                "count"=>count($spps)
            );

            foreach($statuses as $status) {
                $s[$status] = 0;
            }

            $repoProfiles = new \cncflora\repository\Profiles;
            $docs = $repoProfiles->listByFamily($f);

            foreach($docs as $doc) {
                $s[$doc->metadata->status] += 1;
            }

            $data[] = $s;
        }

        return new View('workflow.html',array('families'=>$data));
    }

    function family($r) {
        $user =$r->getParameter("user");
        if($user==null) {
            return new View('index.html',array());
        }

        $family = $r->getRequest()->getParameter('family');

        $statuses=['empty','open','sig','validation','review','review-sig','done'];
        $data=[];

        $repoProfiles = new \cncflora\repository\Profiles;
        $docs = $repoProfiles->listByFamily($family);

        foreach($statuses as $status) {
            $data[$status]=[];
        }

        $got =array();
        foreach($docs as $p) {
            $got[$p->taxon->scientificName] = true; 
            $data[$p->metadata->status][] = $p;
        }

        $repo = new \cncflora\repository\Species;
        $species = $repo->getSpecies($family);
        foreach($species as $spp) {
            if(!isset($got[$spp->scientificName])) {
                $data['empty'][] = ['taxon'=>$spp];
            }
        }

        $final=[];
        foreach($data as $k=>$v) {
            $final[]=['status'=>$k,'species'=>$v];
        }

        $stats = [];
        foreach($statuses as $status) {
            $stats[$status] = count($data[$status]);
        }

        $stats['total']=count($species);

        return new View('workflow-in.html',array('data'=> $final,'family'=>$family,'stats'=>$stats));
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
        return new \Rest\Controller\Redirect(BASE.'/profile/'.$doc->_id);
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
        return new \Rest\Controller\Redirect(BASE.'/profile/'.$doc->_id);
    }

}

