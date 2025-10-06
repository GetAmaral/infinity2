# 🚀 TURBO MIGRATION PLAN V2 - INFINITY APPLICATION

**Project:** Infinity Symfony Application
**Goal:** Implement Hotwire Turbo across entire application
**Approach:** Full migration with comprehensive testing
**Timeline:** 5-6 days (30-35 hours)
**Risk Level:** Medium

**Date Created:** 2025-10-06
**Plan Version:** 2.0 (Updated after comprehensive codebase investigation)

---

## 📊 EXECUTIVE SUMMARY

This plan implements Turbo Drive across the Infinity application to achieve SPA-like navigation without page reloads. The migration is broken into 8 focused phases with clear rollback capability.

**Key Findings from Investigation:**
- ✅ Turbo package installed (`symfony/ux-turbo ^2.30`, `@hotwired/turbo 7.3.0`)
- ✅ CSRF protection already Turbo-aware
- ✅ PreferenceManager already Turbo-compatible
- ✅ Base template has partial Turbo integration
- ❌ **Turbo NOT imported in app.js** - Currently dormant
- 📁 **83 template files** total
- 📁 **21 Stimulus controllers**
- 🔧 **15 window.location instances** across 8 files (not 8 instances as originally reported)
- 📝 **50+ inline onclick handlers** across entity list templates

---

## 🎯 SUCCESS CRITERIA

**Navigation:**
- ✅ Smooth page transitions (no white flash)
- ✅ Progress bar shows during loading
- ✅ Back/forward buttons work perfectly
- ✅ Scroll positions preserved

**Forms & Modals:**
- ✅ All forms submit correctly
- ✅ Modals open/close properly
- ✅ Validation works
- ✅ CSRF protection maintained

**Features:**
- ✅ Search works
- ✅ Video player works (no duplicates)
- ✅ Drag-drop works
- ✅ Enrollments work
- ✅ Organization switcher works
- ✅ TreeFlow canvas works

**Performance:**
- ✅ Faster perceived performance
- ✅ No memory leaks
- ✅ Good browser caching
- ✅ Reduced server load

**Quality:**
- ✅ No console errors
- ✅ Cross-browser compatible
- ✅ Well documented
- ✅ Production ready

---

## 📋 PHASE-BY-PHASE IMPLEMENTATION

---

## **PHASE 1: Fix Controller Navigation (8 Files, 15 Instances)**
**Duration:** 4-5 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Replace all `window.location` calls in JavaScript files with Turbo-compatible code.

### Files to Modify

#### 1. **`assets/delete-handler.js`** (2 instances)

**Line 101:**
```javascript
// BEFORE
window.location.href = '/treeflow';

// AFTER
if (typeof Turbo !== 'undefined') {
    Turbo.visit('/treeflow');
} else {
    window.location.href = '/treeflow';
}
```

**Line 324:**
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

#### 2. **`assets/controllers/session_monitor_controller.js`** (2 instances)

**Line 636:**
```javascript
// BEFORE
window.location.href = '/login?expired=1';

// AFTER
if (typeof Turbo !== 'undefined') {
    Turbo.visit('/login?expired=1');
} else {
    window.location.href = '/login?expired=1';
}
```

**Line 822 (inline onclick in modal HTML):**
```javascript
// BEFORE
<button type="button" class="btn btn-secondary" onclick="window.location.href='/logout'">Logout Now</button>

// AFTER
<button type="button" class="btn btn-secondary" onclick="if(typeof Turbo !== 'undefined') { Turbo.visit('/logout'); } else { window.location.href='/logout'; }">Logout Now</button>
```

#### 3. **`assets/controllers/treeflow_canvas_controller.js`** (4 instances)

**Line 81 (NO CHANGE - reading URL, not navigating):**
```javascript
// Keep as-is - just reading current URL
const response = await fetch(window.location.href, {
```

**Line 112:**
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

**Line 1055:**
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

**Line 1442:**
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

#### 4. **`assets/controllers/module_lecture_reorder_controller.js`** (1 instance)

**Line 228:**
```javascript
// BEFORE
setTimeout(() => {
    window.location.reload();
}, 1000);

// AFTER
setTimeout(() => {
    if (typeof Turbo !== 'undefined') {
        Turbo.cache.clear();
        Turbo.visit(window.location, { action: 'replace' });
    } else {
        window.location.reload();
    }
}, 1000);
```

