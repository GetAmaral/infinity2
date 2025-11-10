# Modern Sidebar Navigation - Implementation V2

**Date:** 2025-10-29
**Status:** REVISED - Ready for Clean Implementation
**Priority:** CRITICAL

---

## 🎯 What Went Wrong in V1

### Critical Mistakes:
1. ❌ Used `position: fixed` without proper container structure
2. ❌ Body overflow not disabled causing content to push down
3. ❌ No flexbox wrapper to control layout flow
4. ❌ Main content not in proper flex child
5. ❌ CSS loaded but not applied due to cascade issues

---

## ✅ Correct Implementation Strategy

### Core Principles (from research):

1. **Disable body scroll** - Only main content area scrolls
2. **Flexbox wrapper** - Container with `display: flex` and `height: 100vh`
3. **Sidebar as flex child** - `flex: 0 0 260px` (no grow, no shrink, 260px wide)
4. **Main content scrollable** - `flex: 1` with `overflow-y: auto`
5. **No position: fixed needed** - Flexbox handles everything

---

## 📐 HTML Structure

```html
<body style="overflow: hidden; height: 100vh; margin: 0;">
    <!-- Navbar (stays at top) -->
    <nav class="luminai-navbar">...</nav>

    <!-- Main wrapper with flexbox -->
    <div class="app-wrapper">
        <!-- Sidebar (non-scrolling) -->
        <aside class="app-sidebar">
            <div class="sidebar-content">
                <!-- Search -->
                <!-- Favorites -->
                <!-- Navigation Accordion -->
            </div>
        </aside>

        <!-- Main content (scrollable) -->
        <main class="app-main">
            <!-- All page content here -->
        </main>
    </div>
</body>
```

---

## 🎨 CSS Architecture

### Base Structure
```css
/* Prevent body scroll */
body {
    overflow: hidden;
    height: 100vh;
    margin: 0;
}

/* Flex wrapper */
.app-wrapper {
    display: flex;
    height: calc(100vh - 56px); /* Minus navbar height */
    overflow: hidden;
}

/* Sidebar - Fixed width, no scroll */
.app-sidebar {
    flex: 0 0 260px; /* Don't grow, don't shrink, 260px wide */
    background: var(--luminai-card-bg);
    border-right: 1px solid var(--luminai-border);
    display: flex;
    flex-direction: column;
    transition: flex-basis 0.3s ease;
}

.app-sidebar.collapsed {
    flex-basis: 60px;
}

/* Sidebar content - Scrollable */
.sidebar-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem;
}

/* Main content - Scrollable */
.app-main {
    flex: 1; /* Take remaining space */
    overflow-y: auto;
    overflow-x: hidden;
    padding: 2rem;
    background: var(--luminai-dark-bg);
}

/* Mobile: Stack vertically */
@media (max-width: 991px) {
    .app-wrapper {
        flex-direction: column;
    }

    .app-sidebar {
        flex-basis: auto;
        max-height: 60px;
        border-right: none;
        border-bottom: 1px solid var(--luminai-border);
    }

    .app-sidebar.mobile-open {
        position: fixed;
        top: 56px;
        left: 0;
        bottom: 0;
        z-index: 1000;
        max-height: none;
        box-shadow: 4px 0 12px rgba(0, 0, 0, 0.3);
    }
}
```

---

## 🔍 Features Required

### 1. Search (Real-time)
```javascript
// Filters menu items as user types
- Highlights matching items
- Shows/hides sections based on results
- Keyboard shortcut: Ctrl+K
```

### 2. Favorites
```javascript
// Star icon on each menu item
- Click to add/remove favorite
- Saves to user_sidebar_preference table
- Favorites section at top
- Drag-and-drop reorder (SortableJS)
```

### 3. Sort Options
```javascript
// Dropdown in sidebar header
- Sort by: Name (A-Z), Name (Z-A), Recently Added, Custom Order
- Saves preference to database
- Applies to both favorites and sections
```

### 4. Accordion Sections
```javascript
// Each section (CRM, Calendar, etc.)
- Click header to expand/collapse
- Only one section open at a time (accordion)
- Remembers expanded section in localStorage + DB
- Smooth animation
```

---

## 🗄️ Database Schema (Already Exists)

```sql
CREATE TABLE user_sidebar_preference (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    collapsed BOOLEAN DEFAULT FALSE NOT NULL,
    expanded_sections JSONB DEFAULT '[]' NOT NULL,
    favorites JSONB DEFAULT '[]' NOT NULL,
    sort_order VARCHAR(50) DEFAULT 'name_asc',
    updated_at TIMESTAMP NOT NULL,
    UNIQUE(user_id)
);
```

---

## 🚀 Implementation Steps

### Step 1: Clean Up (Remove Broken Code)
```bash
# Remove position: fixed approach
# Keep: Database entities, Twig extensions, Services
# Remove: Broken CSS, incorrect templates
```

### Step 2: Update base.html.twig
```twig
{# Wrap everything in flexbox structure #}
<body style="overflow: hidden; height: 100vh; margin: 0;">
    {% include '_partials/_navbar.html.twig' %}

    {% if app.user %}
        <div class="app-wrapper">
            {% include '_partials/_sidebar.html.twig' %}
            <main class="app-main">
                {% block body %}{% endblock %}
            </main>
        </div>
    {% else %}
        <main class="container">
            {% block body %}{% endblock %}
        </main>
    {% endif %}
</body>
```

### Step 3: Create Sidebar Component
```twig
{# _partials/_sidebar.html.twig #}
<aside class="app-sidebar" data-controller="sidebar">
    <div class="sidebar-header">
        {# Search bar #}
        {# Sort dropdown #}
        {# Collapse button #}
    </div>

    <div class="sidebar-content">
        {# Favorites section (if any) #}
        {# Accordion sections #}
    </div>
</aside>
```

### Step 4: Stimulus Controllers
```javascript
// sidebar_controller.js - Main controller
- Toggle collapse
- Save preferences
- Keyboard shortcuts

// sidebar-search_controller.js - Search
- Real-time filtering
- Highlight matches

// sidebar-favorites_controller.js - Favorites
- Add/remove stars
- Drag-and-drop reorder
- Save to database

// sidebar-sort_controller.js - Sorting
- Sort menu items
- Save preference
```

### Step 5: CSS File Structure
```
assets/styles/
├── app.css (main imports)
├── components/
│   └── sidebar.css (flexbox implementation)
```

---

## 🎯 Success Criteria

✅ Sidebar stays fixed while main content scrolls
✅ No content pushing down
✅ Search filters menu items in real-time
✅ Favorites work with drag-and-drop
✅ Sort persists across sessions
✅ Accordion sections remember state
✅ Mobile responsive (offcanvas)
✅ Smooth animations (60fps)
✅ Works in Chrome, Firefox, Safari, Edge

---

## 📋 Testing Checklist

- [ ] Hard refresh browser after implementation
- [ ] Test search with various queries
- [ ] Add/remove favorites
- [ ] Reorder favorites by dragging
- [ ] Expand/collapse sections
- [ ] Sort menu items
- [ ] Collapse sidebar
- [ ] Test on mobile (< 992px)
- [ ] Verify preferences persist after logout/login
- [ ] Check performance (no lag)

---

## 🔧 Quick Commands

```bash
# Clear all caches
docker-compose exec -T app php bin/console cache:clear

# Restart services
docker-compose restart app nginx

# Check Twig functions
docker-compose exec -T app php bin/console debug:twig | grep sidebar
```

---

**READY TO IMPLEMENT**
