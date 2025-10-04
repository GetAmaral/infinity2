# 🚀 TURBO MIGRATION PLAN - FULL IMPLEMENTATION

**Project:** Infinity Symfony Application
**Goal:** Implement Hotwire Turbo across entire application
**Approach:** Full migration (Option B)
**Timeline:** 7-8 days
**Risk Level:** Medium-High

---

## 📊 EXECUTIVE SUMMARY

This plan implements Turbo Drive across the entire Infinity application to achieve SPA-like navigation without page reloads. The migration is broken into 10 short, testable phases with clear rollback capability.

**Key Features:**
- ✅ Smooth page transitions (no white flash)
- ✅ Faster navigation (XHR instead of full page loads)
- ✅ Preserved scroll positions
- ✅ Better perceived performance
- ✅ Reduced server load from caching

**Critical Exclusions:**
- ❌ Admin Audit Log pages (full reload required)

**Included Features:**
- ✅ All CRUD operations
- ✅ TreeFlow functionality
- ✅ Course management
- ✅ Video player
- ✅ Organization switcher

---

## 🎯 PREREQUISITES VERIFIED

### Already Turbo-Ready:
- ✅ **Preference System** - `/public/preference-manager.js` exists and is Turbo-aware
- ✅ **UserPreferencesService** - Backend service ready
- ✅ **ListPreferencesService** - Backend service ready
- ✅ **Turbo Package** - Already installed (`symfony/ux-turbo` v2.30)
- ✅ **Stimulus** - 14 controllers actively used

### Issues to Fix:
- ⚠️ 8 instances of `window.location.reload()` and `window.location.href`
- ⚠️ 5 templates with inline `onclick` handlers
- ⚠️ 14 templates with `DOMContentLoaded` listeners

---

## 📋 PHASE-BY-PHASE IMPLEMENTATION

---

## **PHASE 1: Fix Controller Navigation**
**Duration:** 3-4 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Replace all `window.location` calls in Stimulus controllers with Turbo-compatible code.

### Files to Modify (8 total)

1. **`assets/delete-handler.js:174`**
   ```javascript
   // BEFORE
   setTimeout(() => window.location.reload(), 800);

   // AFTER
   setTimeout(() => {
       if (typeof Turbo !== 'undefined') {
           Turbo.cache.clear();
           Turbo.visit(window.location, { action: 'replace' });
       } else {
           window.location.reload();
       }
   }, 800);
   ```

2. **`assets/controllers/module_lecture_reorder_controller.js:228`**
   ```javascript
   // BEFORE
   window.location.reload();

   // AFTER
   if (typeof Turbo !== 'undefined') {
       Turbo.cache.clear();
       Turbo.visit(window.location, { action: 'replace' });
   } else {
       window.location.reload();
   }
   ```

3. **`assets/controllers/lecture_processing_controller.js:58`**
   - Same pattern as #2

4. **`assets/controllers/enrollment_switch_controller.js:172`**
   - Same pattern as #2

5. **`assets/controllers/course_enrollment_controller.js:276`**
   - Same pattern as #2

6. **`assets/controllers/crud_modal_controller.js:119`**
   ```javascript
   // BEFORE
   window.location.href = response.url;

   // AFTER
   if (typeof Turbo !== 'undefined') {
       Turbo.visit(response.url);
   } else {
       window.location.href = response.url;
   }
   ```

7. **`assets/controllers/crud_modal_controller.js:136`**
   ```javascript
   // BEFORE
   window.location.href = '/organization';

   // AFTER
   if (typeof Turbo !== 'undefined') {
       Turbo.visit('/organization');
   } else {
       window.location.href = '/organization';
   }
   ```

8. **`assets/controllers/live_search_controller.js:184`**
   ```javascript
   // BEFORE
   window.location.href = `/organization/${orgId}`;

   // AFTER
   if (typeof Turbo !== 'undefined') {
       Turbo.visit(`/organization/${orgId}`);
   } else {
       window.location.href = `/organization/${orgId}`;
   }
   ```

### Test Checklist
- [ ] Delete organization → Success message → Page updates
- [ ] Enroll students → Success message → List updates
- [ ] Process lecture → Success → Course page updates
- [ ] Reorder modules → Success → Order persists
- [ ] Edit organization modal → Save → Redirects properly
- [ ] Live search → Click result → Navigates to detail
- [ ] **No console errors**
- [ ] **Everything works exactly as before**

### Expected Result
No visual changes. All functionality works identically. Code is now Turbo-ready but Turbo is not active yet.

### Changes Summary
**Modified:** 8 JavaScript files
**Added:** 0 files
**Deleted:** 0 files

---

## **PHASE 2: Fix base.html.twig Scripts**
**Duration:** 2 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Make inline scripts in base template compatible with both DOMContentLoaded and turbo:load events.

### File to Modify

**`templates/base.html.twig` (Lines 187-476)**

### Changes

**Replace the entire inline script section:**

```javascript
<script data-turbo-eval="false">
    // ============================================
    // INITIALIZATION FUNCTION
    // ============================================
    function initializePage() {
        // Ensure Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.warn('⚠️ Bootstrap not yet loaded');
            return;
        }

        // Initialize theme
        if (window.GlobalTheme) {
            GlobalTheme.init();
        }

        // Initialize tooltips
        if (window.initGlobalTooltips) {
            window.initGlobalTooltips();
        }

        console.log('✅ Page initialized');
    }

    // ============================================
    // CLEANUP FUNCTION (Before Turbo caches page)
    // ============================================
    function cleanupPage() {
        console.log('🧹 Cleaning up page before cache');

        // Dispose all Bootstrap tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            const tooltip = bootstrap.Tooltip.getInstance(el);
            if (tooltip) tooltip.dispose();
        });

        // Remove modal backdrops
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Close all open dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }

    // ============================================
    // TURBO EVENT LOGGING (for debugging)
    // ============================================
    if (typeof Turbo !== 'undefined') {
        // Navigation events
        document.addEventListener('turbo:click', (event) => {
            console.log('🖱️ Turbo: Link clicked', event.detail.url);
        });

        document.addEventListener('turbo:before-visit', (event) => {
            console.log('🚀 Turbo: Starting visit to', event.detail.url);
        });

        document.addEventListener('turbo:visit', (event) => {
            console.log('📡 Turbo: Fetching', event.detail.url);
        });

        document.addEventListener('turbo:before-cache', () => {
            console.log('📦 Turbo: Caching current page');
            cleanupPage();
        });

        document.addEventListener('turbo:before-render', (event) => {
            console.log('🎨 Turbo: About to render');
        });

        document.addEventListener('turbo:render', () => {
            console.log('✨ Turbo: Page rendered');
        });

        document.addEventListener('turbo:load', () => {
            console.log('✅ Turbo: Navigation complete');
            initializePage();
        });

        // Form events
        document.addEventListener('turbo:submit-start', (event) => {
            console.log('📤 Turbo: Form submission started');
        });

        document.addEventListener('turbo:submit-end', (event) => {
            console.log('📥 Turbo: Form submission ended', event.detail.success ? '✅' : '❌');
        });

        // Error events
        document.addEventListener('turbo:fetch-request-error', (event) => {
            console.error('❌ Turbo: Fetch error', event.detail);
        });
    }

    // ============================================
    // SUPPORT BOTH EVENTS
    // ============================================
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📄 DOMContentLoaded fired');
        initializePage();
    });

    // Turbo events (only fire if Turbo is active)
    document.addEventListener('turbo:load', () => {
        console.log('🔄 turbo:load fired');
        initializePage();
    });

    document.addEventListener('turbo:before-cache', cleanupPage);

    // ============================================
    // KEEP EXISTING CODE BELOW
    // ============================================

    // Global theme management (keep as-is)
    if (typeof GlobalTheme === 'undefined') {
        window.GlobalTheme = {
            current: 'dark',
            // ... rest of existing GlobalTheme code ...
        };
    }

    // ... rest of existing inline script code ...
</script>
```

