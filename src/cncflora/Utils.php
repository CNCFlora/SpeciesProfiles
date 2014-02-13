<?php

namespace cncflora;

use Symfony\Component\Yaml\Yaml;

class Utils {
    public static $db;
    public static $data;
    public static $couchdb;
    public static $couch;
    public static $config;
    public static $strings;
    public static $taxons;

    public static function init() {
        self::$config  = self::config();
        self::$data    = __DIR__.'/../../data';
        self::$couchdb = "http://".COUCHDB_HOST.":".COUCHDB_PORT."/".COUCHDB_BASE;
        self::$strings = json_decode(file_get_contents(__DIR__."/../../resources/locales/".LANG.".json"));
        self::$taxons  = self::taxons();
    }

    public static function taxons() {
        $arr =  array();
        $f = fopen(__DIR__."/../../resources/checklist.csv",'r');
        while($l = fgetcsv($f,0,';','"')) {
            $name = strtolower(trim(implode(" ",$l)));
            $arr[strtolower( $l[0] )] = true;
            $arr[$name] = true;
        }
        fclose($f);
        return $arr;
    }

    public static function taxonOk($t) {
       if(isset(self::$taxons[strtolower($t)]) && self::$taxons[strtolower( $t )] === true) return true;
       else return false;
    }

    public static function setupTest() {
        if(!defined('TEST')) {
            define('TEST',true);
        }
        self::$couchdb = "http://".COUCHDB_HOST.":".COUCHDB_PORT."/".COUCHDB_BASE."_test";
    }

    public static function config() {
        $data = array();

        $array = Yaml::parse(__DIR__."/../../resources/config.yml");
        foreach($array as $key=>$value) {
            $data[strtoupper($key)] = $value;
        }

        if(isset($data['ETCD'])) {
            $keys = json_decode( file_get_contents($data['ETCD']."/v2/keys/?recursive=true") );
            foreach($keys->node->nodes as $node) {
                if(isset($node->nodes)) {
                    foreach($node->nodes as $entry) {
                        $key  = strtoupper(str_replace("/","_",substr($entry->key,1)));
                        if(isset($entry->value) && !is_null($entry->value)) {
                            $data[$key] = $entry->value;
                        }
                    }
                }
            }
        }

        foreach($data as $k=>$v) {
            if(!defined($k)) {
                define($k,$v);
            }
        }

        return $data;
    }

    public static function schema() {
        $ddoc_json = file_get_contents(Utils::$couchdb.'/_design/species_profiles');
        $ddoc = json_decode($ddoc_json);
        $schema_json = substr($ddoc->schema->profile,24,-2);
        $schema = json_decode($schema_json);
        unset($schema->properties->taxon);
        unset($schema->properties->metadata);
        unset($schema->properties->validations);
        unset($schema->required);

        $schema->properties->ecology->properties->habitats->items->enum = json_decode(file_get_contents( __DIR__."/../../resources/dicts/habitats.json" ));
        $schema->properties->ecology->properties->biomas->items->enum = json_decode(file_get_contents( __DIR__."/../../resources/dicts/biomas.json" ));
        $schema->properties->ecology->properties->fitofisionomies->items->enum = json_decode(file_get_contents( __DIR__."/../../resources/dicts/fitofisionomies.json" ));
        $schema->properties->threats->items->properties->threat->enum = json_decode(file_get_contents(__DIR__."/../../resources/dicts/threats.json"));
        $schema->properties->actions->items->properties->action->enum = json_decode(file_get_contents(__DIR__."/../../resources/dicts/actions.json"));
        $schema->properties->uses->items->properties->use->enum = json_decode(file_get_contents(__DIR__."/../../resources/dicts/uses.json"));

        return $schema;
    }

}

function t($str) {
    if(isset(Utils::$strings->$str)) {
        return Utils::$strings->$str;
    } else {
        return $str;
    }
}

Utils::init();