#### 5. **`assets/controllers/lecture_processing_controller.js`** (1 instance)

**Line 58:**
```javascript
// BEFORE
setTimeout(() => {
    window.location.reload();
}, 2000);

// AFTER
setTimeout(() => {
    if (typeof Turbo !== 'undefined') {
        Turbo.cache.clear();
        Turbo.visit(window.location, { action: 'replace' });
    } else {
        window.location.reload();
    }
}, 2000);
```

#### 6. **`assets/controllers/enrollment_switch_controller.js`** (1 instance)

**Line 172:**
```javascript
// BEFORE
setTimeout(() => {
    window.location.reload();
}, 1500);

// AFTER
setTimeout(() => {
    if (typeof Turbo !== 'undefined') {
        Turbo.cache.clear();
        Turbo.visit(window.location, { action: 'replace' });
    } else {
        window.location.reload();
    }
}, 1500);
```

#### 7. **`assets/controllers/course_enrollment_controller.js`** (1 instance)

**Line 276:**
```javascript
// BEFORE
setTimeout(() => {
    window.location.reload();
}, 1500);

// AFTER
setTimeout(() => {
    if (typeof Turbo !== 'undefined') {
        Turbo.cache.clear();
        Turbo.visit(window.location, { action: 'replace' });
    } else {
        window.location.reload();
    }
}, 1500);
```

#### 8. **`assets/controllers/crud_modal_controller.js`** (2 instances)

**Line 119:**
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

**Line 136:**
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

#### 9. **`assets/controllers/live_search_controller.js`** (1 instance)

**Line 184:**
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
- [ ] Session timeout → Redirects to login
- [ ] TreeFlow canvas operations work
- [ ] **No console errors**
- [ ] **Everything works exactly as before**

### Expected Result
No visual changes. All functionality works identically. Code is now Turbo-ready but Turbo is not active yet.

### Changes Summary
**Modified:** 8 JavaScript files (15 total modifications)
**Added:** 0 files
**Deleted:** 0 files

---

## **PHASE 2: Enhance base.html.twig Turbo Integration**
**Duration:** 2 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Enhance existing partial Turbo integration in base template to full Turbo support.

### File to Modify

**`templates/base.html.twig`**

### Current State Analysis

**Already Present (Keep these):**
- Line 22: `<script src="/preference-manager.js" data-turbo-eval="false"></script>`
- Line 177: `<script data-turbo-eval="false">` wrapper for global scripts
- Lines 274-288: Turbo event listeners for tooltips (`turbo:load`, `turbo:render`)
- Lines 377-396: Turbo event listeners for preferences

**What's Missing:**
- Turbo-specific cleanup before caching
- Bootstrap dropdown cleanup
- Comprehensive event logging for debugging

### Changes

**Add after line 288 (after existing Turbo tooltip handlers):**

```javascript
// ============================================
// TURBO CLEANUP (Before page is cached)
// ============================================
document.addEventListener('turbo:before-cache', function() {
    console.log('🧹 Turbo: Cleaning up page before cache');

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
});
```

**Add Turbo event logging for development (after turbo:before-cache handler):**

```javascript
{% if app.environment == 'dev' %}
// ============================================
// TURBO EVENT LOGGING (Development Only)
// ============================================
if (typeof Turbo !== 'undefined') {
    console.log('🎯 Turbo event logging enabled (dev mode)');

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

    document.addEventListener('turbo:before-render', (event) => {
        console.log('🎨 Turbo: About to render');
    });

    document.addEventListener('turbo:render', () => {
        console.log('✨ Turbo: Page rendered');
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
{% endif %}
```

### Test Checklist
- [ ] Page loads normally (full refresh)
- [ ] Theme switcher works
- [ ] Theme persists on refresh
- [ ] Tooltips appear on hover
- [ ] Dropdowns open/close
- [ ] Organization switcher works
- [ ] Check console: Should see "📄 DOMContentLoaded fired"
- [ ] Check console: Should NOT see Turbo logs yet (Turbo not active)
- [ ] **No console errors**

### Expected Result
Enhanced cleanup and logging infrastructure ready for Turbo activation. No behavior changes yet.

### Changes Summary
**Modified:** 1 file (`templates/base.html.twig`)
**Added:** Cleanup handlers and debug logging
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

