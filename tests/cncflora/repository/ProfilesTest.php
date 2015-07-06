<?php

namespace cncflora\repository;

include_once 'vendor/autoload.php';
include_once 'tests/cncflora/error_handler.php';

use cncflora\Utils;

class ProfilesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        putenv("PHP_ENV=test");
        putenv("DB=cncflora_test");
        //Init variables
        Utils::init();
        set_error_handler('defaultErrorHandler');
        try {
            Utils::http_delete(COUCHDB."/cncflora_test",[]);
        }
        catch (Exception $e){
            // Database doesn't exist, no need to delete it
        }
        Utils::http_put(COUCHDB."/cncflora_test",[]);

        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
          $repo0->delete($r->id);
        }

        $this->user = new \StdClass;
        $this->user->name = "Foo";
        $this->user->email = "foo@bar.com";

        $repo = new Species;

        $t1 = new \StdClass;
        $t1->metadata = new \StdClass;
        $t1->metadata->type = 'taxon';
        $t1->_id = '1';
        $t1->family = 'Acanthaceae';
        $t1->scientificName = 'Aphelandra longiflora S.Profice';
        $t1->scientificNameWithoutAuthorship = 'Aphelandra longiflora';
        $t1->scientificNameAuthorship = 'S.Profice';
        $t1->taxonomicStatus = 'accepted';

        $t2 = new \StdClass;
        $t2->metadata = new \StdClass;
        $t2->metadata->type = 'taxon';
        $t2->_id = '2';
        $t2->family = 'Acanthaceae';
        $t2->scientificName = 'Aphelandra longiflora2 S.Profice';
        $t2->scientificNameWithoutAuthorship = 'Aphelandra longiflora2';
        $t2->scientificNameAuthorship = 'S.Profice';
        $t2->taxonomicStatus = 'synonym';

        $t3 = new \StdClass;
        $t3->metadata = new \StdClass;
        $t3->metadata->type = 'taxon';
        $t3->_id = '3';
        $t3->family = 'Acanthaceae';
        $t3->scientificName = 'Aphelandra espirito-stantensis S.Profice';
        $t3->scientificNameWithoutAuthorship = 'Aphelandra espirito-stantensis';
        $t3->scientificNameAuthorship = 'S.Profice';
        $t3->taxonomicStatus = 'accepted';

        $t4 = new \StdClass;
        $t4->metadata = new \StdClass;
        $t4->metadata->type = 'taxon';
        $t4->_id = '4';
        $t4->family = 'BROMELIACEAE';
        $t4->scientificName = 'Dickya whatevs Forzza';
        $t4->scientificNameWithoutAuthorship = 'Dickya whatevs';
        $t4->scientificNameAuthorship = 'Forzza';
        $t4->taxonomicStatus = 'accepted';

        $repo->put($t1);
        $repo->put($t2);
        $repo->put($t3);
        $repo->put($t4);
    }


    public static function tearDownAfterClass() {
        Utils::http_delete(COUCHDB."/cncflora_test",[]);
    }

    public function tearDown() {
        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
            $repo0->delete($r->id);
        }
    }

    /**
     * @expectedException Exception
     */
    public function testMustHaveUser() {
        $repo = new Profiles();

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);
    }

    public function testCRUD() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->_id);
        $this->assertEquals($profile->taxon->family,$taxon->family);
        $this->assertEquals($profile->taxon->scientificName,$taxon->scientificName);

        $profilePersisted = $repo->get($profile->_id);

        $this->assertNotNull($profilePersisted);
        $this->assertEquals($profile,$profilePersisted);

        $profile->ecology = new \StdClass;
        $profile->ecology->resume = "Hello, World!";
        $repo->update($profile);


        $profilePersisted = $repo->get($profile->_id);
        $this->assertEquals($profilePersisted->ecology->resume,"Hello, World!");

        $repo->delete($profile);
    }

    public function testListByFamily() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);

        $profileList = $repo->listByFamily("ACANTHACEAE");
        $this->assertNotEmpty($profileList);
        //$this->assertCount(2,$profileList);
        foreach($profileList as $p) {
            $this->assertEquals($p->taxon->family,"ACANTHACEAE");
        }

        foreach($profileList as $p) {
            $repo->delete($p);
        }
    }

    public function testLatestByTaxon() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];

        $profile1 = $repo->create($taxon);
        $profile2 = $repo->create($taxon);

        $profileSpp = $repo->latestByTaxon($taxon->scientificNameWithoutAuthorship);
        $this->assertEquals($profile2,$profileSpp);

        $taxon  = $taxons[1];
        $profileSpp = $repo->latestByTaxon($taxon->scientificNameWithoutAuthorship);
        $this->assertNull($profileSpp);

        $repo->delete($profile1);
        $repo->delete($profile2);
    }

    public function testMetadata() {
        $this->markTestIncomplete();
    }

    public function testPermissions() {
        $this->markTestIncomplete();
    }
}