### Test Checklist
- [ ] Page loads normally (full refresh)
- [ ] Theme switcher works
- [ ] Theme persists on refresh
- [ ] Tooltips appear on hover
- [ ] Dropdowns open/close
- [ ] Organization switcher works
- [ ] Check console: Should see "📄 DOMContentLoaded fired"
- [ ] Check console: Should see "✅ Page initialized"
- [ ] **No console errors**

### Expected Result
Same behavior as before. Console now shows helpful log messages.

### Changes Summary
**Modified:** 1 file (`templates/base.html.twig`)
**Added:** Enhanced logging and cleanup
**Deleted:** 0 files

---

## **PHASE 3: Fix _base_entity_list.html.twig**
**Duration:** 2 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Make the base entity list template compatible with Turbo navigation.

### File to Modify

**`templates/_base_entity_list.html.twig`**

### Changes

**Find the DOMContentLoaded listener (around line 789) and replace:**

```javascript
// BEFORE
document.addEventListener('DOMContentLoaded', function() {
    // Initialization code
});

// AFTER
function initializeEntityList() {
    console.log('📋 Initializing entity list');

    // All existing initialization code goes here
    // (view toggles, search, pagination, etc.)
}

// Support both events
document.addEventListener('DOMContentLoaded', initializeEntityList);
document.addEventListener('turbo:load', initializeEntityList);

// Cleanup MutationObserver before caching
document.addEventListener('turbo:before-cache', function() {
    console.log('🧹 Cleaning up entity list observers');

    if (window.entityListObserver) {
        window.entityListObserver.disconnect();
        window.entityListObserver = null;
    }
});
```

**For the MutationObserver (around lines 862-872):**

```javascript
// Store observer globally for cleanup
if (!window.entityListObserver) {
    window.entityListObserver = new MutationObserver(function(mutations) {
        // Existing observer code
    });

    window.entityListObserver.observe(targetElement, {
        childList: true,
        subtree: true
    });
}
```

### Test Checklist
- [ ] Grid view displays correctly
- [ ] List view displays correctly
- [ ] Table view displays correctly
- [ ] Switch between views → Preference persists
- [ ] Search works
- [ ] Clear search works
- [ ] Pagination works
- [ ] Items per page works
- [ ] Sorting works (if applicable)
- [ ] Navigate away and back → View preference remembered
- [ ] Check console: Should see "📋 Initializing entity list"
- [ ] **No console errors**

### Expected Result
All list functionality works. View preferences persist across page navigation.

### Changes Summary
**Modified:** 1 file (`templates/_base_entity_list.html.twig`)
**Added:** Turbo event support and cleanup
**Deleted:** 0 files

---

## **PHASE 4: Create Navigate Controller & Remove Inline Handlers**
**Duration:** 4-5 hours
**Risk:** Low-Medium
**Can Rollback:** Yes

### Goal
1. Create role-aware navigation Stimulus controller
2. Replace all inline onclick handlers with proper navigation

### Part A: Create Navigate Controller (1 hour)

**New File:** `assets/controllers/navigate_controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';

/**
 * Navigate Controller - Role-aware navigation
 *
 * Handles navigation with permission checking
 * Supports both Turbo and traditional navigation
 *
 * Usage:
 * <div data-controller="navigate"
 *      data-navigate-url-value="/path"
 *      data-navigate-allowed-value="true"
 *      data-action="click->navigate#go">
 */
export default class extends Controller {
    static values = {
        url: String,
        allowed: { type: Boolean, default: true },
        confirmMessage: String
    };

    connect() {
        // Add pointer cursor if navigation is allowed
        if (this.allowedValue) {
            this.element.style.cursor = 'pointer';
        } else {
            this.element.style.cursor = 'not-allowed';
            this.element.style.opacity = '0.6';
        }

        console.log('🧭 Navigate controller connected', {
            url: this.urlValue,
            allowed: this.allowedValue
        });
    }

    go(event) {
        // Prevent navigation if not allowed
        if (!this.allowedValue) {
            event.preventDefault();
            event.stopPropagation();
            console.warn('⛔ Navigation not allowed');
            return;
        }

        // Handle confirmation if needed
        if (this.hasConfirmMessageValue) {
            if (!confirm(this.confirmMessageValue)) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
        }

        // Navigate
        event.preventDefault();

        console.log('🚀 Navigating to:', this.urlValue);

        if (typeof Turbo !== 'undefined') {
            // Use Turbo for smooth navigation
            Turbo.visit(this.urlValue);
        } else {
            // Fallback to traditional navigation
            window.location.href = this.urlValue;
        }
    }

    /**
     * Navigate to URL directly (for programmatic use)
     */
    navigateTo(url) {
        if (typeof Turbo !== 'undefined') {
            Turbo.visit(url);
        } else {
            window.location.href = url;
        }
    }
}
```

### Part B: Fix Templates with Inline onclick (3-4 hours)

#### Templates to Fix (5 total):

1. **`templates/organization/index.html.twig`**
2. **`templates/course/index.html.twig`**
3. **`templates/user/index.html.twig`**
4. **`templates/treeflow/index.html.twig`**
5. **`templates/student/lecture.html.twig`**

#### Pattern to Apply:

