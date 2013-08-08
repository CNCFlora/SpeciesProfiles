<?php

namespace cncflora;

class Utils {
    public static $db;
    public static $data;
    public static $couchdb;
    public static $couch;
    public static $config;
    public static $strings;

    public static function init() {
        self::$config = self::config();
        self::$data = __DIR__.'/../../data';
        self::$couchdb = "http://".COUCH_HOST.":".COUCH_PORT."/".COUCH_BASE;
        self::$strings = json_decode(file_get_contents(__DIR__."/../../resources/locales/".LANG.".json"));
    }

    public static function config() {
        $ini = parse_ini_file(__DIR__."/../../resources/config.ini");
        $arr = array();
        foreach($ini as $k=>$v) {
            if(!defined($k)){
                define($k,$v);
            }
            $arr[$k] = $v;
        }
        return $arr;
    }

    public static function schema() {
        $ddoc_json = file_get_contents(Utils::$couchdb.'/_design/species_profiles');
        $ddoc = json_decode($ddoc_json);
        $schema_json = substr( $ddoc->schema->profile,24,-2);
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

