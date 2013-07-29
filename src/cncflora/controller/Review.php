<?php

namespace cncflora\controller ;

use cncflora\Utils;
use cncflora\View;

class Review implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $id = $r->GetRequest()->getParameter("id");
        $profile = Utils::$couchdb->asDocuments()->get($id);
        $user = $r->getParameter('user');

        $taxon = $profile->taxon;
        $validations = $profile->validations;
        $meta = $profile->metadata;
        $meta['modified_date'] = date('d-m-Y',$meta['modified']);
        $meta['created_date'] = date('d-m-Y',$meta['created']);
        unset($profile->metadata);
        unset($profile->taxon);
        unset($profile->validations);

        if(is_array($validations)) {
            foreach($validations as  $k=>$v) {
                $v['metadata']['created_date'] = date('d-m-Y',$v['metadata']['created']);
                $validations[$k] = $v;
            }
            foreach($validations as $k=>$v) {
                if($v['metadata']['status'] == 'open') {
                    $v[ 'open' ] = true;
                    $validations[$k] = $v;
                }
            }
        }

        $schema = Utils::schema();

        $data = array (
            'profile'=>$profile,
            'data'=>json_encode($profile),
            'schema'=>json_encode($schema),
            'metadata'=>$meta,
            'taxon'=>$taxon,
            'validations'=>$validations
        );

        return new View('review.html',$data);
    }

    public function markDone($r) {
        $id = $r->getRequest()->getParameter("id");
        $created = $r->getRequest()->getParameter("created");
        $profile = Utils::$couchdb->asDocuments()->get($id);

        $validations = $profile->validations;
        foreach( $validations as $k=>$v ) {
            if($v['metadata']['created'] == $created) {
                $v['metadata']['status'] = 'done';
                $validations[$k] = $v;
            }
        }
        $profile->validations = $validations;

        $profile->save();

        return new Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."/review");
    }
}

