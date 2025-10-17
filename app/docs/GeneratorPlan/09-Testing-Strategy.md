# Testing Strategy

## Overview

Comprehensive testing strategy for the Luminai Code Generator ensuring quality, reliability, and maintainability.

**Test Coverage Target:** 80%+

**Test Levels:**
1. Unit Tests (isolated component testing)
2. Integration Tests (component interaction)
3. Functional Tests (end-to-end workflows)
4. Performance Tests (speed and scalability)
5. Manual Tests (user acceptance)

---

## Unit Tests

### CSV Parser & Validator Tests

**File:** `tests/Service/Generator/Csv/CsvParserServiceTest.php`

**Test Cases:**
- ✅ Parse valid Entity.csv
- ✅ Parse valid Property.csv
- ✅ Handle missing files
- ✅ Handle malformed CSV
- ✅ Handle empty rows
- ✅ Boolean parsing (true/false/1/0/yes/no)
- ✅ JSON field parsing
- ✅ CSV list parsing (comma-separated)

**File:** `tests/Service/Generator/Csv/CsvValidatorServiceTest.php`

**Test Cases:**
- ✅ Validate entity required fields
- ✅ Validate property required fields
- ✅ Detect duplicate entity names
- ✅ Detect duplicate property names
- ✅ Validate relationship integrity
- ✅ Validate self-referential relationships
- ✅ Validate index configuration
- ✅ Detect circular dependencies

---

### Generator Tests

**Entity Generator Tests:**
- ✅ Generate entity base class with properties
- ✅ Generate entity extension class once
- ✅ Import OrganizationTrait when hasOrganization=true
- ✅ Handle all Doctrine property types
- ✅ Generate relationship methods correctly
- ✅ Generate collection add/remove methods
- ✅ Generate __toString() method

**Repository Generator Tests:**
- ✅ Generate repository base class
- ✅ Generate repository extension class once
- ✅ Include searchable fields
- ✅ Include search() method
- ✅ Include createFilteredQueryBuilder() method
- ✅ Handle custom queries in extension

**Controller Generator Tests:**
- ✅ Generate controller base class with CRUD actions
- ✅ Generate controller extension class once
- ✅ Include Turbo Stream responses
- ✅ Include organization context integration
- ✅ Include voter authorization checks
- ✅ Include flash messages

**Voter Generator Tests:**
- ✅ Generate voter base class
- ✅ Generate voter extension class once
- ✅ Include VIEW, EDIT, DELETE attributes
- ✅ Include organization ownership check
- ✅ Include role-based permissions

**Form Generator Tests:**
- ✅ Generate form base class
- ✅ Generate form extension class once
- ✅ Map Doctrine types to form types correctly
- ✅ Include validation constraints
- ✅ Exclude organization field (hidden)
- ✅ Handle EntityType for relationships

---

### Template Generator Tests

- ✅ Generate index.html.twig with table
- ✅ Include search and filter UI
- ✅ Include sortable columns
- ✅ Include pagination
- ✅ Generate form.html.twig with proper field types
- ✅ Generate show.html.twig with all fields
- ✅ Generate Turbo Stream templates

---

### Navigation Generator Tests

- ✅ Inject menu items between markers
- ✅ Group entities by navGroup
- ✅ Sort by navOrder
- ✅ Preserve custom menu items
- ✅ Handle missing markers gracefully

---

### Translation Generator Tests

- ✅ Generate entity labels (singular/plural)
- ✅ Generate field labels
- ✅ Merge with existing translations
- ✅ Preserve custom translations
- ✅ Generate humanized labels from camelCase

---

## Integration Tests

### Complete Generation Flow

**File:** `tests/Integration/CompleteGenerationTest.php`

**Test Scenario:**
1. Parse CSV files
2. Validate data
3. Create backup
4. Generate all components
5. Verify all files created
6. Verify navigation updated
7. Verify translations updated
8. Run generated tests
9. Restore from backup

**Success Criteria:**
- All files generated correctly
- All relationships intact
- All tests pass
- Backup/restore works

---

### Multi-Entity Relationships

**File:** `tests/Integration/RelationshipTest.php`

**Test Scenario:**
1. Generate Contact entity (ManyToOne → Company)
2. Generate Company entity (OneToMany → Contact)
3. Verify bidirectional relationship
4. Test cascade operations
5. Test orphan removal

**Success Criteria:**
- Relationship methods exist
- Inverse side configured
- Cascade works
- Orphan removal works

---

## Functional Tests

### Generated Controller Tests

**File:** `tests/Functional/GeneratedControllerTest.php`

**Test Cases:**
- ✅ Index action loads
- ✅ Show action displays entity
- ✅ Create action saves entity
- ✅ Update action modifies entity
- ✅ Delete action removes entity
- ✅ Search works
- ✅ Filter works
- ✅ Sort works
- ✅ Pagination works
- ✅ Turbo Stream responses work
- ✅ Organization isolation works
- ✅ Voter authorization works

---

### Generated API Tests

**File:** `tests/Functional/GeneratedApiTest.php`

**Test Cases:**
- ✅ GET /api/{entities} returns collection
- ✅ GET /api/{entities}/{id} returns single
- ✅ POST /api/{entities} creates
- ✅ PUT /api/{entities}/{id} updates
- ✅ PATCH /api/{entities}/{id} partial updates
- ✅ DELETE /api/{entities}/{id} deletes
- ✅ Pagination works
- ✅ Filters work
- ✅ Authorization works
- ✅ Organization isolation works

