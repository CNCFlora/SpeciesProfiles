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
        $metadata->identifier = 'profile:'.uniqid();

        $profile = new \StdClass;
        $profile->_id = $metadata->identifier;
        $profile->metadata = $metadata;
        $profile->taxon = new \StdClass;
        $profile->taxon->family = $taxon->family;
        $profile->taxon->scientificName = $taxon->scientificName;
        $profile->taxon->scientificNameWithoutAuthorship = $taxon->scientificNameWithoutAuthorship;
        $profile->taxon->scientificNameAuthorship = $taxon->scientificNameAuthorship;

        $r = $this->put($profile);

        $profile->_rev = $r->rev;
        return $profile;
    }

    public function update($profile,$log=true) {
        if($log) {
            $this->metalog($profile);
        }

        $r = $this->put($profile);
        if(isset($r->error)) {
            return $r;
        } else {
            $profile->_rev = $r->rev;
            return $profile;
        }
    }

    public function listByFamily($family) {

        $repo = new \cncflora\repository\Species;
        $spps = $repo->getSpecies($family);

        $profiles = array();
        $docs = $this->search("profile","taxon.family=\"".$family."\"");
        foreach($docs as $doc) {
            $ok = false;
            foreach($spps as $spp) {
              if($spp->scientificNameWithoutAuthorship == $doc->taxon->scientificNameWithoutAuthorship) {
                  $ok = true;
                  break;
              }
            }
            if($ok) {
                $doc->taxon->family = strtoupper($doc->taxon->family);
                $profiles[] = $doc;
            }
        }

        usort($profiles,function($a0,$a1){
          return strcmp($a0->taxon->scientificNameWithoutAuthorship,$a1->taxon->scientificNameWithoutAuthorship);
        });
        return $profiles;
    }

    public function latestByTaxon($name) {
        $profile = null;
        $docs = $this->search("profile","taxon.scientificNameWithoutAuthorship:\"".$name."\"");
        foreach($docs as $doc) {
            $doc->taxon->family = strtoupper($doc->taxon->family);
            if(is_null($profile)) {
                $profile = $doc;
            } else if($profile->metadata->created <= $doc->metadata->created ) {
                $profile = $doc;
            }
        }
        return $profile;
    }

}

