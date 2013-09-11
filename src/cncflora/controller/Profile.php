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

        $can_edit=(($meta->status == 'open' || $meta->status == 'review') && $r->getParameter("logged")) ;
        if($can_edit) {
            $user = $r->getParameter("user");
            $can_edit = false;
            foreach($user->roles as $role) {
                if($role->role == "Analyst") {
                    foreach($role->entities as $ent) {
                        if(strpos($profile->taxon->lsid,$ent->value) !== false) {
                            $can_edit = true;
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

        return new View('profile.html',array('profile'=>$profile,'edit'=>$can_edit,'occurrences'=>$occs));
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
        $repo->update($data);
        return new \Rest\View\JSon($data);
    }

}

