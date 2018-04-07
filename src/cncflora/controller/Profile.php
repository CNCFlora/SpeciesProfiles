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
            $roles = $r->getParameter("roles");

            foreach($roles as $role) {
                if(strtolower( $role->role ) == "analyst") {
                    foreach($role->entities as $ent) {
                        if(trim(strtolower($ent)) == 'all') {
                            $can_edit = true;
                            break;
                        }
                        if(strpos(strtolower(trim( $ent )),strtolower( trim( $profile->taxon->family ) )) !== false) {
                            $can_edit = true;
                            break;
                        }
                        if(strpos(strtolower( trim( $ent ) ),strtolower( trim( $profile->taxon->scientificNameWithoutAuthorship ) )) !== false) {
                            $can_edit = true;
                            break;
                        }
                    }
                }
            }

            foreach($roles as $role) {
                if(strtolower( $role->role ) == "validator") {
                    foreach($role->entities as $ent) {
                        if(trim(strtolower($ent)) == 'all') {
                            $can_edit = true;
                            break;
                        }
                        if(strpos(strtolower( trim( $profile->taxon->family ) ),strtolower( trim( $ent ) )) !== false) {
                            $can_validate = true;
                            break;
                        }
                        if(strpos(strtolower( trim( $profile->taxon->scientificNameWithoutAuthorship ) ),strtolower( trim( $ent ) )) !== false) {
                            $can_validate = true;
                            break;
                        }
                    }
                }
            }
        }

        $r2 = new \cncflora\repository\Species;
        $profile->synonyms = $r2->getSynonyms($profile->taxon->scientificNameWithoutAuthorship);

        $currentTaxon = $r2->getCurrentTaxon( $profile->taxon->scientificNameWithoutAuthorship );
        // $taxonomia_diferente = ($currentTaxon->scientificNameWithoutAuthorship != $profile->taxon->scientificNameWithoutAuthorship);
        // $taxonomia_diferente_scientificNameWithoutAuthorship = $currentTaxon->scientificNameWithoutAuthorship;
        // $taxonomia_diferente_scientificNameAuthorship = $currentTaxon->scientificNameAuthorship;
        $s = "status_".$profile->metadata->status;
        $profile->$s = true;

        if(isset($profile->distribution) && isset($profile->distribution->brasilianEndemic)) {
            $profile->distribution->brasilianEndemic = ($profile->distribution->brasilianEndemic === "yes");
        }
        if(isset($profile->economicValue) && isset($profile->economicValue->potentialEconomicValue)) {
            $profile->economicValue->potentialEconomicValue = ($profile->economicValue->potentialEconomicValue === "yes");
        }

        $others = $repo->getAllOthers($profile->taxon->scientificNameWithoutAuthorship);

        $endemic = \cncflora\Utils::http_get(SERVICOS.rawurlencode($profile->taxon->scientificNameWithoutAuthorship));
        if (isset($endemic))
          $endemic = $endemic->result;

        //error_log(print_r($endemic[0]->{"endemism"}, TRUE));
        if(!isset($endemic[0]) || $endemic[0]->{"endemism"} != "Endemic")
          $endemic = null;

        if(isset($_GET['txt'])) {
          if($_GET['txt']=='1') {
            header('Content-Disposition: attachment; filename="'.$profile->taxon->family.' '.$profile->taxon->scientificNameWithoutAuthorship.'.txt"');
            header('Content-type: plain/text');
            return new View('txt.html',array('profile'=>$profile));
          } elseif($_GET['txt']=='2') {
            return new View('txt2.html',array('profile'=>$profile));
          }
        } else {
          return new View('profile.html',array('profile'=>$profile,'edit'=>$can_edit,$s=>true,
          'can_edit'=>$can_edit,'can_validate'=>$can_validate,'others'=>$others,
          'currentTaxon'=>$currentTaxon, 'endemic'=>$endemic));
          // return new View('profile.html',array('profile'=>$profile,'edit'=>$can_edit,$s=>true,'can_edit'=>$can_edit,'can_validate'=>$can_validate,'others'=>$others,
          //                 'currentTaxon'=>$currentTaxon, 'taxonomia_diferente'=>$taxonomia_diferente,
          //                 'taxonomia_diferente_scientificNameWithoutAuthorship'=>$taxonomia_diferente_scientificNameWithoutAuthorship,
          //                 'taxonomia_diferente_scientificNameAuthorship'=>$taxonomia_diferente_scientificNameAuthorship));
        }
    }


    function view($r) {
        $id = $r->getRequest()->getParameter("id");
        return new \Rest\Controller\Redirect(BASE."/".DB."/profile/".$id);
    }

    function createProfile($r) {
        $id = $r->getRequest()->getPost("id");
        $spp = r2(new \cncflora\repository\Species)->getSpecie($id);
        $user = $r->getParameter("user");
        $repo  = new \cncflora\repository\Profiles($user);
        $profile = $repo->create($spp);
        return new \Rest\Controller\Redirect(BASE."/".DB.'/profile/'.$profile->_id.'/edit');
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
        $habitats = json_decode($r->getRequest()->getGet("habitats"));
        $f = fopen(__DIR__."/../../../resources/dicts/habitats2fito.csv",'r');
        $res = array() ;
        while($row = fgetcsv($f)) {
            foreach($habitats as $habitat) {
                if(strpos($habitat,$row[0]) !== false) {
                    foreach(explode(";",$row[1]) as $fito)  {
                        $res[] = trim($fito);
                    }
                }
            }
        }
        fclose($f);
        $res = array_unique($res);
        sort($res);
        $res = implode(" ; ",$res);
        return new \Rest\View\JSon($res);
    }

    function sig($r) {
        $id = $r->getRequest()->getParameter("id");
        $repo = new \cncflora\repository\Profiles;

        $profile = $repo->get($id);
        $profile->metadata->modified_date = date('d-m-Y',$profile->metadata->modified);
        $profile->metadata->created_date = date('d-m-Y',$profile->metadata->created);
        $meta = $profile->metadata;

        $r2 = new \cncflora\repository\Species;
        $profile->synonyms = $r2->getSynonyms($profile->taxon->scientificNameWithoutAuthorship);

        $s = "status_".$profile->metadata->status;
        $profile->$s = true;

        return new View('sig.html',array('profile'=>$profile));
    }

}
