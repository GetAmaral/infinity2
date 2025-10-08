# TURBO Generator - Quick Start Guide

**Generate complete CRUD applications from CSV files in minutes!**

---

## 🚀 What It Does

The TURBO Generator creates 17+ files per entity from CSV definitions:

- ✅ **Entity classes** (with OrganizationTrait for multi-tenancy)
- ✅ **Repositories** (with search/filter methods)
- ✅ **Controllers** (full CRUD + Turbo support)
- ✅ **Security Voters** (authorization logic)
- ✅ **Form Types** (Bootstrap 5 styled)
- ✅ **Twig Templates** (index, form, show)
- ✅ **API Platform configs** (REST API)
- ✅ **Navigation** (automatic menu updates)
- ✅ **Translations** (i18n labels)
- ✅ **PHPUnit Tests** (entity, repository, controller, voter)

---

## ⚡ Quick Start (3 Steps)

### Step 1: Define Your Entities

Edit `config/EntityNew.csv`:

```csv
entityName,entityLabel,pluralLabel,icon,description,hasOrganization,apiEnabled,operations,security,...
Product,Product,Products,bi-box,Manages products,true,true,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_USER'),...
```

**Key Columns:**
- `entityName` - Class name (PascalCase): `Product`, `OrderItem`
- `hasOrganization` - Multi-tenant? `true`/`false`
- `apiEnabled` - REST API? `true`/`false`
- `voterEnabled` - Security? `true`/`false`
- `menuGroup` - Navigation: `Catalog`, `Sales`, `Admin`

### Step 2: Define Your Properties

Edit `config/PropertyNew.csv`:

```csv
entityName,propertyName,propertyLabel,propertyType,nullable,length,unique,validationRules,formType,...
Product,name,Name,string,false,255,false,"NotBlank,Length(min=2)",TextType,...
Product,price,Price,decimal,false,,false,"NotBlank,Positive",MoneyType,...
Product,description,Description,text,true,,false,"Length(max=1000)",TextareaType,...
```

**Key Columns:**
- `propertyName` - Property name (camelCase): `name`, `createdAt`
- `propertyType` - Doctrine type: `string`, `integer`, `datetime`, `boolean`
- `nullable` - Allow NULL? `true`/`false`
- `validationRules` - Constraints: `NotBlank,Email,Length(min=5)`
- `relationshipType` - Relations: `ManyToOne`, `OneToMany`, `ManyToMany`

### Step 3: Generate Code

```bash
# Preview what will be generated
php bin/console app:generate-from-csv --dry-run

# Generate everything
php bin/console app:generate-from-csv

# Create database migration
php bin/console make:migration

# Apply migration
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
php bin/console cache:clear

# Done! Visit https://localhost/product
```

---

## 📋 Common Patterns

### Simple Entity

```csv
# EntityNew.csv
Contact,Contact,Contacts,bi-person,Manages contacts,true,true,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_USER'),contact:read,contact:write,true,30,"{""name"": ""asc""}","name,email",,true,"VIEW,EDIT,DELETE",,,,CRM,10,true

# PropertyNew.csv (name field)
Contact,name,Name,string,false,255,,false,,"NotBlank,Length(min=2)",,TextType,{},true,false,,true,true,true,true,true,false,true,true,contact:read,contact:write,,,name,{}

# PropertyNew.csv (email field)
Contact,email,Email,string,false,255,,true,,"NotBlank,Email",,EmailType,{},true,false,,true,true,true,true,true,true,true,true,contact:read,contact:write,,,email,"{""unique"": true}"
```

### ManyToOne Relationship

```csv
# PropertyNew.csv - Product belongs to Category
Product,category,Category,,false,,,false,,NotBlank,ManyToOne,Category,,,,,,,"{"class": "App\\Entity\\Category", "choice_label": "name"}",EntityType,true,false,,true,true,true,false,false,false,true,false,,,,,
```

### OneToMany Relationship

```csv
# PropertyNew.csv - Category has many Products
Category,products,Products,,true,,,false,,,OneToMany,Product,category,,,,,,,CollectionType,false,true,,false,true,false,false,false,false,false,false,,,,,
```

---

## 🛠️ Useful Commands

