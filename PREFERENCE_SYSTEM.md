# Infinity Preference System

## Overview

**PreferenceManager** is the single centralized system for managing ALL user preferences in Infinity. It handles both:
- **UserPreferences**: theme, locale, animations, sound, compact_mode, etc.
- **ListPreferences**: view mode (grid/list/table), sort, search, pagination per entity

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   PreferenceManager                     │
│                (preference-manager.js)                  │
├─────────────────────────────────────────────────────────┤
│  Full Page Load:                                        │
│    Database → localStorage + apply                      │
│                                                         │
│  Turbo Navigation:                                      │
│    localStorage → apply (instant)                       │
│                                                         │
│  User Changes:                                          │
│    localStorage + Database API (immediate + debounced) │
└─────────────────────────────────────────────────────────┘
```

## Data Flow

### 1. Full Page Load
```javascript
// base.html.twig on DOMContentLoaded
await window.PreferenceManager.init(false);

// Flow:
1. Fetch from /settings/ajax/preferences (UserPreferences)
2. Fetch from /settings/ajax/list-preferences (ListPreferences)
3. Merge both → preferences object
4. Save to localStorage (key: 'infinity_preferences')
5. Apply theme and UI settings
```

### 2. Turbo Navigation
```javascript
// base.html.twig on turbo:load
await window.PreferenceManager.init(true);

// Flow:
1. Load from localStorage (instant, no API calls)
2. Apply theme and UI settings
```

### 3. User Changes Setting
```javascript
// Settings page or any preference change
await window.PreferenceManager.setUserPreference('theme', 'light');

// Flow:
1. Save to localStorage immediately (for instant UI update)
2. Apply UI change (theme switch)
3. Send POST to /settings/ajax/preferences (async)
```

### 4. User Changes List View
```javascript
// List page (organizations/users) view toggle or search
await window.PreferenceManager.setListPreference('organizations', 'view', 'table');

// Flow:
1. Save to localStorage immediately
2. Update UI immediately
3. Queue API save (debounced 500ms)
4. Send POST to /settings/ajax/list-preferences/organizations
```

## API Structure

### Unified Preference Object
```javascript
{
  user: {
    theme: 'dark',
    locale: 'en',
    animations_enabled: true,
    sound_enabled: true,
    compact_mode: false,
    items_per_page: 25,
    // ... other user preferences
  },
  list: {
    organizations: {
      view: 'grid',
      sortColumn: null,
      sortDirection: 'asc',
      searchTerm: '',
      itemsPerPage: 10,
      currentPage: 1
    },
    users: {
      view: 'list',
      sortColumn: 'name',
      sortDirection: 'asc',
      searchTerm: 'john',
      itemsPerPage: 25,
      currentPage: 2
    }
  }
}
```

## Usage Examples

### Get User Preference
```javascript
const theme = window.PreferenceManager.getUserPreference('theme', 'dark');
```

### Set User Preference
```javascript
await window.PreferenceManager.setUserPreference('theme', 'light');
```

### Get Entity List Preferences
```javascript
const orgPrefs = window.PreferenceManager.getEntityPreferences('organizations');
// Returns: { view: 'grid', sortColumn: null, ... }
```

### Set Single List Preference
```javascript
await window.PreferenceManager.setListPreference('users', 'view', 'table');
```

### Set Multiple List Preferences
```javascript
await window.PreferenceManager.setEntityPreferences('organizations', {
  view: 'grid',
  sortColumn: 'name',
  sortDirection: 'desc'
});
```

## Integration Points

### 1. base.html.twig
- Loads `preference-manager.js` first
- Initializes on DOMContentLoaded (full load)
- Initializes on turbo:load (Turbo navigation)
- Auto-saves theme changes via GlobalTheme wrapper

### 2. Settings Page
- Uses PreferenceManager for theme switching
- Uses PreferenceManager for UI preference changes
- All changes saved to both localStorage + database

### 3. List Controllers (view_toggle_controller.js)
- Uses PreferenceManager via backward-compatible ListPreferences wrapper
- Saves view/sort/search preferences immediately
- Preferences persist across page refreshes and Turbo navigation

### 4. Backend APIs
- `/settings/ajax/preferences` - User preferences (GET/POST)
- `/settings/ajax/list-preferences` - All list preferences (GET/DELETE)
- `/settings/ajax/list-preferences/{entityName}` - Entity-specific (GET/POST)

## Backward Compatibility

Old code using `ListPreferences` or `UserPreferences` still works via wrapper:

```javascript
// Old way (still works)
const prefs = window.ListPreferences.getEntityPreferences('users');

// New way (preferred)
const prefs = window.PreferenceManager.getEntityPreferences('users');
```

## Testing

1. **Full Page Load Test**
   - Clear localStorage
   - Login
   - Check: Preferences loaded from database
   - Check: localStorage populated

2. **Turbo Navigation Test**
   - Navigate between pages
   - Check: No API calls
   - Check: Preferences loaded from localStorage instantly

3. **Setting Change Test**
   - Change theme in Settings page
   - Check: localStorage updated immediately
   - Check: API called
   - Refresh page
   - Check: Theme persists

4. **List Preference Test**
   - Switch view mode (grid/list/table)
   - Check: localStorage updated immediately
   - Check: API called (debounced)
   - Navigate away and back
   - Check: View mode persists

## Benefits

✅ **Single Source of Truth** - One manager for all preferences
✅ **No Duplication** - All preference code in one place
✅ **Fast Turbo Navigation** - localStorage only, no API calls
✅ **Immediate UI Updates** - localStorage first, API second
✅ **Debounced API Calls** - Efficient database updates
✅ **Type Safety** - Unified preference structure
✅ **Easy Debugging** - Single console log namespace
✅ **Backward Compatible** - Old code still works

## Files Modified

- ✅ `/app/public/preference-manager.js` - NEW centralized manager
- ✅ `/app/templates/base.html.twig` - Initialize PreferenceManager
- ✅ `/app/templates/settings/index.html.twig` - Use PreferenceManager
- ✅ `/app/assets/controllers/view_toggle_controller.js` - Already compatible
- ❌ `/app/public/list-preferences.js` - REMOVED (deprecated)

## Future Enhancements

- Add preference versioning for migration
- Add preference export/import
- Add preference reset per category
- Add preference validation schemas
- Add preference change events for real-time sync
