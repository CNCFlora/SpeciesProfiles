<?php

namespace cncflora\controller;

use cncflora\Utils;
use cncflora\View;

class Species implements \Rest\Controller {

    public function execute(\Rest\Server $r) {
        $docs = Utils::$couchdb->getView("taxonomy","species_by_family",null,array("reduce"=>true,"group"=>true));
        $families = array();
        foreach($docs[ 'rows' ] as $r) {
            $families[] = array('family'=> $r['key'] ,'count'=>$r['value']);
        }
        return new View('families.html',array('families'=>$families));
    }

    function family($r) {
        $family = $r->getRequest()->getParameter('family');
        $docs = Utils::$couchdb->getView("species_profiles","by_taxon_lsid");
        $profiles = array();
        foreach($docs['rows'] as $r) {
            $profiles[$r['key']] = true; 
        }

        $docs = Utils::$couchdb->getView("taxonomy","species_by_family",$family,array("reduce"=>false));
        $species = array();
        foreach($docs['rows'] as $r) {
            $s = $r['value'];
            if(isset( $profiles[$r['value']['_id']] )) {
                $s[ 'have' ]=true;
            }
            $species[] = $s;
        }
        return new View('family.html',array('species'=>$species,'family'=>$family));
    }

    function specie($r) {
        $id = $r->getRequest()->getParameter('id');
        $spp = Utils::$couchdb->get($id);
        $docs = Utils::$couchdb->asDocuments()->getView("species_profiles","by_taxon_lsid",$id);
        if(isset($docs[0])) {
            $doc = $docs[0];
            foreach($docs as $d) {
                if($d->metadata['modified'] > $doc->metadata['modified']) {
                    $doc = $d;
                }
            }
        }
        if(!isset($doc)) {
            return new View('specie.html',array('specie'=>$spp));
        } else {
            return new \Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
        }
    }
}
