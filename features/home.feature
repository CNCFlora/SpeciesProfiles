Feature: Test homepage load
    In order for the website to work
    As a user
    I need to access the homepage

    Scenario: Open homepage 
        Given I am on "/"
        Then I should see "ENV=test"
        Then I should see "Sem resultados"

    Scenario: Perform search
        Given I am on "/"
        When I fill in "query" with "Aphelandra longiflora"
        And I press "search-btn"
        Then I should see "Sem resultados"

