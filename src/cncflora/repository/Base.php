<?php

namespace cncflora\repository;

use cncflora\Utils ;

class Base {
    public $user = null;
    public $couchdb = null;
    public $db = null;

    public function __construct($user=null) {
        Utils::config();
        $this->user = $user;
    }

    public function get($id) {
       return \cncflora\Utils::http_get(COUCHDB.'/'.DB.'/'.$id);
    }

    public function put($doc) {
       if(!isset($doc->_id)) $doc->id = 'urn:'.uniqid();
      
       $re = \cncflora\Utils::http_put(COUCHDB.'/'.DB.'/'.$doc->_id,$doc);

       $redoc= clone $doc;
       $redoc->id = $doc->_id;
       $redoc->rev = $re->rev;
       \cncflora\Utils::http_put(ELASTICSEARCH.'/'.DB.'/'.$redoc->metadata->type.'/'.$redoc->id,$redoc);
       sleep(1);

       return $re;

    }

    public function delete($doc) {
        if(is_string($doc)) {
            $doc = $this->get($doc);
        }
        \cncflora\Utils::http_delete(ELASTICSEARCH.'/'.DB.'/'.$doc->metadata->type.'/'.$doc->_id);
        return \cncflora\Utils::http_delete(COUCHDB.'/'.DB.'/'.$doc->_id.'?rev='.$doc->_rev);
    }

    public function search($idx,$q) {
        return \cncflora\Utils::search($idx,$q);
    }

    public function metalog($profile) {
        $metadata = $profile->metadata;
        if(strpos($metadata->contact,$this->user->email) === false) {
            $metadata->contributor = $this->user->name ." ; ".$metadata->contributor;
            $metadata->contact = $this->user->email ." ; ".$metadata->contact;
        }

        $contributors = explode(" ; ",$metadata->contributor);
        $contributorsFinal = array();
        foreach($contributors as $contributor) {
            if($contributor != null && strlen($contributor) >= 3) {
                $contributorsFinal[] = $contributor;
            }
        }
        $metadata->contributor = implode(" ; ",$contributorsFinal);

        $contacts = explode(" ; ",$metadata->contact);
        $contactsFinal = array();
        foreach($contacts as $contact) {
            if($contact != null && strlen($contact) >= 3) {
                $contactsFinal[] = $contact;
            }
        }
        $metadata->contact = implode(" ; ",$contactsFinal);

        $metadata->modified = time();
        $profile->metadata = $metadata;
    }
}