### Current Issue

Lines 749-789 have `DOMContentLoaded` listener that won't fire on Turbo navigation.

### Changes

**Find the DOMContentLoaded listener (around line 749) and replace:**

```javascript
// BEFORE
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('{{ entity_name }}SearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    // ... rest of code
});

// AFTER
function initializeEntityList() {
    console.log('📋 Initializing entity list: {{ entity_name }}');

    const searchInput = document.getElementById('{{ entity_name }}SearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const originalContent = document.getElementById('{{ entity_name_plural }}-grid')?.innerHTML;
    let isSearching = false;

    if (searchInput && clearBtn) {
        // Track search state
        searchInput.addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                isSearching = true;
            } else {
                if (isSearching) {
                    restoreOriginalContent();
                    isSearching = false;
                }
            }
        });

        // Clear button click handler
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = '';
            restoreOriginalContent();
            searchInput.focus();
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // ESC key handler
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                e.preventDefault();
                this.value = '';
                restoreOriginalContent();
                this.blur();
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    function restoreOriginalContent() {
        const grid = document.getElementById('{{ entity_name_plural }}-grid');
        if (grid && originalContent) {
            grid.innerHTML = originalContent;
        }
    }
}

// Support both events
document.addEventListener('DOMContentLoaded', initializeEntityList);
document.addEventListener('turbo:load', initializeEntityList);

// Cleanup before Turbo caches page
document.addEventListener('turbo:before-cache', function() {
    console.log('🧹 Cleaning up entity list');
    // No specific cleanup needed for this component
});
```

### Test Checklist
- [ ] Grid view displays correctly
- [ ] List view displays correctly
- [ ] Table view displays correctly
- [ ] Switch between views → Preference persists
- [ ] Search works
- [ ] Clear search works
- [ ] ESC key clears search
- [ ] Navigate away and back → View preference remembered
- [ ] Navigate to different entity → Come back → Search state preserved
- [ ] Check console: Should see "📋 Initializing entity list"
- [ ] **No console errors**

### Expected Result
All list functionality works. View preferences persist across page navigation. Search functionality ready for Turbo.

### Changes Summary
**Modified:** 1 file (`templates/_base_entity_list.html.twig`)
**Added:** Turbo event support
**Deleted:** 0 files

---

## **PHASE 4: Fix Template DOMContentLoaded Issues**
**Duration:** 2-3 hours
**Risk:** Low
**Can Rollback:** Yes

### Goal
Fix remaining templates with DOMContentLoaded listeners to work with Turbo navigation.

### Files to Modify

#### 1. **`templates/organization/users.html.twig`** (Lines 469-494)

**Current:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Tooltip initialization
    initializeTooltips();
});
```

**Fix:**
```javascript
function initializePageTooltips() {
    console.log('💡 Initializing tooltips for organization users page');

    async function initializeTooltips() {
        if (window.bootstrapReady) {
            await window.bootstrapReady;
        }

        if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            return;
        }

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover',
                delay: { show: 300, hide: 100 }
            });
        });
    }

    initializeTooltips();
}

// Support both events
document.addEventListener('DOMContentLoaded', initializePageTooltips);
document.addEventListener('turbo:load', initializePageTooltips);
```

**Note:** This is somewhat redundant since base.html.twig already handles tooltips globally. Consider removing if no issues.

#### 2. **`templates/security/login.html.twig`** (Lines 152-164)

**Current:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const passwordInput = document.getElementById('password');

    if (passwordInput && loginForm) {
        passwordInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                loginForm.submit();
            }
        });
    }
});
```

**Fix:**
```javascript
function initializeLoginForm() {
    console.log('🔐 Initializing login form');

    const loginForm = document.getElementById('loginForm');
    const passwordInput = document.getElementById('password');

    if (passwordInput && loginForm) {
        passwordInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                loginForm.submit();
            }
        });
    }
}

// Support both events
document.addEventListener('DOMContentLoaded', initializeLoginForm);
document.addEventListener('turbo:load', initializeLoginForm);
```

**Note:** Login pages typically don't use Turbo navigation, but this fix ensures compatibility.

#### 3. **`templates/student/lecture.html.twig`** (Lines 316-329)

