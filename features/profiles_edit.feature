Feature: Open and edit profiles

    Scenario: create profile
        Given I am on "/specie/taxon:1"
        Then I login as "Diogo", "diogo@cncflora.net", "admin,analyst"
        And I press "create-btn"

    Scenario: Edit a profile, changes apply and metadata
        Given I am on "/specie/taxon:1"
        When I login as "Bruno", "bruno@cncflora.net", "analyst", "ACANTHACEAE"
        And I follow "Editar"
        Then I should see "Contribuidor(es): [Bruno] ; Diogo"
        Then I wait 5000
        Then I should see "Taxonomic Notes"
        Then I fill field "textarea[id*='-taxonomicNotes-notes']" with "Hello, notes."
        And I press "Salvar"
        And I wait 5000
        Then I should see "Notas Taxonômicas"
        Then I should see "Status: open"
        Then I should see "Hello, notes."

    Scenario: Change workflow, and still save content
        Given I am on "/specie/taxon:1"
        When I login as "Bruno", "bruno@cncflora.net", "analyst", "ACANTHACEAE"
        And I follow "Editar"
        Then I wait 5000
        And I press "Enviar para GIS"
        And I wait 5000
        Then I should see "Status: sig"

