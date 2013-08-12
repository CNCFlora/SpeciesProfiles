<?php

namespace cncflora\controller;

class WorkflowTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        $this->user = new \StdClass;
        $this->user->name = "foo";
        $this->user->email = "foo@bar.com";

        $this->repo = new \cncflora\repository\Profiles($this->user);

        $taxons = (new \cncflora\repository\Species)->getSpecies('ACANTHACEAE');
        $this->taxon0  = $taxons[0];
        $this->taxon1  = $taxons[1];
        $this->profile = $this->repo->create($this->taxon0);

        $this->user->roles = array();

        $role = new \StdClass;
        $role->role= "Analyst";
        $role->entities = array();
        $ent0 = new \StdClass;
        $ent0->value = $this->taxon0->_id;
        $ent1 = new \StdClass;
        $ent1->value = $this->taxon0->_id;
        $role->entities[] = $ent0;

        $this->user->roles[] = $role;
    }

    public function tearDown() {
        $this->repo->delete($this->repo->get($this->profile->_id));
    }

    public function testFamilies() {
        $rest = new \Rest\Server();
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->execute($rest);
        $this->assertCount(1,$view->props['families']);
        $this->assertEquals("ACANTHACEAE",$view->props['families'][0]);
    }

    /**
     * @expectedException Exception 
     */
    public function testMustHaveUser(){
        $rest = new \Rest\Server();
        $control = new Workflow;
        $view = $control->execute($rest);
    }

    public function testFamiliesAndStatuses() {
        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->taxon0->family."/open");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $this->assertEquals($this->taxon0->_id,$view->data[0]->taxon->lsid);

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->taxon0->family."/review");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $this->assertEmpty($view->data);

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("NOEXIST/open");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $this->assertEmpty($view->data);
    }

    public function testFamilyStatusPersmission() {
        $this->markTestSkipped();
    }

    public function testChanges() {
        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->taxon0->family."/open");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $i0 = count($view->data);

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->profile->_id."/review");
        $rest->setMatch(array(":id",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->changeStatus($rest);

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->taxon0->family."/open");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $i1 = count($view->data);
        $this->assertEquals($i0 - 1, $i1);

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->taxon0->family."/review");
        $rest->setMatch(array(":family",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->family($rest);
        $this->assertEquals(1, count($view->data));

        $rest = new \Rest\Server();
        $rest->getRequest()->setURI("".$this->profile->_id."/open");
        $rest->setMatch(array(":id",":status"));
        $rest->setParameter("user",$this->user);
        $control = new Workflow;
        $view = $control->changeStatus($rest);
    }
}