```twig
{# BEFORE #}
<div onclick="window.location.href='/organization/{{ org.id }}'">
    {{ org.name }}
</div>

{# AFTER - Option 1: Simple link (PREFERRED if no special logic) #}
<a href="{{ path('organization_show', {id: org.id}) }}"
   class="text-decoration-none">
    {{ org.name }}
</a>

{# AFTER - Option 2: Navigate controller (if need permission check or complex logic) #}
<div data-controller="navigate"
     data-navigate-url-value="{{ path('organization_show', {id: org.id}) }}"
     data-navigate-allowed-value="{{ is_granted('VIEW', org) ? 'true' : 'false' }}"
     data-action="click->navigate#go">
    {{ org.name }}
</div>

{# For card clicks (common pattern) #}
<div class="card"
     data-controller="navigate"
     data-navigate-url-value="{{ path('organization_show', {id: org.id}) }}"
     data-navigate-allowed-value="true"
     data-action="click->navigate#go">
    <div class="card-body">
        <h5>{{ org.name }}</h5>
    </div>
</div>
```

#### Specific Changes:

**`templates/organization/index.html.twig`:**
- Replace card onclick handlers with navigate controller
- Add permission check: `data-navigate-allowed-value="{{ is_granted('ROLE_USER') ? 'true' : 'false' }}"`

**`templates/course/index.html.twig`:**
- Same pattern as organization
- Permission: `data-navigate-allowed-value="{{ is_granted('VIEW', course) ? 'true' : 'false' }}"`

**`templates/user/index.html.twig`:**
- Same pattern as organization
- Permission: `data-navigate-allowed-value="{{ is_granted('ROLE_ADMIN') ? 'true' : 'false' }}"`

**`templates/treeflow/index.html.twig`:**
- Same pattern as organization
- Permission: `data-navigate-allowed-value="{{ is_granted('VIEW', treeflow) ? 'true' : 'false' }}"`

**`templates/student/lecture.html.twig`:**
- Replace navigation onclick with navigate controller
- Permission: `data-navigate-allowed-value="{{ is_granted('VIEW', lecture) ? 'true' : 'false' }}"`

### Test Checklist
- [ ] Click organization card → Opens detail page (if allowed)
- [ ] Try to click card user has no permission → No navigation (cursor not-allowed)
- [ ] Click course card → Opens course page
- [ ] Click user card → Opens user page (admin only)
- [ ] Click TreeFlow card → Opens TreeFlow detail
- [ ] Student lecture navigation works
- [ ] Check console: Should see "🧭 Navigate controller connected"
- [ ] Check console: Should see "🚀 Navigating to: /path" on click
- [ ] **No console errors**
- [ ] Cards with cursor:pointer are clickable
- [ ] Cards with cursor:not-allowed are not clickable

### Expected Result
Cleaner HTML, no inline onclick handlers, role-based navigation working.

### Changes Summary
**Modified:** 5 template files
**Added:** 1 new Stimulus controller (`navigate_controller.js`)
**Deleted:** 0 files

---

## **PHASE 5: Create Organization Switcher Controller**
**Duration:** 2 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Clean up organization switcher with Stimulus controller while maintaining CSRF protection.

### New File

**`assets/controllers/org_switcher_controller.js`**

```javascript
import { Controller } from '@hotwired/stimulus';

/**
 * Organization Switcher Controller
 *
 * Handles smooth organization switching for admin users
 * Maintains CSRF protection
 * Works with both Turbo and traditional navigation
 */
export default class extends Controller {
    static targets = ['form', 'button'];

    connect() {
        console.log('🏢 Organization switcher connected');
    }

    /**
     * Switch to selected organization
     */
    switch(event) {
        event.preventDefault();

        const form = event.currentTarget.closest('form');

        if (!form) {
            console.error('❌ Form not found');
            return;
        }

        console.log('🔄 Switching organization...');

        // Close dropdown
        this.closeDropdown();

        // Submit form (Turbo will intercept if active)
        if (typeof Turbo !== 'undefined') {
            // Let Turbo handle it smoothly
            form.requestSubmit();
        } else {
            // Traditional form submit
            form.submit();
        }
    }

    /**
     * Clear organization (switch to "All Organizations")
     */
    clear(event) {
        event.preventDefault();

        const form = event.currentTarget.closest('form');

        if (!form) {
            console.error('❌ Form not found');
            return;
        }

        console.log('🌐 Clearing organization...');

        // Close dropdown
        this.closeDropdown();

        // Submit form
        if (typeof Turbo !== 'undefined') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    /**
     * Close the Bootstrap dropdown
     */
    closeDropdown() {
        const dropdown = this.element.querySelector('.dropdown-menu.show');
        if (dropdown && typeof bootstrap !== 'undefined') {
            const toggle = this.element.querySelector('[data-bs-toggle="dropdown"]');
            if (toggle) {
                const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }
        }
    }
}
```

### Template Changes

**`templates/base.html.twig` (Organization Switcher Section)**

```twig
{% if can_switch_organization() %}
{% set orgs = available_organizations() %}
<div class="dropdown" data-controller="org-switcher">
    <a class="nav-link dropdown-toggle text-white d-flex align-items-center"
       href="#"
       id="orgDropdown"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false"
       style="cursor: pointer;"
       data-bs-toggle="tooltip"
       title="{{ 'organization.switcher.title'|trans }}">
        {% if current_organization() %}
            <div class="me-2">
                {{ org_logo.logo(current_organization(), 'xs') }}
            </div>
        {% else %}
            <i class="bi bi-globe me-2"></i>
        {% endif %}
        <span class="d-none d-md-inline">
            {{ current_organization() ? current_organization().name : 'organization.all.organizations'|trans }}
        </span>
    </a>

    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark"
        aria-labelledby="orgDropdown"
        style="background: var(--infinity-dark-surface); border: 1px solid rgba(255, 255, 255, 0.1); min-width: 250px;">

        {# Header #}
        <li class="px-3 py-2 border-bottom" style="border-color: rgba(255, 255, 255, 0.1) !important;">
            <small class="org-switcher-title">{{ 'organization.switcher.title'|trans|upper }}</small>
            <div class="org-switcher-subtitle" style="font-size: 0.7rem; margin-top: 0.25rem;">
                {{ 'organization.switcher.subtitle'|trans }}
            </div>
        </li>

        {# Clear Organization (if one is active) #}
        {% if has_active_organization() %}
        <li>
            <form method="post"
                  action="{{ path('app_organization_switcher_clear') }}"
                  data-org-switcher-target="form">
                <input type="hidden" name="_token" value="{{ csrf_token('organization_clear') }}">
                <button type="submit"
                        class="dropdown-item"
                        data-action="click->org-switcher#clear">
                    <i class="bi bi-globe me-2"></i>{{ 'organization.all.organizations.root'|trans }}
                </button>
            </form>
        </li>
        <li><hr class="dropdown-divider" style="border-color: rgba(255, 255, 255, 0.1);"></li>
        {% endif %}

        {# Organization List #}
        {% for org in orgs %}
        <li>
            <form method="post"
                  action="{{ path('app_organization_switcher_switch', {'id': org.id}) }}"
                  data-org-switcher-target="form">
                <input type="hidden" name="_token" value="{{ csrf_token('organization_switch_' ~ org.id) }}">
                <button type="submit"
                        class="dropdown-item d-flex align-items-center gap-2 {% if current_organization() and current_organization().id == org.id %}active{% endif %}"
                        data-action="click->org-switcher#switch">
                    <div class="flex-shrink-0">
                        {{ org_logo.logo(org, 'sm') }}
                    </div>
                    <div class="flex-grow-1">
                        {{ org.name }}
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            {{ org.slug }}.{{ app_base_domain }}
                        </small>
                    </div>
                </button>
            </form>
        </li>
        {% endfor %}
    </ul>
</div>
{% endif %}
```

