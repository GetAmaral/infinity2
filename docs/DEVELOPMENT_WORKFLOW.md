# Development Workflow Guide

Complete guide to common development tasks in Luminai.

---

## Adding New Entity

### Step 1: Generate Entity

```bash
php bin/console make:entity EntityName --no-interaction
```

### Step 2: Configure Entity with UUIDv7

Edit `src/Entity/EntityName.php`:

```php
<?php
namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EntityNameRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class EntityName
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    protected Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```

### Step 3: Create Migration

```bash
php bin/console make:migration --no-interaction
```

### Step 4: Execute Migration

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Step 5: Create Fixtures

Create `src/DataFixtures/EntityNameFixtures.php`:

```php
<?php
namespace App\DataFixtures;

use App\Entity\EntityName;
use App\Entity\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EntityNameFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organizations = $manager->getRepository(Organization::class)->findAll();

        foreach ($organizations as $organization) {
            for ($i = 1; $i <= 5; $i++) {
                $entity = new EntityName();
                $entity->setName("Entity $i for {$organization->getName()}");
                $entity->setOrganization($organization);

                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrganizationFixtures::class];
    }
}
```

### Step 6: Write Tests

Create `tests/Entity/EntityNameTest.php`:

```php
<?php
namespace App\Tests\Entity;

use App\Entity\EntityName;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityNameTest extends KernelTestCase
{
    public function testEntityHasUuidV7Id(): void
    {
        $entity = new EntityName();

        self::assertInstanceOf(\DateTimeImmutable::class, $entity->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $entity->getUpdatedAt());
    }
}
```

---

## Adding New Controller

### Step 1: Generate Controller

```bash
php bin/console make:controller EntityNameController --no-interaction
```

### Step 2: Add Routes and Actions

Edit `src/Controller/EntityNameController.php`:

```php
<?php
namespace App\Controller;

use App\Entity\EntityName;
use App\Form\EntityNameType;
use App\Repository\EntityNameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/entity-name')]
class EntityNameController extends AbstractController
{
    #[Route('/', name: 'entity_name_index', methods: ['GET'])]
    public function index(EntityNameRepository $repository): Response
    {
        $entities = $repository->findAll();

        return $this->render('entity_name/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    #[Route('/new', name: 'entity_name_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $entity = new EntityName();
        $form = $this->createForm(EntityNameType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('entity_name_index');
        }

        return $this->render('entity_name/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'entity_name_show', methods: ['GET'])]
    public function show(EntityName $entity): Response
    {
        return $this->render('entity_name/show.html.twig', [
            'entity' => $entity,
        ]);
    }

    #[Route('/{id}/edit', name: 'entity_name_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityName $entity, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EntityNameType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('entity_name_index');
        }

        return $this->render('entity_name/edit.html.twig', [
            'entity' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'entity_name_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityName $entity, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirectToRoute('entity_name_index');
    }
}
```

### Step 3: Create Templates

Create `templates/entity_name/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Entity List{% endblock %}

{% block body %}
    <div class="luminai-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>
                <i class="bi bi-list me-2"></i>
                {{ 'entity.title.list'|trans }}
            </h1>

            <a href="{{ path('entity_name_new') }}" class="luminai-btn-primary">
                <i class="bi bi-plus-circle me-2"></i>
                {{ 'entity.button.create'|trans }}
            </a>
        </div>

        <table class="table luminai-table">
            <thead>
                <tr>
                    <th>{{ 'entity.field.name'|trans }}</th>
                    <th>{{ 'entity.field.created_at'|trans }}</th>
                    <th class="text-end">{{ 'entity.field.actions'|trans }}</th>
                </tr>
            </thead>
            <tbody>
                {% for entity in entities %}
                    <tr>
                        <td>{{ entity.name }}</td>
                        <td>{{ entity.createdAt|date('Y-m-d H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ path('entity_name_show', {id: entity.id}) }}" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ path('entity_name_edit', {id: entity.id}) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endblock %}
```

### Step 4: Update Navigation

Edit `templates/base.html.twig`:

```twig
<nav class="navbar navbar-expand-lg luminai-navbar">
    <div class="container-fluid">
        <!-- ... existing items ... -->

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <!-- ... existing items ... -->

                <li class="nav-item">
                    <a class="nav-link" href="{{ path('entity_name_index') }}">
                        <i class="bi bi-list me-1"></i>
                        {{ 'nav.entity_name'|trans }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
```

### Step 5: Write Controller Tests

Create `tests/Controller/EntityNameControllerTest.php`:

```php
<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EntityNameControllerTest extends WebTestCase
{
    public function testIndexPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/entity-name');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Entity List');
    }

    public function testCreateEntity(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/entity-name/new');
        $form = $crawler->selectButton('Save')->form([
            'entity_name[name]' => 'Test Entity',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/entity-name');
    }
}
```

---

## Adding Form Type

### Step 1: Generate Form

```bash
php bin/console make:form EntityNameType EntityName
```

### Step 2: Customize Form

Edit `src/Form/EntityNameType.php`:

```php
<?php
namespace App\Form;

use App\Entity\EntityName;
use App\Entity\Organization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('organization', EntityType::class, [
                'class' => Organization::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EntityName::class,
        ]);
    }
}
```

---

## Adding Security Voter

### Step 1: Generate Voter

```bash
php bin/console make:voter EntityNameVoter
```

### Step 2: Implement Voter Logic

Edit `src/Security/Voter/EntityNameVoter.php`:

