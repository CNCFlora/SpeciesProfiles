<?php

namespace cncflora;

use Symfony\Component\Yaml\Yaml;

class Utils {
    public static $db;
    public static $couchdb;
    public static $config;
    public static $strings;

    public static function init() {
        self::$config  = self::config();
        if(defined('DB'))
          self::$couchdb = COUCHDB."/".DB;
        else
        self::$couchdb = COUCHDB;
        self::$strings = json_decode(file_get_contents(__DIR__."/../../resources/locales/".LANG.".json"));
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

        //putenv("PHP_ENV=${env}");
        //$data["ENV"] = $env;
        // Used in header and app.js to indicate whether is a test or not and
        // bypass some verifications
        $data["TEST"]=($env=='test');

        $array = $raw[$env];

        foreach($array as $key=>$value) {
            preg_match_all('/\$([a-zA-Z]+)/',$value,$reg);
            if(count($reg[0]) >= 1) {
              $e = getenv($reg[1][0]);
              $data[strtoupper($key)] = str_replace($reg[0][0],$e,$value);
            } else {
              $data[strtoupper($key)] = $value;
            }
        }

        //Only use these variables if they are set in configuration file
        //$context = getenv("CONTEXT");
        //if($context != null) $data["CONTEXT"] = $context;

        // Set BASE to null if BASE not defined in config file
        if(!isset($data['BASE'])) $data['BASE'] = '';

        // Set DB
        $db = getenv("DB");
        if($db != null) $data["DB"] = $db;

        //Set default language
        if(!isset($data['LANG'])) {
          $data['LANG'] = 'pt';
        }

        foreach($data as $k=>$v) {
            if(!defined($k)) {
                define(strtoupper($k),$v);
            }
        }

        return $data;
    }

    public static function http_get($url) {
        $content = @file_get_contents($url);
        return json_decode($content);
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

    public static function log($data) {
        $output = fopen('php://stdout', 'w');
        if(is_string($data)) {
            fwrite($output, $data."\n");
        } else {
            fwrite($output, json_encode($data)."\n");
        }
        fclose($output);
    }


    public static function search($idx,$q) {
      return self::searchRaw(DB,$idx,$q);
    }

    public static function searchRaw($db,$idx,$q) {
        $q = str_replace("=",":",$q);
        $url = ELASTICSEARCH.'/'.$db.'/'.$idx.'/_search?size=99999&q='.urlencode($q);
        $r = Utils::http_get($url);
        $arr =array();
        $ids = [];
        if(isset($r) && isset($r->hits)) {
          foreach($r->hits->hits as $hit) {
            $doc = $hit->_source;
            $doc->_id = $doc->id;
            if(isset($doc->rev)) {
              $doc->_rev = $doc->rev;
              unset($doc->rev);
            }
            unset($doc->id);
            $arr[] = $doc;
          }
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
        $schema->properties->ecology->properties->vegetation->items->enum
            = json_decode(file_get_contents( __DIR__."/../../resources/dicts/vegetation.json" ));
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
