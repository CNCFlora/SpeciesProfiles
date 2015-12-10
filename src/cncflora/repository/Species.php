<?php

namespace cncflora\repository;

class Species extends Base {

    public function getFamilies() {
        $response = $this->search("taxon","taxonomicStatus:\"accepted\"");
        $families = array();
        foreach($response as $row) {
            $families[] = strtoupper($row->family);
        }
        sort($families);
        return array_unique($families) ;
    }

    public function getSpecies($family) {
        $response = $this->search("taxon","taxonomicStatus:\"accepted\" AND "
            ."(family:\"".ucfirst($family)."\" OR family:\"".strtoupper($family)."\" OR family:\"".strtolower( $family )."\")");
        $species = array();
        foreach($response as $row) {
            $row->family = strtoupper($row->family);
            $species[] = $row;
        }
        usort($species,function($s0,$s1){
            return $s0->scientificName > $s1->scientificName;
        });
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
        $r= $this->search('taxon','scientificNameWithoutAuthorship:"'.$name.'"');
        if(isset($r[0])) {
            return $r[0];
        } else {
            return null;
        }
    }

    public function getSynonyms($name) {
        $response = $this->search("taxon","taxonomicStatus:\"synonym\" AND acceptedNameUsage:\"".$name."*\"");
        $taxons = array();
        foreach($response as $row) {
            $row->family = strtoupper($row->family);
            $taxons[] = $row;
        }
        return $taxons;
    }

    public function getCurrentTaxon($name) {
      $flora = \cncflora\Utils::http_get(FLORADATA."/api/v1/specie?scientificName=".rawurlencode($name))->result;

      if($flora==null) {
        $flora = ["not_found"=>true];
      } else if($flora->scientificNameWithoutAuthorship != $name) {
        $flora->changed=true;
      } else {
        $syns = $this->getSynonyms($name);
        $floraSyns = $flora->synonyms;

        $synsNames = [];
        foreach($syns as $syn) {
          $synsNames[] = $syn->scientificNameWithoutAuthorship;
        }
        sort($synsNames);

        $floraSynsNames =[];
        foreach($floraSyns as $syn) {
          $floraSynsNames[] = $syn->scientificNameWithoutAuthorship;
        }
        sort($floraSynsNames);

        if(implode(",",$floraSynsNames) != implode(",",$synsNames)) {
            $flora->synonyms_changed=true;
        }
      }

      return $flora;
    }
}

