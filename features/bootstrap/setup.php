<?php
putenv("PHP_ENV=test");

include_once __DIR__.'/../../vendor/autoload.php';

use cncflora\Utils;

$repo0 = new \cncflora\repository\Base;
$all = $repo0->get("_all_docs");
foreach($all->rows as $r) {
    $repo0->delete($r->id);
}

$repo = new \cncflora\repository\Species;

$t1 = new \StdClass;
$t1->metadata = new \StdClass;
$t1->metadata->type = 'taxon';
$t1->_id = 'taxon:1';
$t1->family = 'Acanthaceae';
$t1->scientificName = 'Aphelandra longiflora';
$t1->scientificNameAuthorship = 'S.Profice';
$t1->taxonomicStatus = 'accepted';

$t2 = new \StdClass;
$t2->metadata = new \StdClass;
$t2->metadata->type = 'taxon';
$t2->_id = 'taxon:2';
$t2->family = 'Acanthaceae';
$t2->scientificName = 'Aphelandra longiflora2';
$t2->scientificNameAuthorship = 'S.Profice';
$t2->taxonomicStatus = 'synonym';
$t2->acceptedNameUsage = 'Aphelandra longiflora';

$t3 = new \StdClass;
$t3->metadata = new \StdClass;
$t3->metadata->type = 'taxon';
$t3->_id = 'taxon:3';
$t3->family = 'Acanthaceae';
$t3->scientificName = 'Aphelandra espirito-santensis';
$t3->scientificNameAuthorship = 'S.Profice';
$t3->taxonomicStatus = 'accepted';

$t4 = new \StdClass;
$t4->metadata = new \StdClass;
$t4->metadata->type = 'taxon';
$t4->_id = 'taxon:4';
$t4->family = 'BROMELIACEAE';
$t4->scientificName = 'Dickya whatevs';
$t4->scientificNameAuthorship = 'Forza';
$t4->taxonomicStatus = 'accepted';

@$repo->put($t1);
@$repo->put($t2);
@$repo->put($t3);
@$repo->put($t4);

sleep(1);

