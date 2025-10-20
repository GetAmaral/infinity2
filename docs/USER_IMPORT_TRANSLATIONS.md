# User Import - Translation Reference

> **Status**: ✅ Complete - All validation error messages are now fully translatable in both English and Portuguese

---

## 📋 Overview

All validation error messages in the User Import feature have been internationalized. The system automatically uses the appropriate language based on the user's locale preference.

---

## 🌍 Supported Languages

- **English (en)**: Default language
- **Portuguese (pt_BR)**: Full translation support

---

## 🔧 Translation Keys

### Email Validation

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.email_required` | Email is required | Email é obrigatório |
| `user.import.validation.email_invalid` | Invalid email format | Formato de email inválido |
| `user.import.validation.email_duplicate_same_org` | Email already registered in your organization: '%email%' (User: %user%) | Email já registrado na sua organização: '%email%' (Usuário: %user%) |
| `user.import.validation.email_duplicate_other_org` | Email already registered in another organization: '%email%' (User: %user% in %org%) | Email já registrado em outra organização: '%email%' (Usuário: %user% em %org%) |
| `user.import.validation.unknown_org` | Unknown Organization | Organização Desconhecida |

### Name Validation

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.name_required` | Name is required | Nome é obrigatório |
| `user.import.validation.name_min_length` | Name must be at least 2 characters | Nome deve ter pelo menos 2 caracteres |
| `user.import.validation.name_max_length` | Name must not exceed 255 characters | Nome não pode exceder 255 caracteres |

### Password Validation

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.password_required` | Password is required | Senha é obrigatória |
| `user.import.validation.password_min_length` | Password must be at least 6 characters | Senha deve ter pelo menos 6 caracteres |

### Role Validation

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.role_not_found` | Role not found: %role% | Permissão não encontrada: %role% |

### Organization Validation

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.no_organization` | No organization context available | Nenhum contexto de organização disponível |

### Database Errors

| Translation Key | English | Portuguese (pt_BR) |
|----------------|---------|-------------------|
| `user.import.validation.database_duplicate` | Email already exists in database (duplicate) | Email já existe no banco de dados (duplicado) |

---

## 💡 How It Works

### 1. Automatic Locale Detection

The system uses the user's browser language or profile settings to determine which language to use. This is handled automatically by Symfony's translation component.

### 2. Fallback Mechanism

If a translation is missing in the user's preferred language, the system automatically falls back to English (default locale).

### 3. Parameter Substitution

Translation messages support dynamic parameters:

```php
// Example with parameters
$this->translator->trans('user.import.validation.email_duplicate_same_org', [
    '%email%' => 'john@example.com',
    '%user%' => 'John Doe',
], 'user');

// Output (English):
// "Email already registered in your organization: 'john@example.com' (User: John Doe)"

// Output (Portuguese):
// "Email já registrado na sua organização: 'john@example.com' (Usuário: John Doe)"
```

---

## 📊 Example Error Messages

### English (en)

```
❌ Error #1 (Row 2):
   Email: teste@luminai.ai
   Errors:
     - Email already registered in another organization: 'teste@luminai.ai'
       (User: John Doe in Acme Corp)

❌ Error #2 (Row 3):
   Email: invalid-email
   Errors:
     - Invalid email format

❌ Error #3 (Row 4):
   Email: test@example.com
   Name: T
   Errors:
     - Name must be at least 2 characters

❌ Error #4 (Row 5):
   Email: test2@example.com
   Errors:
     - Password must be at least 6 characters

❌ Error #5 (Row 6):
   Email: test3@example.com
   Errors:
     - Role not found: admin_invalid
```

### Portuguese (pt_BR)

```
❌ Erro #1 (Linha 2):
   Email: teste@luminai.ai
   Erros:
     - Email já registrado em outra organização: 'teste@luminai.ai'
       (Usuário: John Doe em Acme Corp)

❌ Erro #2 (Linha 3):
   Email: invalid-email
   Erros:
     - Formato de email inválido

❌ Erro #3 (Linha 4):
   Email: test@example.com
   Nome: T
   Erros:
     - Nome deve ter pelo menos 2 caracteres

❌ Erro #4 (Linha 5):
   Email: test2@example.com
   Erros:
     - Senha deve ter pelo menos 6 caracteres

❌ Erro #5 (Linha 6):
   Email: test3@example.com
   Erros:
     - Permissão não encontrada: admin_invalid