---

## Performance Tests

### Generation Performance

**Test:** Generation time for all entities

```php
public function testGenerationPerformance(): void
{
    $startTime = microtime(true);

    $this->orchestrator->generate();

    $duration = microtime(true) - $startTime;

    $this->assertLessThan(120, $duration, 'Generation should complete in < 2 minutes');
}
```

**Target:** < 2 minutes for 66 entities

---

### Query Performance

**Test:** Repository search performance

```php
public function testSearchPerformance(): void
{
    $repository = $this->entityManager->getRepository(Contact::class);

    $startTime = microtime(true);

    $results = $repository->search('test', 100);

    $duration = (microtime(true) - $startTime) * 1000; // ms

    $this->assertLessThan(100, $duration, 'Search should complete in < 100ms');
}
```

**Target:** < 100ms for search queries

---

### API Response Time

**Test:** API endpoint performance

```bash
ab -n 1000 -c 10 https://localhost/api/contacts
```

**Target:**
- Mean response time: < 150ms
- 95th percentile: < 300ms
- Throughput: > 100 req/s

---

## Manual Testing Checklist

### Pre-Generation Testing

- [ ] CSV files validate without errors
- [ ] Backup directory writable
- [ ] Database accessible
- [ ] Sufficient disk space
- [ ] Git repository clean

---

### Post-Generation Testing

**Per Entity (sample 5):**

**CRUD Operations:**
- [ ] Create new entity via form
- [ ] Fields validate correctly
- [ ] Entity appears in list
- [ ] Entity details show correctly
- [ ] Edit updates entity
- [ ] Delete removes entity
- [ ] Flash messages appear
- [ ] Turbo navigation works

**Search & Filter:**
- [ ] Search finds entities
- [ ] Filter narrows results
- [ ] Sort changes order
- [ ] Pagination works
- [ ] Results accurate

**API:**
- [ ] GET collection works
- [ ] GET single works
- [ ] POST creates
- [ ] PUT updates
- [ ] DELETE removes
- [ ] Filters work
- [ ] Pagination works

**Security:**
- [ ] Voter prevents unauthorized access
- [ ] Organization isolation enforced
- [ ] Roles respected
- [ ] Admin override works

---

### Cross-Browser Testing

Test in:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari/WebKit
- [ ] Mobile browsers

**Verify:**
- [ ] Turbo Drive works
- [ ] Forms submit correctly
- [ ] Modals display
- [ ] Tables responsive

---

## Test Execution

### Local Development

```bash
# Run all tests
php bin/phpunit

# Run specific suite
php bin/phpunit --testsuite=unit
php bin/phpunit --testsuite=integration
php bin/phpunit --testsuite=functional

# Run with coverage
php bin/phpunit --coverage-html coverage/

# Run specific test
php bin/phpunit tests/Service/Generator/Entity/EntityGeneratorTest.php
```

---

### CI/CD Pipeline

```yaml
# .github/workflows/test.yml
name: Test Suite

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: xdebug

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: php bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: coverage.xml
```

---

## Test Data Management

### Fixtures

**File:** `src/DataFixtures/GeneratorTestFixtures.php`

```php
<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class GeneratorTestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Create test organization
        $org = new Organization();
        $org->setName('Test Organization');
        $org->setSlug('test-org');
        $manager->persist($org);

        // Create 50 contacts for testing
        for ($i = 0; $i < 50; $i++) {
            $contact = new Contact();
            $contact->setName($faker->name);
            $contact->setEmail($faker->email);
            $contact->setPhone($faker->phoneNumber);
            $contact->setOrganization($org);
            $manager->persist($contact);
        }

        $manager->flush();
    }
}
```

**Load Fixtures:**
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

---

### Database Reset

```bash
# Drop and recreate database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

---

## Debugging Tests

### Enable Debug Mode

```bash
# Run with verbose output
php bin/phpunit --debug --verbose

# Stop on failure
php bin/phpunit --stop-on-failure

# Filter specific test
php bin/phpunit --filter testEntityGeneration
```

---

### Profiling Tests

```bash
# Profile test execution
php -d xdebug.mode=profile bin/phpunit

# View profile
php -r "print_r(xdebug_get_profiler_filename());"
```

---

## Test Maintenance

### Regular Tasks

**Weekly:**
- [ ] Run full test suite
- [ ] Review test coverage
- [ ] Fix failing tests
- [ ] Update test data

**Monthly:**
- [ ] Review test performance
- [ ] Refactor slow tests
- [ ] Update fixtures
- [ ] Review test documentation

**After Changes:**
- [ ] Run affected tests
- [ ] Update test expectations
- [ ] Add new test cases
- [ ] Verify coverage maintained

---

## Success Criteria Summary

### Code Coverage
- ✅ Overall: 80%+
- ✅ Generators: 90%+
- ✅ Generated code: 75%+

### Test Execution
- ✅ All tests pass
- ✅ Full suite < 10 minutes
- ✅ Zero flaky tests

### Performance
- ✅ Generation < 2 minutes
- ✅ Queries < 100ms
- ✅ API < 150ms

### Quality
- ✅ PHPStan level 8
- ✅ Zero security issues
- ✅ Zero deprecation warnings

---

**TESTING STRATEGY COMPLETE ✅**