### Test Checklist
- [ ] Organization dropdown opens
- [ ] Click organization → Switches smoothly
- [ ] Click "All Organizations" → Clears org
- [ ] Check console: Should see "🏢 Organization switcher connected"
- [ ] Check console: Should see "🔄 Switching organization..." on click
- [ ] Dropdown closes after selection
- [ ] CSRF tokens still present in forms
- [ ] No console errors
- [ ] Works without Turbo active (traditional submit)

### Expected Result
Cleaner template code, smooth organization switching, CSRF protection maintained.

### Changes Summary
**Modified:** 1 template (`templates/base.html.twig`)
**Added:** 1 new Stimulus controller (`org_switcher_controller.js`)
**Deleted:** 0 files

---

## **PHASE 6: ENABLE TURBO + PROGRESS BAR** 🚀
**Duration:** 1-2 hours
**Risk:** HIGH ⚠️
**Can Rollback:** YES

### Goal
1. Activate Turbo Drive globally
2. Add Turbo progress bar
3. Exclude Audit log from Turbo
4. Enable comprehensive logging

### Part A: Enable Turbo in app.js

**File:** `assets/app.js`

**Add at the very top (before other imports):**

```javascript
// ============================================
// TURBO IMPORT & CONFIGURATION
// ============================================
import '@hotwired/turbo';
import { Turbo } from '@hotwired/turbo';

// Enable Turbo Drive
Turbo.session.drive = true;

console.log('🚀 Turbo enabled');

// Turbo configuration
Turbo.setProgressBarDelay(100); // Show progress bar after 100ms

// ... rest of existing imports ...
```

### Part B: Add Turbo Progress Bar CSS

**File:** `assets/styles/app.css`

**Add at the end:**

```css
/* ============================================
   TURBO PROGRESS BAR
   ============================================ */

.turbo-progress-bar {
    position: fixed;
    display: block;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    z-index: 9999;
    transition: width 300ms ease-out, opacity 150ms 150ms ease-in;
    transform: translate3d(0, 0, 0);
    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
}

/* Add glow effect */
.turbo-progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4));
    animation: turbo-progress-glow 2s ease-in-out infinite;
}

@keyframes turbo-progress-glow {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
}

/* Dark theme variant */
[data-theme="dark"] .turbo-progress-bar {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

/* Light theme variant */
[data-theme="light"] .turbo-progress-bar {
    background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
}
```

### Part C: Exclude Audit Log from Turbo

**File:** `templates/base.html.twig`

**Add in the `<head>` section (after title):**

```twig
{# Exclude admin audit pages from Turbo (need full reload for data integrity) #}
{% set currentRoute = app.request.attributes.get('_route') %}
{% if currentRoute starts with 'admin_audit' %}
    <meta name="turbo-visit-control" content="reload">
    <meta name="turbo-cache-control" content="no-cache">
{% endif %}
```

### Part D: Enhanced Turbo Event Logging

**Already added in Phase 2, but verify it's present in `templates/base.html.twig`:**

```javascript
// Verify this logging code exists from Phase 2
if (typeof Turbo !== 'undefined') {
    document.addEventListener('turbo:click', (event) => {
        console.log('🖱️ Turbo: Link clicked', event.detail.url);
    });

    // ... other event listeners from Phase 2 ...
}
```

### Test Checklist (CRITICAL - Test Everything!)

#### Basic Navigation:
- [ ] **Open browser DevTools → Network tab**
- [ ] Click any link (e.g., Home → Organizations)
- [ ] **Verify:** Network shows XHR request, NOT full page load (Doc type)
- [ ] **Verify:** No white flash during navigation
- [ ] **Verify:** Turbo progress bar appears at top (purple gradient line)
- [ ] Check console: Should see "🖱️ Turbo: Link clicked"
- [ ] Check console: Should see "🚀 Turbo: Starting visit"
- [ ] Check console: Should see "✅ Turbo: Navigation complete"

#### Browser Controls:
- [ ] Click back button → Smooth navigation back
- [ ] Click forward button → Smooth navigation forward
- [ ] Press F5 (refresh) → Full page reload (expected)
- [ ] Scroll down page → Navigate → Come back → Scroll position restored

#### Audit Log (MUST BE EXCLUDED):
- [ ] Navigate to Organizations (smooth Turbo nav)
- [ ] Click "Audit Log" link
- [ ] **Verify:** Network shows **full page load** (Doc type, NOT XHR)
- [ ] **Verify:** Console shows "📄 DOMContentLoaded fired" (not turbo:load)
- [ ] Audit page works normally
- [ ] Click "Analytics" → Full page reload again
- [ ] Navigate away from Audit → **Turbo resumes** (smooth nav)

#### TreeFlow (MUST BE INCLUDED):
- [ ] Navigate to TreeFlow list
- [ ] **Verify:** Network shows XHR request (Turbo navigation)
- [ ] **Verify:** Smooth transition, no white flash
- [ ] Click TreeFlow detail → Smooth navigation
- [ ] Check console: Should see turbo:load events

#### Error Checking:
- [ ] **Open console → Check for ANY red errors**
- [ ] **If ANY errors, STOP immediately and report**

### Expected Result
- ✅ Smooth page transitions (no white flash)
- ✅ Purple progress bar appears during navigation
- ✅ Network tab shows XHR requests (except Audit pages)
- ✅ Back/forward buttons work
- ✅ Audit pages do full reload
- ✅ TreeFlow pages use Turbo
- ✅ Console shows Turbo event logs
- ✅ **NO CONSOLE ERRORS**

### 🚨 CRITICAL CHECKPOINT

**IF ANY OF THESE FAIL, STOP IMMEDIATELY:**
- ❌ Console has errors
- ❌ Navigation breaks
- ❌ Forms don't submit
- ❌ Modals don't open
- ❌ Page goes blank

**ROLLBACK PROCEDURE:**
1. Edit `assets/app.js`
2. Comment out: `// import '@hotwired/turbo';`
3. Clear browser cache (Ctrl+Shift+Delete)
4. Hard refresh (Ctrl+F5)
5. Verify site works without Turbo

