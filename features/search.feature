Feature: Search

    Scenario: Perform search
        Given I am on "/"
        When I fill in "query" with "Aphelandra longiflora"
        And I press "search-btn"
        Then I should see "Sem resultados"

