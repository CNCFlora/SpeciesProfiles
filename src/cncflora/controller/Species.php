<?php

namespace cncflora\controller;

use cncflora\Utils;
use cncflora\View;

class Species implements \Rest\Controller {

    public $repo;
    public $repoProfiles;

    public function __construct() {
        $this->repo = new \cncflora\repository\Species;
        $this->repoProfiles = new \cncflora\repository\Profiles;
    }

    public function execute(\Rest\Server $r) {
        $families = $this->repo->getFamilies();
        return new View('families.html',array('families'=>$families));
    }

    function family($r) {
        $family = $r->getRequest()->getParameter('family');
        $docs = $this->repoProfiles->listByFamily($family);
        $profiles = array();
        foreach($docs as $p) {
            $profiles[$p->taxon->scientificName] = true; 
        }
        $species = $this->repo->getSpecies($family);
        foreach($species as $spp) {
            if(isset($profiles[$spp->scientificName])) {
                $spp->have = true;
            }
        }
        return new View('family.html',array('species'=>$species,'family'=>$family));
    }

    function specie($r) {
        $id  = $r->getRequest()->getParameter('id');
        $spp = $this->repo->getSpecie($id);
        $doc = $this->repoProfiles->latestByTaxon($spp->_id);
        if(is_null($doc)) {
            return new View('specie.html',array('specie'=>$spp));
        } else {
            return new \Rest\Controller\Redirect('/'.BASE_PATH.'profile/'.$doc->_id);
        }
    }
}
