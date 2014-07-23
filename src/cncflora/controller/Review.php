<?php

namespace cncflora\controller ;

use cncflora\Utils;
use cncflora\View;

class Review implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $id = $r->GetRequest()->getParameter("id");
        $repo = new \cncflora\repository\Profiles;
        $profile = $repo->get($id);
        $user = $r->getParameter('user');

        $taxon = $profile->taxon;
        $validations = $profile->validations;
        $profile->metadata->modified_date = date('d-m-Y',$profile->metadata->modified);
        $profile->metadata->created_date = date('d-m-Y',$profile->metadata->created);

        if(is_array($validations)) {
            foreach($validations as  $k=>$v) {
                $v->metadata->created_date = date('d-m-Y',$v->metadata->created);
                $validations[$k] = $v;
            }
            foreach($validations as $k=>$v) {
                if($v->metadata->status == 'open') {
                    $v->open = true;
                    $validations[$k] = $v;
                }
            }
        }

        $schema = Utils::schema();

        $data = array (
            'profile'=>$profile,
            'data'=>json_encode($profile),
            'schema'=>json_encode($schema),
            'metadata'=>$profile->metadata,
            'taxon'=>$taxon,
            'validations'=>$validations
        );

        return new View('review.html',$data);
    }

    public function markDone($r) {
        $id = $r->getRequest()->getParameter("id");
        $created = $r->getRequest()->getParameter("created");
        $user = $r->getParameter("user");
        $repo  = new \cncflora\repository\Profiles($user);
        $profile = $repo->get($id);

        $validations = $profile->validations;
        foreach( $validations as $k=>$v ) {
            if($v->metadata->created == $created) {
                $v->metadata->status = 'done';
                $validations[$k] = $v;
            }
        }
        $profile->validations = $validations;

        $r = $repo->update($profile);
        if(isset($r->error)) {
            $j = json_decode(substr($r->reason,strpos( $r->reason,":" ) + 1));
            $err = "Error: ".$j->message." at ".substr($j->dataPath,1);
            echo $err;
            exit;
        }

        return new \Rest\Controller\Redirect(BASE."/profile/".$id."/review");
    }
}