### Changes Summary
**Modified:** 3 files (`assets/app.js`, `assets/styles/app.css`, `templates/base.html.twig`)
**Added:** Turbo import, progress bar CSS, audit exclusion
**Deleted:** 0 files

---

## **PHASE 7: Comprehensive Feature Testing**
**Duration:** 8 hours (full day)
**Risk:** Medium
**Can Rollback:** Yes

### Goal
Test all major features of the application to ensure Turbo compatibility.

### 7.1 Navigation Testing (1 hour)

#### Test Scenarios:
- [ ] Home → Organizations → Detail → Back
  - [ ] Smooth transition (no white flash)
  - [ ] Progress bar visible
  - [ ] Scroll position preserved on back

- [ ] Home → Courses → Detail → Back
  - [ ] Same checks as above

- [ ] Home → Users → Detail → Back
  - [ ] Same checks as above

- [ ] Home → TreeFlow → Detail → Back
  - [ ] Same checks as above

- [ ] Navbar navigation
  - [ ] All navbar links work
  - [ ] Active page highlighted
  - [ ] Dropdowns work

- [ ] Breadcrumbs (if present)
  - [ ] Clickable
  - [ ] Navigate correctly

#### Console Checks:
- [ ] See turbo:click for each link
- [ ] See turbo:load after navigation
- [ ] No errors

### 7.2 Forms & Modals Testing (2 hours)

#### Organization CRUD:
- [ ] Click "New Organization" → Modal opens
  - [ ] Modal appears smoothly
  - [ ] Focus on first input

- [ ] Fill form with invalid data → Submit
  - [ ] Validation errors display
  - [ ] Modal stays open

- [ ] Fill form with valid data → Submit
  - [ ] Success message appears
  - [ ] Modal closes
  - [ ] List updates with new org
  - [ ] Smooth redirect (if applicable)

- [ ] Edit organization → Save
  - [ ] Modal opens with existing data
  - [ ] Save works
  - [ ] Changes reflected

- [ ] Delete organization → Confirm
  - [ ] Confirmation modal appears
  - [ ] Delete succeeds
  - [ ] Item removed from list

#### Course CRUD:
- [ ] Same tests as Organization
- [ ] Create module works
- [ ] Create lecture works
- [ ] Delete module works
- [ ] Delete lecture works

#### User CRUD:
- [ ] Same tests as Organization

#### Console Checks:
- [ ] See turbo:submit-start on form submit
- [ ] See turbo:submit-end after response
- [ ] No errors

### 7.3 Complex Features Testing (2 hours)

#### Course Enrollment:
- [ ] Open course detail
- [ ] Click "Manage Enrollments"
- [ ] Modal opens with student list
- [ ] TomSelect multi-select works
  - [ ] Can search students
  - [ ] Can select multiple
  - [ ] Can deselect

- [ ] Toggle enrollment switches
  - [ ] Switches respond
  - [ ] Badge updates

- [ ] Click "Confirm"
  - [ ] Success message appears
  - [ ] Page updates
  - [ ] List shows new enrollments

#### Enrollment Switch View:
- [ ] Open enrollment switch page
- [ ] Search for student
  - [ ] Results filter
  - [ ] Debounced correctly

- [ ] Toggle switches
  - [ ] Switches respond
  - [ ] Count updates

- [ ] Click "Confirm"
  - [ ] Success notification
  - [ ] Page updates (NO full reload)

#### Lecture Video Player:
- [ ] Open student lecture page
- [ ] Video player loads
  - [ ] Plyr interface appears
  - [ ] HLS.js loads (if video is HLS)

- [ ] Play video
  - [ ] Video plays
  - [ ] Progress tracked

- [ ] Mark lecture complete
  - [ ] Checkbox toggles
  - [ ] Progress updates

- [ ] Navigate away
  - [ ] Navigate back
  - [ ] **No duplicate video players**
  - [ ] Player state reset

#### Drag-and-Drop Lecture Reorder:
- [ ] Open course with modules
- [ ] Drag lecture to new position
  - [ ] Placeholder appears
  - [ ] Drop works
  - [ ] Order saves

- [ ] Refresh page
  - [ ] New order persists

- [ ] Navigate away and back
  - [ ] **No ghost placeholders**

#### Console Checks:
- [ ] No errors during video playback
- [ ] No errors during drag-drop
- [ ] Video player disconnect on navigation

### 7.4 Search & Filters Testing (1 hour)

#### Live Search:
- [ ] Organizations page → Search
  - [ ] Type in search box
  - [ ] Results filter in real-time
  - [ ] Debouncing works (not searching on every keystroke)
  - [ ] Clear search → All results return

- [ ] Courses page → Search
  - [ ] Same checks

- [ ] Users page → Search
  - [ ] Same checks

#### Search in Modal:
- [ ] Enrollment modal → Search
  - [ ] Search filters students
  - [ ] Clear works
  - [ ] Modal doesn't close

#### Click Search Result:
- [ ] Search for item
- [ ] Click result
  - [ ] Navigate to detail page
  - [ ] Smooth Turbo navigation

#### Console Checks:
- [ ] See turbo:visit on result click
- [ ] No errors

### 7.5 View Toggles Testing (1 hour)

#### View Switching:
- [ ] Organizations page
- [ ] Click Grid view
  - [ ] Layout changes to grid
  - [ ] Items display correctly

- [ ] Click List view
  - [ ] Layout changes to list
  - [ ] Items display correctly

- [ ] Click Table view
  - [ ] Layout changes to table
  - [ ] Columns display correctly

- [ ] Navigate to Courses
- [ ] Come back to Organizations
  - [ ] **View preference persisted**

#### Items Per Page:
- [ ] Change items per page
- [ ] List updates
- [ ] Navigate away and back
  - [ ] Preference persisted

#### Pagination:
- [ ] Click page 2
  - [ ] Page changes
  - [ ] URL updates

- [ ] Click back button
  - [ ] Returns to page 1

- [ ] Navigate to different entity
- [ ] Come back
  - [ ] On correct page

#### Console Checks:
- [ ] Preferences saved to PreferenceManager
- [ ] No errors

### 7.6 Organization Switcher Testing (30 min)

**Admin users only:**

- [ ] Click organization dropdown
  - [ ] Dropdown opens
  - [ ] Shows all organizations
  - [ ] Current org highlighted

- [ ] Click different organization
  - [ ] Dropdown closes
  - [ ] **Smooth transition (no white flash)**
  - [ ] Page updates with new org context
  - [ ] Check console: See turbo:submit-start

- [ ] Click "All Organizations"
  - [ ] Clears organization
  - [ ] Smooth transition
  - [ ] Can see all data

