<?php

namespace cncflora\controller;

use cncflora\Utils;
use cncflora\View ;

class Validation implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
    }

    function validate($r) {
        $id = $r->getRequest()->getParameter("id");
        $user = $r->getParameter('user');
        $repo = new \cncflora\repository\Profiles($user);
        $profile = $repo->get($id);

        $v = new \StdClass;
        $v->metadata = new \StdClass;
        $v->metadata->creator = $user->name;
        $v->metadata->contact = $user->email;
        $v->metadata->created = time();
        $v->metadata->status  = 'open';
        $v->field = $r->getRequest()->getPost('field');
        $v->comment = $r->getRequest()->getPost('comment');
        $profile->validations[] = $v;

        $r = $repo->update($profile);
        if(isset($r->error)) {
            $j = json_decode(substr($r->reason,strpos( $r->reason,":" ) + 1));
            $err = "Error: ".$j->message." at ".substr($j->dataPath,1);
            echo $err;
            echo "<hr>";
            echo $v->field." -- ";
            echo $v->comment;
            exit;
        }

        return new \Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."/validate");
    }

    function validateForm($r) {
        $id = $r->getRequest()->getParameter("id");
        $user = $r->getParameter('user');
        $repo = new \cncflora\repository\Profiles($user);
        $profile = $repo->get($id);

        $taxon = $profile->taxon;
        if(!isset($profile->validations)) {
            $profile->validations = array();
        }
        $validations = $profile->validations;
        if(is_array($validations)) {
            foreach($validations as  $k=>$v) {
                $v->metadata->created_date = date('d-m-Y',$v->metadata->created);
                $validations[$k] = $v;
            }
        }
        $meta = $profile->metadata;
        $meta->modified_date = date('d-m-Y',$meta->modified);
        $meta->created_date = date('d-m-Y',$meta->created);
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
    }
}