**Current:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Init] DOM Content Loaded');

    // Initialize Bootstrap tooltips if bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        console.log('[Init] Tooltips initialized');
    } else {
        console.warn('[Init] Bootstrap not loaded, skipping tooltip initialization');
    }
});
```

**Fix:**
```javascript
function initializeLecturePage() {
    console.log('[Init] Lecture page initialization');

    // Initialize Bootstrap tooltips if bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            // Dispose existing tooltip first
            const existing = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existing) existing.dispose();
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        console.log('[Init] Tooltips initialized:', tooltipList.length);
    } else {
        console.warn('[Init] Bootstrap not loaded, skipping tooltip initialization');
    }
}

// Support both events
document.addEventListener('DOMContentLoaded', initializeLecturePage);
document.addEventListener('turbo:load', initializeLecturePage);

// Cleanup video player before navigation
document.addEventListener('turbo:before-visit', function() {
    console.log('[Cleanup] Cleaning up lecture page');

    // If video player exists, destroy it
    if (window.player) {
        console.log('[Cleanup] Destroying video player');
        window.player.destroy();
        window.player = null;
    }
});
```

#### 4. **`templates/admin/audit/index.html.twig`** (Lines 989-1012)

**Current:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+F to focus first filter
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const firstInput = document.querySelector('#filterSection select, #filterSection input');
            if (firstInput) firstInput.focus();
        }
    });

    // Update last updated time
    setInterval(function() {
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                       now.getMinutes().toString().padStart(2, '0') + ':' +
                       now.getSeconds().toString().padStart(2, '0');
        const lastUpdate = document.getElementById('lastUpdate');
        if (lastUpdate) {
            lastUpdate.textContent = timeStr;
        }
    }, 1000);
});
```

**Fix:**
```javascript
let clockInterval = null;
let keydownHandler = null;

function initializeAuditPage() {
    console.log('📊 Initializing audit page');

    // Clean up previous handlers if any
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }
    if (keydownHandler) {
        document.removeEventListener('keydown', keydownHandler);
        keydownHandler = null;
    }

    // Keyboard shortcuts
    keydownHandler = function(e) {
        // Ctrl+F to focus first filter
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const firstInput = document.querySelector('#filterSection select, #filterSection input');
            if (firstInput) firstInput.focus();
        }
    };
    document.addEventListener('keydown', keydownHandler);

    // Update last updated time
    clockInterval = setInterval(function() {
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                       now.getMinutes().toString().padStart(2, '0') + ':' +
                       now.getSeconds().toString().padStart(2, '0');
        const lastUpdate = document.getElementById('lastUpdate');
        if (lastUpdate) {
            lastUpdate.textContent = timeStr;
        }
    }, 1000);
}

// Support both events
document.addEventListener('DOMContentLoaded', initializeAuditPage);
document.addEventListener('turbo:load', initializeAuditPage);

// Cleanup before navigation
document.addEventListener('turbo:before-visit', function() {
    console.log('🧹 Cleaning up audit page');
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }
    if (keydownHandler) {
        document.removeEventListener('keydown', keydownHandler);
        keydownHandler = null;
    }
});
```

### Test Checklist
- [ ] Organization users page → Tooltips work
- [ ] Navigate away and back → Tooltips still work
- [ ] Login page → Enter key submits form
- [ ] Student lecture page → Video player loads
- [ ] Navigate away from lecture → Come back → No duplicate players
- [ ] Audit page → Ctrl+F focuses filter
- [ ] Audit page → Clock updates every second
- [ ] Navigate away from audit → Clock stops
- [ ] Come back to audit → Clock starts fresh
- [ ] Check console: Should see initialization logs
- [ ] **No console errors**

### Expected Result
All pages work with Turbo navigation. No duplicate initializations. Proper cleanup on navigation.

### Changes Summary
**Modified:** 4 template files
**Added:** Turbo event support and cleanup
**Deleted:** 0 files

---

## **PHASE 5: ENABLE TURBO + PROGRESS BAR** 🚀
**Duration:** 1-2 hours
**Risk:** HIGH ⚠️
**Can Rollback:** YES

### Goal
1. Activate Turbo Drive globally by importing in app.js
2. Add Turbo progress bar CSS
3. Exclude Admin Audit pages from Turbo (require full reload)
4. Verify everything works

### Part A: Import Turbo in app.js

**File:** `assets/app.js`

**Add at the very top (BEFORE other imports):**

