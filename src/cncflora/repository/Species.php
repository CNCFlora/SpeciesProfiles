<?php

namespace cncflora\repository;

class Species extends Base {

    public function getFamilies() {
        $response = $this->db->view('taxonomy','species_by_family',array('reduce'=>true,'group'=>true));
        $families = array();
        foreach($response->rows as $row) {
            if(\cncflora\Utils::taxonOk($row->key)) $families[] = strtoupper( $row->key );
        }
        return $families;
    }

    public function getSpecies($family) {
        $response = $this->db->view('taxonomy','species_by_family',array('reduce'=>false,'key'=>$family));
        $species = array();
        foreach($response->rows as $row) {
            if(\cncflora\Utils::taxonOk($row->value->family." ".$row->value->scientificName)) {
                $row->value->family = strtoupper($row->value->family);
                $species[] = $row->value;
            }
        }
        return $species;
    }

    public function getSpecie($id) {
        $r = $this->db->get($id);
        if(isset($r->error)) {
            return null;
        } else {
            return $r;
        }
    }

    public function getSynonyms($id) {
        $response = $this->db->view('taxonomy','synonyms',array('reduce'=>false,'key'=>$id));
        $taxons = array();
        foreach($response->rows as $row) {
            $taxons[] = $row->value;
        }
        return $taxons;
    }

}