```

---

## 🔧 Implementation Details

### Files Modified

| File | Changes |
|------|---------|
| `app/src/Service/UserImportService.php` | Added `TranslatorInterface` dependency, replaced all hardcoded error messages with translation calls |
| `app/translations/en/user.en.yaml` | Added 13 new translation keys for validation errors |
| `app/translations/pt_BR/user.pt_BR.yaml` | Added 13 new translation keys for validation errors (Portuguese) |

### Code Example

**Before** (Hardcoded):
```php
if (empty($userData['email'])) {
    $errors[] = 'Email is required';
}
```

**After** (Translatable):
```php
if (empty($userData['email'])) {
    $errors[] = $this->translator->trans('user.import.validation.email_required', [], 'user');
}
```

---

## 🌐 Adding New Languages

To add support for a new language (e.g., Spanish):

### 1. Create Translation File

```bash
touch app/translations/es/user.es.yaml
```

### 2. Copy English Keys

```yaml
# app/translations/es/user.es.yaml

# Importación de Usuarios - Mensajes de Error de Validación
user.import.validation.email_required: El email es obligatorio
user.import.validation.email_invalid: Formato de email inválido
user.import.validation.email_duplicate_same_org: "Email ya registrado en su organización: '%email%' (Usuario: %user%)"
# ... etc
```

### 3. Clear Cache

```bash
docker-compose exec -T app php bin/console cache:clear
```

### 4. Test

The new language will be automatically available to users whose browser or profile is set to that locale.

---

## 🧪 Testing Translations

### Manual Testing

1. **Change Browser Language**:
   - Set browser to Portuguese: `pt-BR`
   - System will automatically use Portuguese translations

2. **Test Import with Errors**:
   - Upload an XLSX file with validation errors
   - Verify error messages appear in the correct language

### Programmatic Testing

```php
// Test translation service directly
$translator = $container->get('translator');

// Set locale to Portuguese
$translator->setLocale('pt_BR');

// Test translation
$message = $translator->trans('user.import.validation.email_required', [], 'user');
// Output: "Email é obrigatório"

// Set locale back to English
$translator->setLocale('en');

$message = $translator->trans('user.import.validation.email_required', [], 'user');
// Output: "Email is required"
```

---

## ✅ Validation Coverage

All validation error messages are now translatable:

- ✅ **Email Validation** (5 messages)
  - Required check
  - Format validation
  - Duplicate detection (same org)
  - Duplicate detection (other org)
  - Unknown organization fallback

- ✅ **Name Validation** (3 messages)
  - Required check
  - Minimum length
  - Maximum length

- ✅ **Password Validation** (2 messages)
  - Required check
  - Minimum length

- ✅ **Role Validation** (1 message)
  - Role existence check

- ✅ **Organization Validation** (1 message)
  - Organization context check

- ✅ **Database Errors** (1 message)
  - Duplicate constraint violation

**Total**: 13 fully translated validation messages

---

## 📝 Best Practices

### For Translators

1. **Maintain Consistency**: Use the same terminology across all translations
2. **Parameter Names**: Keep parameter names (e.g., `%email%`, `%user%`) unchanged
3. **Punctuation**: Adapt punctuation to the target language's conventions
4. **Context**: Consider the context in which the message appears
5. **Test**: Always test translations with real data

### For Developers

1. **Never Hardcode**: Always use translation keys instead of hardcoding strings
2. **Meaningful Keys**: Use descriptive translation keys (e.g., `user.import.validation.email_required`)
3. **Parameters**: Use parameters for dynamic content (e.g., email addresses, user names)
4. **Default Locale**: Always provide English (en) as the default translation
5. **Cache Clear**: Remember to clear cache after adding new translations

---

## 🎯 Locale Priority

The system determines the locale in the following order:

1. **User Profile Locale**: If user has a saved language preference
2. **Session Locale**: If a locale was set during the current session
3. **Browser Accept-Language**: Based on browser's language settings
4. **Default Locale**: Falls back to English (en) if no other locale matches

---

## 📞 Support

### Adding Translations

To add or modify translations:

1. Edit the appropriate YAML file:
   - English: `app/translations/en/user.en.yaml`
   - Portuguese: `app/translations/pt_BR/user.pt_BR.yaml`

2. Clear cache:
   ```bash
   php bin/console cache:clear
   ```

3. Test the changes in the application

### Reporting Issues

If you find translation errors or missing translations, please report them with:
- Translation key
- Current translation
- Suggested translation
- Language code (en, pt_BR, etc.)

---

**Last Updated**: 2025-10-19
**Languages**: 2 (English, Portuguese)
**Translation Keys**: 13 validation messages
**Status**: ✅ Production Ready