```php
<?php
namespace App\Security\Voter;

use App\Entity\EntityName;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EntityNameVoter extends Voter
{
    public const LIST = 'ENTITY_NAME_LIST';
    public const CREATE = 'ENTITY_NAME_CREATE';
    public const VIEW = 'ENTITY_NAME_VIEW';
    public const EDIT = 'ENTITY_NAME_EDIT';
    public const DELETE = 'ENTITY_NAME_DELETE';

    public function __construct(
        private readonly Security $security
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::LIST,
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::LIST:
            case self::CREATE:
                return $this->security->isGranted('ROLE_ADMIN');

            case self::VIEW:
            case self::EDIT:
            case self::DELETE:
                return $this->canManage($user, $subject);
        }

        return false;
    }

    private function canManage(User $user, EntityName $entity): bool
    {
        // Super admin can do anything
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Organization admin can manage entities in their org
        if ($this->security->isGranted('ROLE_ORGANIZATION_ADMIN')) {
            return $entity->getOrganization()->getId()->equals($user->getOrganization()->getId());
        }

        return false;
    }
}
```

### Step 3: Use Voter in Controller

```php
use App\Security\Voter\EntityNameVoter;

#[Route('/entity-name', name: 'entity_name_index')]
public function index(): Response
{
    $this->denyAccessUnlessGranted(EntityNameVoter::LIST);

    // ...
}

#[Route('/entity-name/{id}/edit', name: 'entity_name_edit')]
public function edit(EntityName $entity): Response
{
    $this->denyAccessUnlessGranted(EntityNameVoter::EDIT, $entity);

    // ...
}
```

---

## Adding API Resource

### Step 1: Configure Entity for API Platform

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['entity:read']],
    denormalizationContext: ['groups' => ['entity:write']],
)]
class EntityName
{
    #[Groups(['entity:read'])]
    private Uuid $id;

    #[Groups(['entity:read', 'entity:write'])]
    private string $name;

    // ...
}
```

### Step 2: Test API Endpoints

```bash
# Get collection
curl -X GET https://localhost/api/entity_names

# Get item
curl -X GET https://localhost/api/entity_names/{id}

# Create (requires auth)
curl -X POST https://localhost/api/entity_names \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name": "New Entity"}'
```

---

## Adding Translations

### Step 1: Create Translation File

Create `translations/messages.en.yaml`:

```yaml
entity:
    title:
        list: 'Entity List'
        create: 'Create Entity'
        edit: 'Edit Entity'
    field:
        name: 'Name'
        description: 'Description'
        created_at: 'Created At'
    button:
        create: 'Create'
        save: 'Save'
        cancel: 'Cancel'
    flash:
        created: 'Entity created successfully'
        updated: 'Entity updated successfully'
        deleted: 'Entity deleted successfully'
```

### Step 2: Use Translations

```twig
{{ 'entity.title.list'|trans }}
{{ 'entity.button.create'|trans }}
{{ 'entity.flash.created'|trans }}
```

---

## Asset Management

### Adding JavaScript Package

```bash
# Add package via importmap
php bin/console importmap:require package-name

# Import in assets/app.js
echo "import 'package-name';" >> assets/app.js

# Clear cache
php bin/console cache:clear
php bin/console importmap:install
```

### Creating Stimulus Controller

```bash
# Create controller file
touch assets/controllers/my_controller.js
```

```javascript
// assets/controllers/my_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('Controller connected');
    }
}
```

---

## Testing

### Writing Unit Tests

```php
<?php
namespace App\Tests\Entity;

use App\Entity\EntityName;
use PHPUnit\Framework\TestCase;

class EntityNameTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $entity = new EntityName();
        $entity->setName('Test Name');

        self::assertSame('Test Name', $entity->getName());
    }
}
```

### Writing Functional Tests

```php
<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EntityNameControllerTest extends WebTestCase
{
    public function testListPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/entity-name');

        self::assertResponseIsSuccessful();
    }
}
```

### Running Tests

```bash
# All tests
php bin/phpunit

# Specific file
php bin/phpunit tests/Entity/EntityNameTest.php

# With coverage
php bin/phpunit --coverage-html coverage/
```

---

## Performance Monitoring

### Adding Performance Monitoring to Service

```php
use App\Service\PerformanceMonitor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MyService
{
    public function __construct(
        private readonly PerformanceMonitor $performanceMonitor,
        #[Autowire(service: 'monolog.logger.business')]
        private readonly LoggerInterface $businessLogger
    ) {}

    public function businessOperation(): void
    {
        $this->performanceMonitor->startTimer('my_operation');

        // Business logic here

        $this->performanceMonitor->endTimer('my_operation');

        $this->businessLogger->info('Operation completed', [
            'duration' => $this->performanceMonitor->getLastDuration('my_operation'),
        ]);
    }
}
```

---

## Quick Reference

### Common Entity Commands

```bash
php bin/console make:entity EntityName --no-interaction
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
```

### Common Controller Commands

```bash
php bin/console make:controller EntityNameController --no-interaction
php bin/console make:form EntityNameType EntityName
php bin/console make:voter EntityNameVoter
```

### Common Test Commands

```bash
php bin/phpunit
php bin/phpunit tests/Entity/
php bin/phpunit tests/Controller/
php bin/phpunit --coverage-html coverage/
```

---

For more information:
- [Database Guide](DATABASE.md)
- [Security Guide](SECURITY.md)
- [Frontend Guide](FRONTEND.md)
- [Quick Start Guide](QUICK_START.md)
