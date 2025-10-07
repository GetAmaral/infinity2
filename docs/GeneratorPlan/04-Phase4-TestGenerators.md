# Phase 4: Test Generators (Week 5)

## Overview

Phase 4 generates comprehensive test suites for all generated code.

**Duration:** Week 5 (5 working days)

**Deliverables:**
- ✅ Entity Test Generator
- ✅ Repository Test Generator
- ✅ Controller Test Generator
- ✅ Voter Test Generator
- ✅ Fixture Data Generator (Faker integration)
- ✅ All tests achieve 80%+ coverage

---

## Test Generator Components

### 1. Entity Test Generator
- **File:** `src/Service/Generator/Test/EntityTestGenerator.php`
- **Output:** `tests/Entity/{Entity}Test.php`
- **Tests:** Property getters/setters, relationships, validation constraints, lifecycle callbacks

### 2. Repository Test Generator
- **File:** `src/Service/Generator/Test/RepositoryTestGenerator.php`
- **Output:** `tests/Repository/{Entity}RepositoryTest.php`
- **Tests:** search(), findBy(), createFilteredQueryBuilder(), custom queries

### 3. Controller Test Generator
- **File:** `src/Service/Generator/Test/ControllerTestGenerator.php`
- **Output:** `tests/Controller/{Entity}ControllerTest.php`
- **Tests:** index, show, create, update, delete actions, security, Turbo responses

### 4. Voter Test Generator
- **File:** `src/Service/Generator/Test/VoterTestGenerator.php`
- **Output:** `tests/Security/Voter/{Entity}VoterTest.php`
- **Tests:** VIEW, EDIT, DELETE permissions for different roles

### 5. Fixture Data Generator
- **File:** `src/Service/Generator/Test/FixtureDataGenerator.php`
- **Purpose:** Generate realistic test data using Faker based on property types
- **Integration:** Used by all test generators for fixture creation

---

## Implementation Example: Controller Test Generator

```php
<?php

namespace App\Service\Generator\Test;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Twig\Environment;

class ControllerTestGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig
    ) {}

    public function generate(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/tests/Controller/%sControllerTest.php',
            $this->projectDir,
            $entity->entityName
        );

        $content = $this->twig->render('generator/test/controller_test.php.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
```

**Template Example:** `templates/generator/test/controller_test.php.twig`

```php
<?php

namespace App\Tests\Controller;

use App\Entity\{{ entity.entityName }};
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class {{ entity.entityName }}ControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Create test fixtures
        $this->loadFixtures();
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/{{ entity.getSnakeCaseName() }}');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '{{ entity.pluralLabel }}');
    }

    public function testShow(): void
    {
        ${{ entity.getLowercaseName() }} = $this->createTest{{ entity.entityName }}();

        $this->client->request('GET', '/{{ entity.getSnakeCaseName() }}/' . ${{ entity.getLowercaseName() }}->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testCreate(): void
    {
        $crawler = $this->client->request('GET', '/{{ entity.getSnakeCaseName() }}/new');

        $form = $crawler->selectButton('Save')->form([
{% for property in entity.properties if property.showInForm and not property.isRelationship %}
            '{{ entity.getLowercaseName() }}[{{ property.propertyName }}]' => '{{ '{{ faker.' ~ (property.fixtureType ?? 'text') ~ ' }}' }}',
{% endfor %}
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/{{ entity.getSnakeCaseName() }}');
    }

    public function testUpdate(): void
    {
        ${{ entity.getLowercaseName() }} = $this->createTest{{ entity.entityName }}();

        $crawler = $this->client->request('GET', '/{{ entity.getSnakeCaseName() }}/' . ${{ entity.getLowercaseName() }}->getId() . '/edit');

        $form = $crawler->selectButton('Update')->form([
{% for property in entity.properties|slice(0, 1) if property.showInForm %}
            '{{ entity.getLowercaseName() }}[{{ property.propertyName }}]' => 'Updated Value',
{% endfor %}
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/{{ entity.getSnakeCaseName() }}');
    }

    public function testDelete(): void
    {
        ${{ entity.getLowercaseName() }} = $this->createTest{{ entity.entityName }}();

        $this->client->request('DELETE', '/{{ entity.getSnakeCaseName() }}/' . ${{ entity.getLowercaseName() }}->getId());

        $this->assertResponseRedirects('/{{ entity.getSnakeCaseName() }}');

        $this->assertNull(
            $this->entityManager->getRepository({{ entity.entityName }}::class)
                ->find(${{ entity.getLowercaseName() }}->getId())
        );
    }

    private function createTest{{ entity.entityName }}(): {{ entity.entityName }}
    {
        ${{ entity.getLowercaseName() }} = new {{ entity.entityName }}();
{% for property in entity.properties if not property.isRelationship and not property.nullable %}
        ${{ entity.getLowercaseName() }}->set{{ property.propertyName|capitalize }}('Test Value');
{% endfor %}
{% if entity.hasOrganization %}
        ${{ entity.getLowercaseName() }}->setOrganization($this->getTestOrganization());
{% endif %}

        $this->entityManager->persist(${{ entity.getLowercaseName() }});
        $this->entityManager->flush();

        return ${{ entity.getLowercaseName() }};
    }

    private function loadFixtures(): void
    {
        // Clean database
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('{{ entity.getSnakeCaseName() }}', true));
    }
}
```

---

## Faker Integration for Realistic Test Data

**Fixture Type Mapping:**

| Property Type | Faker Method |
|---------------|--------------|
| `string` (name, firstName, lastName) | `name()` |
| `string` (email) | `email()` |
| `string` (phone, telephone) | `phoneNumber()` |
| `string` (address) | `address()` |
| `string` (city) | `city()` |
| `string` (company) | `company()` |
| `text` (description, notes) | `paragraph()` |
| `integer` | `numberBetween(1, 100)` |
| `decimal` | `randomFloat(2, 0, 1000)` |
| `boolean` | `boolean()` |
| `date` | `date()` |
| `datetime` | `dateTime()` |

---

## Phase 4 Deliverables Checklist

- [ ] Entity Test Generator implemented
- [ ] Repository Test Generator implemented
- [ ] Controller Test Generator implemented
- [ ] Voter Test Generator implemented
- [ ] Fixture Data Generator with Faker
- [ ] All test generators produce passing tests
- [ ] 80%+ coverage achieved
- [ ] PHPStan level 8 passes on test code

---

## Next Phase

**Phase 5: CLI & Orchestrator** (Week 6)
- Symfony Console command
- Generator orchestration service
- Progress reporting
- Error handling
