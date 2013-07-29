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
        $profile = Utils::$couchdb->asDocuments()->get($id);

        $validations = $profile->validations;
        $validations[] = array(
            'metadata'=> array(
                'creator'=> $user->name,
                'created'=> time(),
                'status'=>'open'
            ),
            'field'=>$r->getRequest()->getPost('field'),
            'comment'=>$r->getRequesT()->getPost('comment')
        );
        $profile->validations = $validations;
        $profile->save();

        return new Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."/validate");
    }

    function validateForm($r) {
        $id = $r->GetRequest()->getParameter("id");
        $profile = Utils::$couchdb->asDocuments()->get($id);
        $user = $r->getParameter('user');

        $taxon = $profile->taxon;
        $validations = $profile->validations;
        if(is_array($validations)) {
            foreach($validations as  $k=>$v) {
                $v['metadata']['created_date'] = date('d-m-Y',$v['metadata']['created']);
                $validations[$k] = $v;
            }
        }
        $meta = $profile->metadata;
        $meta['modified_date'] = date('d-m-Y',$meta['modified']);
        $meta['created_date'] = date('d-m-Y',$meta['created']);
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