```javascript
// ============================================
// TURBO IMPORT & CONFIGURATION
// ============================================
import * as Turbo from '@hotwired/turbo';

console.log('🚀 Turbo Drive enabled');

// Turbo configuration
Turbo.setProgressBarDelay(100); // Show progress bar after 100ms

// ============================================
// REST OF EXISTING IMPORTS
// ============================================
import { startStimulusApp } from '@symfony/stimulus-bundle';
import * as bootstrap from 'bootstrap';
// ... rest of existing imports
```

### Part B: Add Turbo Progress Bar CSS

**File:** `assets/styles/app.css`

**Add at the end of the file:**

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
    background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
    z-index: 9999;
    transition: width 300ms ease-out, opacity 150ms 150ms ease-in;
    transform: translate3d(0, 0, 0);
    box-shadow: 0 0 10px rgba(79, 70, 229, 0.5);
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
    background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
    box-shadow: 0 0 10px rgba(79, 70, 229, 0.7);
}

/* Light theme variant */
[data-theme="light"] .turbo-progress-bar {
    background: linear-gradient(90deg, #4338ca 0%, #6d28d9 100%);
    box-shadow: 0 0 10px rgba(67, 56, 202, 0.5);
}
```

### Part C: Exclude Admin Audit Pages from Turbo

**File:** `templates/base.html.twig`

**Add in the `<head>` section (after viewport meta tag, around line 7):**

```twig
{# Exclude admin audit pages from Turbo (require full reload for data integrity) #}
{% set currentRoute = app.request.attributes.get('_route') %}
{% if currentRoute starts with 'admin_audit' %}
    <meta name="turbo-visit-control" content="reload">
    <meta name="turbo-cache-control" content="no-cache">
{% endif %}
```

### Test Checklist (CRITICAL - Test Everything!)

#### Basic Navigation:
- [ ] **Open browser DevTools → Network tab**
- [ ] Click any link (e.g., Home → Organizations)
- [ ] **Verify:** Network shows XHR/Fetch request, NOT full page load (Document type)
- [ ] **Verify:** No white flash during navigation
- [ ] **Verify:** Turbo progress bar appears at top (blue/purple gradient line)
- [ ] Check console: Should see "🖱️ Turbo: Link clicked" (if dev mode)
- [ ] Check console: Should see "🚀 Turbo: Starting visit"
- [ ] Check console: Should see "✅ Turbo: Navigation complete"

#### Browser Controls:
- [ ] Click back button → Smooth navigation back
- [ ] Click forward button → Smooth navigation forward
- [ ] Press F5 (refresh) → Full page reload (expected)
- [ ] Scroll down page → Navigate → Come back → Scroll position restored

#### Admin Audit Pages (MUST BE EXCLUDED):
- [ ] Navigate to Organizations (smooth Turbo nav)
- [ ] Navigate to `/admin/audit` (if accessible as admin)
- [ ] **Verify:** Network shows **full page load** (Document type, NOT XHR)
- [ ] **Verify:** NO Turbo logs in console
- [ ] Audit page works normally
- [ ] Navigate away from Audit → **Turbo resumes** (smooth nav to next page)

#### Core Features Quick Check:
- [ ] Organization list loads
- [ ] Click organization card → Detail page opens (smooth)
- [ ] Course list loads
- [ ] User list loads
- [ ] TreeFlow list loads
- [ ] Organization switcher works (if admin)
- [ ] Theme toggle works
- [ ] Tooltips work

#### Error Checking:
- [ ] **Open console → Check for ANY red errors**
- [ ] **If ANY errors, STOP immediately and report below**

### 🚨 CRITICAL CHECKPOINT

**IF ANY OF THESE FAIL, STOP IMMEDIATELY:**
- ❌ Console has errors
- ❌ Navigation breaks
- ❌ Forms don't submit
- ❌ Modals don't open
- ❌ Page goes blank
- ❌ Infinite loading

**ROLLBACK PROCEDURE:**
1. Edit `assets/app.js`
2. Comment out Turbo import:
   ```javascript
   // import * as Turbo from '@hotwired/turbo';
   ```
3. Clear browser cache (Ctrl+Shift+Delete)
4. Hard refresh (Ctrl+F5)
5. Verify site works without Turbo

### Expected Result After Successful Activation
- ✅ Smooth page transitions (no white flash)
- ✅ Blue/purple progress bar appears during navigation
- ✅ Network tab shows XHR/Fetch requests (except Audit pages)
- ✅ Back/forward buttons work smoothly
- ✅ Admin Audit pages do full reload
- ✅ Console shows Turbo event logs (in dev mode)
- ✅ **NO CONSOLE ERRORS**

### Changes Summary
**Modified:** 3 files (`assets/app.js`, `assets/styles/app.css`, `templates/base.html.twig`)
**Added:** Turbo import, progress bar CSS, audit page exclusion
**Deleted:** 0 files

---

## **PHASE 6: Comprehensive Feature Testing**
**Duration:** 8 hours (full day)
**Risk:** Medium
**Can Rollback:** Yes

### Goal
Test all major features of the application to ensure Turbo compatibility.

### 6.1 Navigation Testing (1 hour)

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

#### Console Checks:
- [ ] See `turbo:click` for each link (dev mode)
- [ ] See `turbo:load` after navigation
- [ ] No errors

### 6.2 Forms & Modals Testing (2 hours)

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
  - [ ] Smooth Turbo navigation

- [ ] Edit organization → Save
  - [ ] Modal opens with existing data
  - [ ] Save works
  - [ ] Changes reflected

- [ ] Delete organization → Confirm
  - [ ] Confirmation works
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
- [ ] See `turbo:submit-start` on form submit
- [ ] See `turbo:submit-end` after response
- [ ] No errors

### 6.3 Complex Features Testing (2 hours)

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
  - [ ] Count updates

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
  - [ ] Page updates (Turbo navigation)

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

#### TreeFlow Canvas:
- [ ] Open TreeFlow detail
- [ ] Canvas renders correctly
- [ ] Create new step node
  - [ ] Node appears
  - [ ] Can drag node

- [ ] Delete step from canvas
  - [ ] Confirmation appears
  - [ ] Canvas refreshes via Turbo

- [ ] Navigate away and back
  - [ ] Canvas re-initializes correctly
  - [ ] **No duplicate event listeners**

#### Console Checks:
- [ ] No errors during video playback
- [ ] No errors during drag-drop
- [ ] No errors on TreeFlow canvas
- [ ] Video player cleanup on navigation

### 6.4 Search & Filters Testing (1 hour)

#### Live Search:
- [ ] Organizations page → Search
  - [ ] Type in search box
  - [ ] Results filter in real-time
  - [ ] Debouncing works (not searching on every keystroke)
  - [ ] Clear search → All results return
  - [ ] ESC key clears search

- [ ] Courses page → Search
  - [ ] Same checks

- [ ] Users page → Search
  - [ ] Same checks

#### Click Search Result:
- [ ] Search for item
- [ ] Click result
  - [ ] Navigate to detail page
  - [ ] Smooth Turbo navigation

#### Console Checks:
- [ ] See `turbo:visit` on result click
- [ ] No errors

### 6.5 View Toggles Testing (1 hour)

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

### 6.6 Organization Switcher Testing (30 min)

**Admin users only:**

- [ ] Click organization dropdown
  - [ ] Dropdown opens
  - [ ] Shows all organizations
  - [ ] Current org highlighted

- [ ] Click different organization
  - [ ] Dropdown closes
  - [ ] **Smooth transition (Turbo navigation)**
  - [ ] Page updates with new org context
  - [ ] Check console: See `turbo:submit-start`

- [ ] Click "All Organizations"
  - [ ] Clears organization
  - [ ] Smooth transition
  - [ ] Can see all data

#### Console Checks:
- [ ] See Turbo form submission events
- [ ] No errors

### 6.7 Theme & Preferences Testing (30 min)

#### Theme Toggle:
- [ ] Current theme displays (dark/light)
- [ ] Click theme toggle
  - [ ] Theme switches immediately
  - [ ] PreferenceManager saves

- [ ] Navigate to another page (Turbo)
  - [ ] **Theme persists**
  - [ ] Check console: See preference load from localStorage

- [ ] Refresh page (full reload)
  - [ ] Theme still correct

#### Tooltips:
- [ ] Hover over element with tooltip
  - [ ] Tooltip appears

- [ ] Navigate to another page (Turbo)
- [ ] Hover over tooltip element
  - [ ] **Tooltip still works**

- [ ] Check console: See tooltip initialization

#### Dropdowns:
- [ ] Open navbar dropdown
  - [ ] Dropdown opens

- [ ] Navigate to another page (Turbo)
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

## **PHASE 7: Browser & Performance Testing**
**Duration:** 4 hours
**Risk:** Low
**Can Rollback:** N/A (testing only)

### Goal
Verify Turbo works across browsers and performs well.

### 7.1 Cross-Browser Testing (2 hours)

#### Chrome/Chromium-based:
- [ ] Navigation works smoothly
- [ ] Forms submit correctly
- [ ] Modals work
- [ ] Video player works
- [ ] No console errors

#### Firefox:
- [ ] Navigation works smoothly
- [ ] Forms submit correctly
- [ ] Modals work
- [ ] Video player works
- [ ] No console errors

#### Safari (if available):
- [ ] Navigation works
- [ ] Forms work
- [ ] Modals work

#### Mobile Browsers (if available):
- [ ] Mobile Chrome - Navigation, forms, modals
- [ ] Mobile Safari - Navigation, forms, modals
- [ ] Touch interactions work

### 7.2 Performance Testing (2 hours)

#### Memory Leak Check:
- [ ] Open DevTools → Performance tab
- [ ] Navigate between 10 different pages
- [ ] Return to starting page
- [ ] **Check:** Memory should return to ~baseline

#### Navigation Speed:
- [ ] Open DevTools → Network tab
- [ ] Navigate between pages
- [ ] **Measure:** Time to complete navigation
- [ ] **Compare:** Faster than full page reload
- [ ] **Check:** Progress bar appears for slow requests

#### Cache Behavior:
- [ ] Navigate to page
- [ ] Click back button
- [ ] **Check:** Page appears instantly from cache
- [ ] **Check:** Network tab shows "from cache"

#### Error Handling:
- [ ] Navigate to page
- [ ] Open DevTools → Network → Offline
- [ ] Click link
- [ ] **Check:** Error handling works

---

## **PHASE 8: Polish & Documentation**
**Duration:** 4 hours
**Risk:** Low
**Can Rollback:** N/A (documentation only)

### Goal
Final polish and documentation for production.

### 8.1 Production Optimization (2 hours)

#### Remove Verbose Logging

Already implemented in `templates/base.html.twig`:
```twig
{% if app.environment == 'dev' %}
    // Turbo event logging only in dev
{% endif %}
```

No changes needed - already production-ready.

#### Verify Asset Loading

```bash
# Clear cache
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup --env=prod

# Install importmap
php bin/console importmap:install
```

### 8.2 Update Documentation (2 hours)

#### Update CLAUDE.md

Add Turbo section after Multi-Tenant section:

```markdown
## 🚀 TURBO DRIVE

### Status
✅ **Enabled globally** (as of 2025-10-06)

### Features
- Smooth page transitions (no white flash)
- XHR-based navigation (faster perceived performance)
- Progress bar during page loads
- Preserved scroll positions
- Browser cache for instant back/forward navigation
- Automatic form submission handling with CSRF protection

### Excluded Pages
- Admin Audit pages (`/admin/audit/*`)
- Any page with `<meta name="turbo-visit-control" content="reload">`

### Configuration

**Disabling Turbo for specific links:**
```html
<a href="/path" data-turbo="false">Full reload link</a>
```

**Disabling Turbo for specific forms:**
```html
<form data-turbo="false" method="post">
    <!-- Traditional form submission -->
</form>
```

**Confirmation before navigation:**
```html
<a href="/delete" data-turbo-confirm="Are you sure?">Delete</a>
```

### Turbo Events

For custom JavaScript that needs to work with Turbo:

```javascript
// Initialize on both full load and Turbo navigation
document.addEventListener('DOMContentLoaded', initFunction);
document.addEventListener('turbo:load', initFunction);

// Cleanup before Turbo caches page
document.addEventListener('turbo:before-cache', cleanupFunction);

// Before navigating away
document.addEventListener('turbo:before-visit', cleanupFunction);
```

**Available Events:**
- `turbo:load` - Page loaded/navigated
- `turbo:before-cache` - Before page cached
- `turbo:before-visit` - Before navigation starts
- `turbo:visit` - Navigation in progress
- `turbo:before-render` - Before new page renders
- `turbo:render` - Page rendered
- `turbo:submit-start` - Form submission started
- `turbo:submit-end` - Form submission ended

### Debugging

**Development mode** automatically logs Turbo events to console:
- 🖱️ Turbo: Link clicked
- 🚀 Turbo: Starting visit
- ✅ Turbo: Navigation complete
- 📤 Turbo: Form submission started
- 📥 Turbo: Form submission ended

**Check if Turbo is active:**
```javascript
console.log(typeof Turbo !== 'undefined' ? 'Turbo is active' : 'Turbo not loaded');
```

### Troubleshooting

**Issue: JavaScript not working after navigation**
- Add `turbo:load` listener in addition to `DOMContentLoaded`

**Issue: Duplicate elements (tooltips, players, etc.)**
- Add cleanup in `turbo:before-cache` or `turbo:before-visit` listener

**Issue: Forms not submitting**
- Check CSRF tokens are present
- Verify `csrf_protection_controller.js` is loaded

**Issue: Page not updating after form submit**
- Ensure controller returns redirect response or proper Turbo response

**Issue: Need to force full reload for specific page**
- Add `<meta name="turbo-visit-control" content="reload">` to page head
```

---

## 📊 FINAL SUMMARY

### Implementation Checklist

- [ ] **Phase 1:** Fix controller navigation (8 files, 15 instances)
- [ ] **Phase 2:** Enhance base.html.twig Turbo integration
- [ ] **Phase 3:** Fix _base_entity_list.html.twig
- [ ] **Phase 4:** Fix template DOMContentLoaded issues (4 files)
- [ ] **Phase 5:** Enable Turbo + progress bar + exclusions
- [ ] **Phase 6:** Full feature testing (8 hours)
- [ ] **Phase 7:** Browser & performance testing (4 hours)
- [ ] **Phase 8:** Polish & documentation (4 hours)

### Timeline Summary

| Phase | Duration | Type | Risk |
|-------|----------|------|------|
| 1 | 4-5h | Code fixes | Low |
| 2 | 2h | Code fixes | Low |
| 3 | 2h | Code fixes | Low |
| 4 | 2-3h | Code fixes | Low |
| **5** | **1-2h** | **ACTIVATION** | **HIGH** ⚠️ |
| 6 | 8h | Testing | Medium |
| 7 | 4h | Testing | Low |
| 8 | 4h | Documentation | Low |

**Total: 27-32 hours (5-6 working days)**

### Files Modified Summary

**JavaScript Files:** 8 files (15 modifications)
- Controllers with window.location fixes

**Template Files:** 6 files
- base.html.twig (enhanced)
- _base_entity_list.html.twig (fixed)
- 4 templates with DOMContentLoaded (fixed)

**CSS Files:** 1 file
- app.css (progress bar added)

**Documentation:** 1 file
- CLAUDE.md (Turbo section added)

**Total Modified:** ~16 files

### Rollback Procedure

If anything goes wrong:

1. **Immediate rollback:**
   ```javascript
   // In assets/app.js
   // import * as Turbo from '@hotwired/turbo';  // Comment this line
   ```

2. **Clear cache:**
   ```bash
   php bin/console cache:clear
   ```

3. **Hard refresh browser:**
   - Ctrl+Shift+Delete (clear cache)
   - Ctrl+F5 (hard refresh)

4. **Verify:** Site works without Turbo

**Important:** All Phase 1-4 changes are improvements even without Turbo active!

---

## 🎯 READY TO IMPLEMENT

This plan is comprehensive, tested against the actual codebase, and ready for implementation.

**Each phase includes:**
- ✅ Clear goals
- ✅ Exact files with correct line numbers
- ✅ Code examples
- ✅ Test checklists
- ✅ Expected results
- ✅ Changes summary

**Safeguards:**
- ✅ Phases 1-4 work WITHOUT Turbo active
- ✅ Clear rollback procedure
- ✅ Multiple testing phases
- ✅ Admin Audit excluded from Turbo
- ✅ TreeFlow included in Turbo
- ✅ All existing Turbo-aware code preserved

**The implementation can now proceed phase by phase with confidence.**

---

**Document Created:** 2025-10-06
**Plan Version:** 2.0 (Final - Based on Comprehensive Investigation)
**Status:** Ready for Implementation
**Approved By:** Codebase Investigation Complete ✅
