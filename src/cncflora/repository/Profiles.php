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

        foreach($profiles as $profile) {
        }

        usort($profiles,function($a0,$a1){
          return strcmp($a0->taxon->scientificNameWithoutAuthorship,$a1->taxon->scientificNameWithoutAuthorship);
        });
        return $profiles;
    }

    public function getAllOthers($name) {
      $others = [];

      $dbs = array();
      $all = \cncflora\Utils::http_get(COUCHDB.'/_all_dbs');
      foreach($all as $db) {
        if($db[0] != "_" && !preg_match('/_history$/',$db) && DB != $db && $db != "public") {
          $dbs[] = array('db'=>$db,'name'=>strtoupper(str_replace('_',' ',$db)));
        }
      }

      foreach($dbs as $db) {
        $profile = null;
        $docs = \cncflora\Utils::searchRaw($db['db'],"profile","taxon.scientificNameWithoutAuthorship:\"".$name."\"");
        foreach($docs as $doc) {
          $doc->db = $db;
          $doc->metadata->modified_date = date('d-m-Y',$doc->metadata->modified);
          $doc->metadata->created_date = date('d-m-Y',$doc->metadata->created);
          $others[] = $doc;
        }
      }

      return $others;
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

        if(isset($profile->ecology)) {
          if(isset($profile->ecology->luminosity)) {
            if(is_string($profile->ecology->luminosity)) {
              $profile->ecology->luminosity = array($profile->ecology->luminosity);
            }
          }
          if(isset($profile->ecology->lifeForm)) {
            if(is_string($profile->ecology->lifeForm)) {
              $profile->ecology->lifeForm = array($profile->ecology->lifeForm);
            }
          }
          if(isset($profile->ecology->substratum)) {
            if(is_string($profile->ecology->substratum)) {
              $profile->ecology->substratum = array($profile->ecology->substratum);
            }
          }
        }

        return $profile;
    }

    public function get($id) {
      $profile = parent::get($id);

      if($profile != null){
        if(isset($profile->ecology)) {
          if(isset($profile->ecology->luminosity)) {
            if(is_string($profile->ecology->luminosity)) {
              $profile->ecology->luminosity = array($profile->ecology->luminosity);
            }
          }
          if(isset($profile->ecology->lifeForm)) {
            if(is_string($profile->ecology->lifeForm)) {
              $profile->ecology->lifeForm = array($profile->ecology->lifeForm);
            }
          }
          if(isset($profile->ecology->substratum)) {
            if(is_string($profile->ecology->substratum)) {
              $profile->ecology->substratum = array($profile->ecology->substratum);
            }
          }
        }
      }

      return $profile;
    }

}

