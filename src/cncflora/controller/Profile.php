<?php

namespace cncflora\controller;

use cncflora\Utils;
use cncflora\View;

function r2($obj) {
    return $obj;
}

class Profile implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $id = $r->getRequest()->getParameter("id");
        $repo = new \cncflora\repository\Profiles;

        $profile = $repo->get($id);
        $profile->metadata->modified_date = date('d-m-Y',$profile->metadata->modified);
        $profile->metadata->created_date = date('d-m-Y',$profile->metadata->created);
        $meta = $profile->metadata;

        $can_edit = false;
        $can_validate = false;
        if($r->getParameter("logged")) {
            $user = $r->getParameter("user");
            foreach($user->roles as $role) {
                if($role->role == "Analyst") {
                    foreach($role->entities as $ent) {
                        if(strpos($ent->name,$profile->taxon->family) !== false) {
                            $can_edit = true;
                            break;
                        }
                        if(strpos($ent->name,$profile->taxon->scientificName) !== false) {
                            $can_edit = true;
                            break;
                        }
                    }
                }
            }
            $user = $r->getParameter("user");
            foreach($user->roles as $role) {
                if($role->role == "Validator") {
                    foreach($role->entities as $ent) {
                        if(strpos($profile->taxon->family,$ent->name) !== false) {
                            $can_validate = true;
                            break;
                        }
                        if(strpos($profile->taxon->scientificName,$ent->name) !== false) {
                            $can_validate = true;
                            break;
                        }
                    }
                }
            }
        }

        $r2 = new \cncflora\repository\Species;
        $profile->synonyms = $r2->getSynonyms($profile->taxon->lsid);

        $repoOcc = new \cncflora\repository\Occurrences();
        $occs = $repoOcc->listByName($profile->taxon->scientificName);
        $profile->occsDone = 0;
        $profile->occsTotal = count($occs);
        foreach($occs as $occ) {
            if(isset($occ->validationBy) && $occ->validationBy != null){
                $profile->occsDone++;
            }
        }
        $profile->ocssMissing = $profile->ocssTodo >= 1;

        if(!isset($profile->distribution)) $profile->distribution = new \StdClass;
        $profile->distribution->eoo = $repoOcc->eoo($profile->taxon->scientificName);
        $profile->distribution->aoo = $repoOcc->aoo($profile->taxon->scientificName);

        $s = "status_".$profile->metadata->status;
        $profile->$s = true;

        if(isset($profile->distribution) && isset($profile->distribution->brasilianEndemic)) {
            $profile->distribution->brasilianEndemic = ($profile->distribution->brasilianEndemic === "yes");
        }
        if(isset($profile->economicValue) && isset($profile->economicValue->potentialEconomicValue)) {
            $profile->economicValue->potentialEconomicValue = ($profile->economicValue->potentialEconomicValue === "yes");
        }

        $eoo = $repoOcc->eooPolygon($profile->taxon->scientificName);

        return new View('profile.html',array('profile'=>$profile,'edit'=>$can_edit,'occurrences'=>$occs,$s=>true,'eooPolygon'=> $eoo,'can_edit'=>$can_edit,'can_validate'=>$can_validate));
    }

    public function occs(\Rest\Server $r) {
        $id = $r->getRequest()->getParameter("id");
        $repo = new \cncflora\repository\Profiles;

        $profile = $repo->get($id);
        $profile->metadata->modified_date = date('d-m-Y',$profile->metadata->modified);
        $profile->metadata->created_date = date('d-m-Y',$profile->metadata->created);
        $meta = $profile->metadata;

        $r2 = new \cncflora\repository\Species;
        $profile->synonyms = $r2->getSynonyms($profile->taxon->lsid);

        $repoOcc = new \cncflora\repository\Occurrences();
        $occs = $repoOcc->listByName($profile->taxon->scientificName);

        $s = "status_".$profile->metadata->status;
        $profile->$s = true;

        /*
        $profile->distribution->brasilianEndemic = ($profile->distribution->brasilianEndemic === "yes");
        $profile->economicValue->potentialEconomicValue = ($profile->economicValue->potentialEconomicValue === "yes");
        */

        return new View('occs.html',array('profile'=>$profile,'occurrences'=>$occs,$s=>true));
    }

    function view($r) {
        $id = $r->getRequest()->getParameter("id");
        return new \Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id);
    }

    function createProfile($r) {
        $id = $r->getRequest()->getPost("lsid");
        $spp = r2(new \cncflora\repository\Species)->getSpecie($id);
        $user = $r->getParameter("user");
        $repo  = new \cncflora\repository\Profiles($user);
        $profile = $repo->create($spp);
        return new \Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$profile->_id.'/edit');
    }

    function edit($r) {
        $id = $r->getRequest()->getParameter("id");
        $user = $r->getParameter('user');
        $profile = r2(new \cncflora\repository\Profiles)->get($id);

        $taxon = $profile->taxon;
        $meta = $profile->metadata;
        $meta->modified_date = date('d-m-Y',$meta->modified);
        $meta->created_date = date('d-m-Y',$meta->created);
        if(strpos($meta->contact,$user->email) === false) {
            $meta->contributor = "[".$user->name."] ; ".$meta->contributor;
            $meta->contact = "[".$user->email."] ; ".$meta->contact;
        }
        unset($profile->metadata);
        unset($profile->taxon);

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
        $id  = $r->GetRequest()->getparameter("id");
        $user = $r->getParameter("user");
        $repo  = new \cncflora\repository\Profiles($user);
        $doc   = $repo->get($id);
        $data  = json_decode($r->getRequest()->getBody());
        $data->_id = $doc->_id;
        $data->_rev = $doc->_rev;
        $data->metadata = $doc->metadata;
        $data->taxon = $doc->taxon;
        if(!isset($doc->validations)) $doc->validations=array();
        $data->validations = $doc->validations;
        $r = $repo->update($data);
        return new \Rest\View\JSon($r);
    }

    function habitats2fito($r) {
        $habitats = json_decode($r->getRequest()->getGet("q"));
        $f = fopen(__DIR__."/../../../resources/dicts/habitats2fito.csv",'r');
        $res = array() ;
        while($row = fgetcsv($f,0,',','')) {
            foreach($habitats as $habitat) {
                if(strpos($habitat,$row[0]) !== false)
                    foreach(explode(";",$row[1]) as $fito)  {
                        $res[] = trim($fito);
                    }
                }
            }
        }
        fclose($f);
        $res = array_unique($res);
        sort($res);
        $res = implode(" ; ",$res)
        return new \Rest\View\JSon($res);
    }

}

