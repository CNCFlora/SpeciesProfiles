<?php

namespace cncflora\repository;

use cncflora\Utils ;

abstract class Base {
    public $user = null;
    public $couchdb = null;
    public $db = null;

    public function __construct($user=null) {
        Utils::config();
        $this->user = $user;
    }

    public function get($id) {
       return \cncflora\Utils::http_get(DATAHUB_URL.'/'.DB.'/'.$id);
    }

    public function put($doc) {
       return \cncflora\Utils::http_put(DATAHUB_URL.'/'.DB.'/'.$doc->_id,$doc);
    }

    public function delete($doc) {
        if(is_string($doc)) {
            $doc = $this->get($doc);
        }
        return \cncflora\Utils::http_delete(DATAHUB_URL.'/'.DB.'/'.$doc->_id.'?rev='.$doc->_rev);
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
