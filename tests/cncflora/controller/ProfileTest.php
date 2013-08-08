<?php

namespace cncflora\controller;

class ProfileTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        $this->user = new \StdClass;
        $this->user->name = "foo";
        $this->user->email = "foo@bar.com";
        $this->repo = new \cncflora\repository\Profiles($this->user);

        $taxons = (new \cncflora\repository\Species)->getSpecies('ACANTHACEAE');
        $this->taxon0  = $taxons[0];
        $this->taxon1  = $taxons[1];
        $this->profile = $this->repo->create($this->taxon0);
    }

    public function tearDown() {
        $this->repo->delete($this->repo->get($this->profile->_id));
    }

    public function testProfile() {
        $rest = new \Rest\Server;
        $rest->getRequesT()->setURI($this->profile->_id);
        $rest->setMatch(array(":id"));

        $control = new Profile;
        $view = $control->execute($rest);

        $this->assertEquals($this->profile->_id,$view->props['profile']->_id);
        $this->assertEquals(true,$view->props['edit']);
    }

    public function testProfileViewRedir() {
        $this->markTestSkipped();
    }

    public function testCreate() {
        $_POST["lsid"] = $this->taxon1->_id;
        $rest = new \Rest\Server;
        $rest->setParameter("user",$this->user);

        $control = new Profile;
        $view = $control->createProfile($rest);

        $ok = preg_match('@profile/([^/]+)/edit@',$view->dunno,$reg);
        $profile = $this->repo->get($reg[1]);
        $this->assertEquals($this->taxon1->scientificName,$profile->taxon->scientificName);

        $this->repo->delete($profile);
    }

    public function testEditForm() {
        $rest = new \Rest\Server;
        $rest->getRequesT()->setURI($this->profile->_id);
        $rest->setMatch(array(":id"));
        $rest->setParameter("user",$this->user);

        $control = new Profile;
        $view = $control->edit($rest);

        $this->assertNotNull($view->props['schema']);
        $this->assertNotNull($view->props['profile']);
        $this->assertNotNull($view->props['data']);
        $this->assertNotNull($view->props['metadata']);
        $this->assertNotNull($view->props['taxon']);
    }

    public function testSave() {
        $this->markTestSkipped();
    }
}
