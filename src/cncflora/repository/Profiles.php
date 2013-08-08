<?php

namespace cncflora\repository;

class Profiles extends Base {

    public function create($taxon) {
        if(!isset($this->user)) {
            throw new \Exception("Need a user");
        }

        $metadata = new \StdClass;
        $metadata->status = "open";
        $metadata->contributor = $this->user->name;
        $metadata->contact = $this->user->email;
        $metadata->creator = $this->user->name;
        $metadata->created = time();
        $metadata->modified = time();
        $metadata->description = "Profile for ".$taxon->scientificName;
        $metadata->title  = "Profile for ".$taxon->scientificName;
        $metadata->source = "cncflora";
        $metadata->type = "profile";
        $metadata->valid = false;
        $metadata->identifier = "urn:lsid:cncflora.jbrj.gov.br:profile:".str_replace(' ',':',strtolower($taxon->scientificName)).":".time();

        $profile = new \StdClass;
        $profile->metadata = $metadata;
        $profile->_id = $metadata->identifier;
        $profile->taxon = new \StdClass;
        $profile->taxon->family = $taxon->family;
        $profile->taxon->scientificName = $taxon->scientificName;
        $profile->taxon->scientificNameAuthorship = $taxon->scientificNameAuthorship;
        $profile->taxon->lsid = $taxon->_id;

        $r = $this->db->insert($profile,$profile->_id);

        $profile->_rev = $r->rev;
        return $profile;
    }

    public function get($id) {
        $obj = $this->db->get($id);
        if(!isset($obj->error))  {
            return $obj;
        } else {
            return null;
        }
    }

    public function update($profile) {
        $metadata = $profile->metadata;
        if(strpos($metadata->contact,$this->user->email) === false) {
            $metadata->contributor = $this->user->name ." ; ".$metadata->contributor;
            $metadata->contact = $this->user->email ." ; ".$metadata->contact;
        }
        $metadata->modified = time();

        $r = $this->db->insert($profile,$profile->_id);

        $profile->_rev = $r->rev;
        return $profile;
    }

    public function listByFamily($family) {
        $profiles = array();
        $docs = $this->db->view('species_profiles','by_family',array('key'=>$family));
        foreach($docs->rows as $doc) {
            $profiles[] = $doc->value;
        }
        return $profiles;
    }

    public function latestByTaxon($lsid) {
        $profile = null;
        $docs = $this->db->view('species_profiles','by_taxon_lsid',array('key'=>$lsid));
        foreach($docs->rows as $doc) {
            if(is_null( $profile )) {
                $profile = $doc->value;
            } else if($profile->metadata->created <= $doc->value->metadata->created ) {
                $profile = $doc->value;
            }
        }
        return $profile;
    }

    public function delete($profile) {
        return $this->db->destroy($profile->_id,$profile->_rev);
    }

}

