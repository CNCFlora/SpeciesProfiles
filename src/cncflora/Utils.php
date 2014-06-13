<?php

namespace cncflora;

use Symfony\Component\Yaml\Yaml;

class Utils {
    public static $db;
    public static $couchdb;
    public static $couch;
    public static $config;
    public static $strings;
    public static $taxons;

    public static function init() {
        self::$config  = self::config();
        self::$couchdb = DATAHUB_URL."/".DB;
        self::$strings = json_decode(file_get_contents(__DIR__."/../../resources/locales/".LANG.".json"));
        //self::$taxons  = self::taxons();
    }

    public static function config() {
        $data = array();

        $raw = Yaml::parse(file_get_contents( __DIR__."/../../config.yml" ));

        $env = getenv("PHP_ENV");
        if($env == null) {
            $env = 'development';
        }

        if(isset($_SERVER) && isset($_SERVER['HTTP_HOST'])) {
            if($_SERVER['HTTP_HOST'] == 'test.localhost') {
                $env = "test";
            }
        }

        putenv("PHP_ENV=${env}");
        $data["ENV"] = $env;

        $array = $raw[$env];

        foreach($array as $key=>$value) {
            $data[strtoupper($key)] = $value;
        }

        if(isset($data['ETCD'])) {
            $keys = json_decode( file_get_contents($data['ETCD']."/v2/keys/?recursive=true") );
            foreach($keys->node->nodes as $node) {
                if(isset($node->nodes)) {
                    foreach($node->nodes as $entry) {
                        $key  = strtoupper(str_replace("-","_",( str_replace("/","_",substr($entry->key,1)))));
                        if(isset($entry->value) && !is_null($entry->value)) {
                            $data[$key] = $entry->value;
                        }
                    }
                }
            }
        }

        foreach($data as $k=>$v) {
            if(!defined($k)) {
                define(strtoupper($k),$v);
            }
        }

        return $data;
    }

    public static function http_get($url) {
        return json_decode(file_get_contents($url));
    }

    public static function http_post($url,$doc) {
        $opts = ['http'=>['method'=>'POST','content'=>json_encode($doc),'header'=>'Content-type: application/json']];
        $r = file_get_contents($url, NULL, stream_context_create($opts));
        return json_decode($r);
    }

    public static function http_put($url,$doc) {
        $opts = ['http'=>['method'=>'PUT','content'=>json_encode($doc),'header'=>'Content-type: application/json']];
        $r = file_get_contents($url, NULL, stream_context_create($opts));
        return json_decode($r);
    }

    public static function http_delete($url) {
        $opts = ['http'=>['method'=>'DELETE']];
        $r = file_get_contents($url, NULL, stream_context_create($opts));
        return json_decode($r);
    }

    public static function search($idx,$q) {
        $url = DATAHUB_URL.'/'.DB.'/'.$idx.'/_search?size=999&q='.urlencode($q);
        $r = Utils::http_get($url);
        $arr =array();
        $ids = [];
        foreach($r->hits->hits as $hit) {
            $arr[] = $hit->_source;
            $ids[] = $hit->_source->id;
        }

        $r = Utils::http_get(DATAHUB_URL.'/'.DB.'/_all_docs?keys='.json_encode($ids));
        foreach($r->rows as $row) {
            foreach($arr as $a) {
                if($a->id == $row->id) {
                    $a->_id = $row->id;
                    $a->_rev = $row->value->rev;
                }
            }
        }

        foreach($arr as $a) {
            unset($a->id);
        }

        return $arr;
    }

    public static function schema() {
        $schema = json_decode(file_get_contents(__DIR__.'/../../resources/schema.json'));

        unset($schema->properties->taxon);
        unset($schema->properties->metadata);
        unset($schema->properties->validations);
        unset($schema->required);

        $schema->properties->ecology->properties->habitats->items->enum
            = json_decode(file_get_contents( __DIR__."/../../resources/dicts/habitats.json" ));
        $schema->properties->ecology->properties->biomas->items->enum 
            = json_decode(file_get_contents( __DIR__."/../../resources/dicts/biomas.json" ));
        $schema->properties->ecology->properties->fitofisionomies->items->enum 
            = json_decode(file_get_contents( __DIR__."/../../resources/dicts/fitofisionomies.json" ));
        $schema->properties->threats->items->properties->threat->enum
            = json_decode(file_get_contents(__DIR__."/../../resources/dicts/threats.json"));
        $schema->properties->threats->items->properties->stress->enum 
            = json_decode(file_get_contents(__DIR__."/../../resources/dicts/stress.json"));
        $schema->properties->actions->items->properties->action->enum
            = json_decode(file_get_contents(__DIR__."/../../resources/dicts/actions.json"));
        $schema->properties->uses->items->properties->use->enum 
            = json_decode(file_get_contents(__DIR__."/../../resources/dicts/uses.json"));

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