```bash
# Generate single entity
php bin/console app:generate-from-csv --entity=Product

# Validate CSV files
php scripts/verify-csv-migration.php

# Check system readiness
php scripts/pre-generation-check.php

# Batch generation
php scripts/batch-generate.php --batch=10

# Run tests
php bin/phpunit

# View statistics
php scripts/generation-stats.php
```

---

## 📖 CSV Column Quick Reference

### EntityNew.csv Essential Columns

| Column | Example | Required |
|--------|---------|----------|
| `entityName` | `Product` | ✅ |
| `entityLabel` | `Product` | ✅ |
| `pluralLabel` | `Products` | ✅ |
| `icon` | `bi-box` | ✅ |
| `hasOrganization` | `true` | |
| `apiEnabled` | `true` | |
| `voterEnabled` | `true` | |
| `menuGroup` | `Catalog` | |
| `menuOrder` | `10` | |

### PropertyNew.csv Essential Columns

| Column | Example | Required |
|--------|---------|----------|
| `entityName` | `Product` | ✅ |
| `propertyName` | `name` | ✅ |
| `propertyLabel` | `Product Name` | ✅ |
| `propertyType` | `string` | ✅ |
| `nullable` | `false` | |
| `length` | `255` | for strings |
| `unique` | `true` | |
| `validationRules` | `NotBlank,Length(min=2)` | |
| `relationshipType` | `ManyToOne` | for relations |
| `targetEntity` | `Category` | for relations |

---

## 🔍 Troubleshooting

**CSV Validation Errors?**
```bash
php scripts/verify-csv-migration.php
```

**Generation Failed?**
```bash
# Check logs
tail -f var/log/dev.log

# Try dry-run first
php bin/console app:generate-from-csv --dry-run
```

**Migration Errors?**
```bash
# Check migration status
php bin/console doctrine:migrations:status

# Rollback if needed
php bin/console doctrine:migrations:migrate prev
```

**Tests Failing?**
```bash
# Clear test cache
php bin/console cache:clear --env=test

# Reload fixtures
php bin/console doctrine:fixtures:load --env=test
```

---

## 📚 Full Documentation

- **Complete Guide:** `docs/GeneratorUserGuide.md` (650+ lines)
- **Developer Guide:** `docs/GeneratorDeveloperGuide.md` (800+ lines)
- **Deployment:** `docs/ProductionDeployment.md` (750+ lines)
- **Cheat Sheets:** `docs/CheatSheets.md` (550+ lines)

---

## ✨ Generated File Structure

After generation, you'll have:

```
src/
├── Entity/
│   ├── Generated/
│   │   └── ProductGenerated.php      ← Always regenerated
│   └── Product.php                   ← Safe to customize
├── Repository/
│   ├── Generated/
│   │   └── ProductRepositoryGenerated.php
│   └── ProductRepository.php         ← Add custom queries here
├── Controller/
│   ├── Generated/
│   │   └── ProductControllerGenerated.php
│   └── ProductController.php         ← Add custom actions here
├── Security/Voter/
│   ├── Generated/
│   │   └── ProductVoterGenerated.php
│   └── ProductVoter.php              ← Customize authorization
└── Form/
    ├── Generated/
    │   └── ProductTypeGenerated.php
    └── ProductType.php               ← Customize form fields

templates/
└── product/
    ├── index.html.twig               ← List view
    ├── form.html.twig                ← Create/Edit form
    └── show.html.twig                ← Detail view

config/api_platform/
└── Product.yaml                      ← API configuration

tests/
├── Entity/ProductTest.php
├── Repository/ProductRepositoryTest.php
├── Controller/ProductControllerTest.php
└── Security/Voter/ProductVoterTest.php
```

**Generated files** (in `Generated/` folders) are always overwritten.
**Extension files** (outside `Generated/`) are created once and safe to customize.

---

## 🎯 Pro Tips

1. **Always run dry-run first** to preview changes
2. **Use hasOrganization=true** for multi-tenant data
3. **Enable voterEnabled=true** for authorization
4. **Group entities in menuGroup** for organized navigation
5. **Add searchableFields** for search functionality
6. **Run tests after generation** to catch issues early
7. **Commit CSV changes to git** for version control
8. **Backup before bulk generation** (automatic)

---

## 🚀 Next Steps

1. **Try the examples above** with your own entities
2. **Read the full guides** in `docs/` directory
3. **Run the test suite** to verify everything works
4. **Deploy to production** using `docs/ProductionDeployment.md`

**Happy generating!** 🎉