- [ ] Switch back to specific org
  - [ ] Data filtered correctly

#### Console Checks:
- [ ] See org-switcher controller logs
- [ ] See turbo:submit events
- [ ] No errors

### 7.7 Theme & Preferences Testing (30 min)

#### Theme Toggle:
- [ ] Current theme displays (dark/light)
- [ ] Click theme toggle
  - [ ] Theme switches immediately
  - [ ] PreferenceManager saves

- [ ] Navigate to another page
  - [ ] **Theme persists**
  - [ ] Check console: See preference load from localStorage

- [ ] Refresh page
  - [ ] Theme still correct

#### Tooltips:
- [ ] Hover over element with tooltip
  - [ ] Tooltip appears

- [ ] Navigate to another page
- [ ] Hover over tooltip element
  - [ ] **Tooltip still works**

- [ ] Check console: See tooltip initialization

#### Dropdowns:
- [ ] Open navbar dropdown
  - [ ] Dropdown opens

- [ ] Navigate to another page
- [ ] Open dropdown again
  - [ ] **Dropdown works**
  - [ ] No duplicate dropdowns

### Expected Results Summary

**All features should work EXACTLY as before, but with:**
- ✅ Smooth page transitions
- ✅ No white flash
- ✅ Progress bar during navigation
- ✅ Faster perceived performance
- ✅ Working back/forward buttons
- ✅ Preserved scroll positions
- ✅ Preferences persist

**RED FLAGS (Stop if you see):**
- ❌ Any console errors
- ❌ Forms not submitting
- ❌ Modals not opening/closing
- ❌ Duplicate elements (players, tooltips, etc.)
- ❌ Navigation breaks
- ❌ White page / blank screen

---

## **PHASE 8: Fix Remaining DOMContentLoaded Issues**
**Duration:** 3-4 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Fix any remaining templates that have DOMContentLoaded issues discovered during Phase 7 testing.

### Templates Likely Needing Fixes:

1. **`templates/treeflow/show.html.twig`**
2. **`templates/student/lecture.html.twig`**
3. **`templates/security/login.html.twig`** (if has DOMContentLoaded)
4. **TreeFlow modals:**
   - `templates/treeflow/fewshot/_form_modal.html.twig`
   - `templates/treeflow/input/_form_modal.html.twig`
   - `templates/treeflow/question/_form_modal.html.twig`
   - `templates/treeflow/output/_form_modal.html.twig`
   - `templates/treeflow/step/_form_modal.html.twig`
   - `templates/treeflow/_form_modal.html.twig`
5. **`templates/organization/users.html.twig`**
6. **`templates/settings/index.html.twig`**

### Pattern to Apply (Same as Phase 3):

```javascript
// BEFORE
document.addEventListener('DOMContentLoaded', function() {
    // Initialization code
});

// AFTER
function initializeComponent() {
    console.log('🎯 Initializing component');
    // Initialization code
}

document.addEventListener('DOMContentLoaded', initializeComponent);
document.addEventListener('turbo:load', initializeComponent);

// If has cleanup needs
document.addEventListener('turbo:before-cache', function() {
    console.log('🧹 Cleaning up component');
    // Cleanup code
});
```

### Specific Fixes:

#### For Video Player Template:
```javascript
// Add guard against re-initialization
let playerInitialized = false;

function initializeVideoPlayer() {
    if (playerInitialized && typeof Turbo !== 'undefined') {
        console.log('⏭️ Player already initialized, skipping');
        return;
    }

    // Initialize player
    playerInitialized = true;
}

document.addEventListener('DOMContentLoaded', initializeVideoPlayer);
document.addEventListener('turbo:load', initializeVideoPlayer);

document.addEventListener('turbo:before-visit', function() {
    // Cleanup player before leaving
    if (window.player) {
        window.player.destroy();
        window.player = null;
    }
    playerInitialized = false;
});
```

### Test Checklist
- [ ] Navigate to each fixed page
- [ ] Page loads without errors
- [ ] Interactive features work
- [ ] Navigate away and back
- [ ] Feature still works (no duplicate initialization)
- [ ] Check console: See initialization logs
- [ ] **No errors**

### Expected Result
All pages work with Turbo navigation. No duplicate initializations.

### Changes Summary
**Modified:** 6-8 template files
**Added:** Turbo event support
**Deleted:** 0 files

---

## **PHASE 9: Browser & Performance Testing**
**Duration:** 8 hours (full day)
**Risk:** Low
**Can Rollback:** N/A (testing only)

### Goal
Verify Turbo works across browsers and performs well.

### 9.1 Cross-Browser Testing (3 hours)

#### Chrome/Chromium-based (Edge, Brave, Opera):
- [ ] Run full Phase 7 test suite
- [ ] Check DevTools Console for errors
- [ ] Check DevTools Network for proper XHR requests
- [ ] Test back/forward buttons
- [ ] Test modals
- [ ] Test forms
- [ ] Test video player
- [ ] **Document any issues**

#### Firefox:
- [ ] Run full Phase 7 test suite
- [ ] Check Browser Console
- [ ] Check Network Monitor
- [ ] Test back/forward
- [ ] Test modals
- [ ] Test forms
- [ ] Test video player
- [ ] **Document any issues**

