Feature: Open and edit profiles

    Scenario: create profile
        Given I am on "/cncflora_test/specie/Aphelandra longiflora"
        Then I login as "Diogo", "diogo@cncflora.net", "cncflora_test", "admin,analyst"
        And I press "create-btn"

    Scenario: Edit a profile, changes apply and metadata
        Given I am on "/cncflora_test/specie/Aphelandra longiflora"
        When I login as "Bruno", "bruno@cncflora.net", "cncflora_test", "analyst", "ACANTHACEAE"
        And I follow "Editar"
        Then I should see "Contribuidor(es): [Bruno] ; Diogo"
        Then I wait 5000
        Then I should see "Taxonomic Notes"
        Then I fill field "textarea[id*='-taxonomicNotes-notes']" with "Hello, notes."
        And I press "Salvar"
        And I wait 7000
        Then I should see "Notas Taxonômicas"
        Then I should see "Status: open"
        Then I should see "Hello, notes."

    Scenario: Change workflow, and still save content
        Given I am on "/cncflora_test/specie/Aphelandra longiflora"
        When I login as "Bruno", "bruno@cncflora.net", "cncflora_test", "analyst", "ACANTHACEAE"
        And I follow "Editar"
        Then I wait 5000
        And I press "Enviar para GIS"
        And I wait 5000
        Then I should see "Status: sig"

