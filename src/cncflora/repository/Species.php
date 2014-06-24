<?php

namespace cncflora\repository;

class Species extends Base {

    public function getFamilies() {
        $response = $this->search("taxon","taxonomicStatus:\"accepted\"");
        $families = array();
        foreach($response as $row) {
            $families[] = strtoupper($row->family);
        }

        return array_unique($families);
    }

    public function getSpecies($family) {
        $response = $this->search("taxon","taxonomicStatus:\"accepted\" AND "
            ."(family:\"".ucfirst($family)."\" OR family:\"".strtoupper($family)."\" OR family:\"".strtolower( $family )."\")");
        $species = array();
        foreach($response as $row) {
            $row->family = strtoupper($row->family);
            $species[] = $row;
        }
        return $species;
    }

    public function getSpecie($id) {
        $r = $this->get($id);
        if(isset($r->error)) {
            return null;
        } else {
            return $r;
        }
    }

    public function getSpecieByName($name) {
        $r= $this->search('taxon','scientificName:"'.$name.'"');
        if(isset($r[0])) {
            return $r[0];
        } else {
            return null;
        }
    }

    public function getSynonyms($name) {
        $response = $this->search("taxon","taxonomicStatus:\"synonym\" AND acceptedNameUsage:\"".$name."\"");
        $taxons = array();
        foreach($response as $row) {
            $row->family = strtoupper($row->family);
            $taxons[] = $row;
        }
        return $taxons;
    }

}