#### Safari (macOS/iOS if available):
- [ ] Run key scenarios (don't need full suite)
- [ ] Navigation works
- [ ] Forms work
- [ ] Modals work
- [ ] **Document any issues**

#### Mobile Browsers:
- [ ] Mobile Chrome - Test navigation, forms, modals
- [ ] Mobile Safari - Test navigation, forms, modals
- [ ] Check touch interactions
- [ ] Check responsive design
- [ ] **Document any issues**

### 9.2 Performance Testing (2 hours)

#### Memory Leak Check:
- [ ] Open DevTools → Performance tab
- [ ] Start recording
- [ ] Navigate between 10 different pages
- [ ] Return to starting page
- [ ] Stop recording
- [ ] **Check:** Memory should return to ~baseline (not continuously growing)
- [ ] **Check:** No excessive DOM nodes

#### Navigation Speed:
- [ ] Open DevTools → Network tab
- [ ] Navigate between pages
- [ ] **Measure:** Time to complete navigation
- [ ] **Compare:** Should be faster than full page reload
- [ ] **Check:** Progress bar appears for slow requests

#### Cache Behavior:
- [ ] Navigate to page
- [ ] Click back button
- [ ] **Check:** Page appears instantly from cache
- [ ] **Check:** Network tab shows "from disk cache" or similar

#### Load Testing:
- [ ] Open large list (100+ items)
- [ ] Navigate to detail pages
- [ ] Come back to list
- [ ] **Check:** No slowdown
- [ ] **Check:** Smooth scrolling

### 9.3 Error Handling Testing (2 hours)

#### Network Disconnect:
- [ ] Navigate to page
- [ ] Open DevTools → Network tab → Throttling → Offline
- [ ] Click link
- [ ] **Check:** Error handling works (shows error page or message)
- [ ] Re-enable network
- [ ] **Check:** Navigation resumes

#### Slow Network:
- [ ] DevTools → Network → Throttling → Slow 3G
- [ ] Navigate between pages
- [ ] **Check:** Progress bar shows
- [ ] **Check:** Page loads eventually
- [ ] **Check:** No timeout errors

#### 404 Error:
- [ ] Navigate to non-existent page
- [ ] **Check:** 404 page displays
- [ ] **Check:** Can navigate away from 404

#### 500 Error:
- [ ] Trigger 500 error (if possible in dev)
- [ ] **Check:** Error page displays
- [ ] **Check:** Can navigate away

#### Form Validation:
- [ ] Submit form with errors
- [ ] **Check:** Errors display
- [ ] **Check:** Modal stays open (if modal form)
- [ ] Fix errors and resubmit
- [ ] **Check:** Success handling works

### 9.4 Turbo-Specific Edge Cases (1 hour)

#### Multiple Rapid Clicks:
- [ ] Click link rapidly multiple times
- [ ] **Check:** Only one navigation occurs
- [ ] **Check:** No duplicate requests

#### Back Button Spam:
- [ ] Navigate forward several pages
- [ ] Click back button rapidly
- [ ] **Check:** Handles gracefully
- [ ] **Check:** Ends up in correct state

#### Form Submit + Back:
- [ ] Submit form
- [ ] Immediately click back button
- [ ] **Check:** Handles gracefully
- [ ] **Check:** Form submission completes or cancels cleanly

#### Concurrent Navigations:
- [ ] Click link
- [ ] While loading, click another link
- [ ] **Check:** Second navigation cancels first
- [ ] **Check:** Ends up at second destination

### Performance Benchmarks to Record:

| Metric | Target | Actual |
|--------|--------|--------|
| Page navigation time | < 500ms | ___ms |
| Back button (cache) | < 100ms | ___ms |
| Memory usage (10 pages) | No leaks | Pass/Fail |
| Form submission | < 1s | ___ms |
| Modal open/close | < 300ms | ___ms |

### Expected Results
- ✅ Works in all major browsers
- ✅ Good performance (faster than before)
- ✅ No memory leaks
- ✅ Handles errors gracefully
- ✅ Edge cases handled properly

### Issues to Document
For any issue found:
- Browser/version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Console errors (if any)

---

## **PHASE 10: Polish & Optimization**
**Duration:** 8 hours (full day)
**Risk:** Low
**Can Rollback:** N/A (polish only)

### Goal
Final optimizations and polish for production-ready Turbo implementation.

### 10.1 Turbo Configuration Tuning (2 hours)

#### Cache Configuration:
```javascript
// In assets/app.js

// Adjust cache expiration for different pages
document.addEventListener('turbo:before-cache', function() {
    // Don't cache pages with forms that have sensitive data
    const hasSensitiveForm = document.querySelector('form[data-sensitive="true"]');
    if (hasSensitiveForm) {
        // Tell Turbo not to cache this page
        event.detail.cached = false;
    }
});
```

#### Scroll Restoration:
```javascript
// Fine-tune scroll behavior
document.addEventListener('turbo:load', function() {
    // Custom scroll restoration logic if needed
});
```

#### Prefetching (Optional - for even faster navigation):
```javascript
// Enable prefetching on hover
import { Turbo } from '@hotwired/turbo';

// This will prefetch pages when user hovers over links
// Turbo.session.preloadOnHover = true; // Experimental feature
```

### 10.2 Loading States & UX Polish (2 hours)

#### Add Loading Indicator for Slow Requests:
```javascript
// In assets/app.js or new file

let loadingTimeout;

document.addEventListener('turbo:before-visit', function() {
    // Show loading indicator after 500ms
    loadingTimeout = setTimeout(() => {
        document.body.classList.add('turbo-loading');
    }, 500);
});

document.addEventListener('turbo:load', function() {
    clearTimeout(loadingTimeout);
    document.body.classList.remove('turbo-loading');
});
```

**Add CSS:**
```css
/* In assets/styles/app.css */

body.turbo-loading {
    cursor: wait;
}

body.turbo-loading::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.1);
    z-index: 9998;
    pointer-events: none;
}
```

#### Enhance Progress Bar (Optional):
```css
/* Make progress bar more visible */
.turbo-progress-bar {
    height: 4px; /* Slightly thicker */
    box-shadow: 0 0 15px rgba(102, 126, 234, 0.7); /* More glow */
}
```

### 10.3 Conditional Turbo Control (1 hour)

#### Add Data Attributes for Turbo Control:

**Disable Turbo for specific links:**
```twig
{# For external links or downloads #}
<a href="{{ path('download_pdf') }}" data-turbo="false">
    Download PDF
</a>

{# For links that need full reload #}
<a href="{{ path('special_page') }}" data-turbo="false">
    Special Page
</a>
```

**Disable Turbo for specific forms:**
```twig
{# For file upload forms #}
<form method="post" enctype="multipart/form-data" data-turbo="false">
    <!-- File upload -->
</form>
```

**Confirm before navigation:**
```twig
<a href="{{ path('delete_all') }}" data-turbo-confirm="Are you sure?">
    Delete All
</a>
```

### 10.4 Production Optimization (2 hours)

#### Disable Turbo Logging in Production:

**File:** `templates/base.html.twig`

```twig
{% if app.environment == 'dev' %}
<script data-turbo-eval="false">
    // Only enable verbose logging in dev
    if (typeof Turbo !== 'undefined') {
        // All the console.log statements from Phase 2
    }
</script>
{% endif %}
```

#### Or make it configurable:
```javascript
// In assets/app.js

const TURBO_DEBUG = {{ app.environment == 'dev' ? 'true' : 'false' }};

if (TURBO_DEBUG) {
    // Attach all event listeners with console.log
}
```

#### Optimize Asset Loading:
```bash
# Clear cache
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup --env=prod

# Install importmap
php bin/console importmap:install
```

### 10.5 Documentation (1 hour)

#### Update CLAUDE.md:

Add Turbo section:
```markdown
## 🚀 TURBO DRIVE

**Status:** ✅ Enabled globally

**Features:**
- Smooth page transitions (no white flash)
- XHR-based navigation
- Progress bar during page loads
- Preserved scroll positions
- Browser cache for instant back/forward

**Excluded Pages:**
- Admin Audit Log (`admin_audit_*` routes)
- Any page with `<meta name="turbo-visit-control" content="reload">`

**Disabling Turbo for specific elements:**
```twig
<a href="/path" data-turbo="false">Full reload</a>
<form data-turbo="false">Traditional submit</form>
```

**Turbo Events:**
- `turbo:load` - Page loaded/navigated
- `turbo:before-cache` - Before page cached
- `turbo:submit-start` - Form submission started
- `turbo:submit-end` - Form submission ended

**Debugging:**
Open console in dev environment to see Turbo event logs.
```

#### Create Troubleshooting Guide:

**File:** `TURBO_TROUBLESHOOTING.md`

```markdown
# Turbo Troubleshooting Guide

## Common Issues

### Issue: Page breaks after Turbo navigation

**Cause:** JavaScript relies on DOMContentLoaded which doesn't fire on Turbo navigation

**Solution:**
```javascript
// WRONG
document.addEventListener('DOMContentLoaded', init);

// RIGHT
function init() { ... }
document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);
```

### Issue: Duplicate elements (tooltips, players, etc.)

**Cause:** Not cleaning up before Turbo caches page

**Solution:**
```javascript
document.addEventListener('turbo:before-cache', function() {
    // Cleanup tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        bootstrap.Tooltip.getInstance(el)?.dispose();
    });

    // Cleanup video player
    if (window.player) {
        window.player.destroy();
        window.player = null;
    }
});
```

### Issue: Forms not submitting

**Check:**
1. CSRF token present?
2. Form has proper method?
3. Check console for errors
4. Try adding `data-turbo="false"` to isolate issue

### Issue: Modal not opening after navigation

**Cause:** Bootstrap not re-initialized

**Solution:** Already handled in base.html.twig initialization

## Debugging Tips

1. **Check Network tab:** Should see XHR requests, not Doc type
2. **Check Console:** Look for Turbo event logs
3. **Add breakpoints:** In turbo:load event listener
4. **Disable Turbo temporarily:** Add `data-turbo="false"` to test
```

### 10.6 Final Testing Checklist (2 hours)

#### Complete Application Test:
- [ ] Run through ALL Phase 7 tests again
- [ ] Verify all issues from Phase 9 are fixed
- [ ] Check all console logs are appropriate
- [ ] Verify production-ready (no verbose logs in prod)
- [ ] Test cache clearing works
- [ ] Test in private/incognito mode

#### Performance Verification:
- [ ] Navigation feels faster than before
- [ ] Progress bar appears appropriately
- [ ] No memory leaks after extended use
- [ ] Back button is instant

#### Code Quality:
- [ ] No console errors in any page
- [ ] No console warnings (or documented as expected)
- [ ] Code is clean and documented
- [ ] Patterns are consistent

### Expected Results
- ✅ Production-ready Turbo implementation
- ✅ Optimized performance
- ✅ Good UX with loading states
- ✅ Proper documentation
- ✅ Troubleshooting guide available

### Changes Summary
**Modified:** Various files for optimization
**Added:** Documentation files
**Deleted:** Verbose logging in production

---

## 📊 FINAL SUMMARY

### Implementation Checklist

- [ ] **Phase 1:** Controller navigation fixes (8 files)
- [ ] **Phase 2:** base.html.twig script fixes
- [ ] **Phase 3:** _base_entity_list.html.twig fixes
- [ ] **Phase 4:** Navigate controller + remove inline onclick (5 templates)
- [ ] **Phase 5:** Organization switcher controller
- [ ] **Phase 6:** Enable Turbo + progress bar + logging
- [ ] **Phase 7:** Full feature testing
- [ ] **Phase 8:** Fix remaining DOMContentLoaded issues
- [ ] **Phase 9:** Browser & performance testing
- [ ] **Phase 10:** Polish & optimization

### Success Criteria

✅ **Navigation:**
- Smooth page transitions (no white flash)
- Progress bar shows during loading
- Back/forward buttons work perfectly
- Scroll positions preserved

✅ **Forms & Modals:**
- All forms submit correctly
- Modals open/close properly
- Validation works
- CSRF protection maintained

✅ **Features:**
- Search works
- Video player works (no duplicates)
- Drag-drop works
- Enrollments work
- Organization switcher works

✅ **Performance:**
- Faster perceived performance
- No memory leaks
- Good browser caching
- Reduced server load

✅ **Quality:**
- No console errors
- Cross-browser compatible
- Well documented
- Production ready

### Rollback Procedure

If anything goes wrong:

1. **Immediate rollback:**
   ```javascript
   // In assets/app.js
   // import '@hotwired/turbo';  // Comment this line
   ```

2. **Clear cache:**
   ```bash
   php bin/console cache:clear
   ```

3. **Hard refresh browser:**
   - Ctrl+Shift+Delete (clear cache)
   - Ctrl+F5 (hard refresh)

4. **Verify:** Site works without Turbo

**All Phase 1-5 changes are improvements even without Turbo!**

### Timeline Summary

| Phase | Duration | Type |
|-------|----------|------|
| 1 | 3-4h | Code fixes |
| 2 | 2h | Code fixes |
| 3 | 2h | Code fixes |
| 4 | 4-5h | Code + templates |
| 5 | 2h | Code + templates |
| **6** | **1-2h** | **ACTIVATION** ⚠️ |
| 7 | 8h | Testing |
| 8 | 3-4h | Code fixes |
| 9 | 8h | Testing |
| 10 | 8h | Polish |

**Total: ~40-45 hours (7-8 working days)**

### Files Modified Summary

**JavaScript Files:** ~10 files
- 8 controller files (Phase 1)
- 2 new controllers (Phases 4-5)
- app.js (Phase 6)

**Template Files:** ~15 files
- base.html.twig (Phases 2, 6)
- _base_entity_list.html.twig (Phase 3)
- 5 templates with onclick (Phase 4)
- 6-8 templates with DOMContentLoaded (Phase 8)

**CSS Files:** 1 file
- app.css (Phase 6 - progress bar)

**Documentation:** 2 files
- CLAUDE.md update (Phase 10)
- TURBO_TROUBLESHOOTING.md (Phase 10)

**Total Modified:** ~28 files
**Total Created:** 4 files (2 controllers + 2 docs)

---

## 🎯 READY TO IMPLEMENT

This plan is now complete and ready for implementation by Claude Code.

**Each phase includes:**
- ✅ Clear goals
- ✅ Exact files to modify
- ✅ Code examples
- ✅ Test checklists
- ✅ Expected results
- ✅ Changes summary

**Safeguards:**
- ✅ Phases 1-5 work WITHOUT Turbo
- ✅ Clear rollback procedure
- ✅ Multiple testing phases
- ✅ Audit log excluded from Turbo
- ✅ TreeFlow included in Turbo

**The implementation can now proceed phase by phase with confidence.**

---

**Document Created:** {{ "now"|date("Y-m-d H:i:s") }}
**Plan Version:** 1.0 (Final)
**Status:** Ready for Implementation
