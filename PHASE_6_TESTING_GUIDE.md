# PHASE 6: COMPREHENSIVE TURBO TESTING GUIDE

**Status:** In Progress
**Date:** 2025-10-06
**Duration:** 8 hours (full day)
**Prerequisites:** Turbo enabled ✅, Docker containers running ✅, App accessible ✅

---

## 🎯 TESTING OBJECTIVES

Verify that Turbo Drive works correctly with all application features:
- Smooth navigation without white flash
- Forms and modals work correctly
- Complex features (video player, drag-drop, canvas) work
- Search and filters function properly
- Preferences persist across Turbo navigation
- No memory leaks or duplicate elements

---

## 🔧 TESTING SETUP

### Browser DevTools Setup (CRITICAL)

Before starting, open your browser DevTools:

1. **Chrome/Firefox:** Press `F12` or `Ctrl+Shift+I`
2. **Open Console tab** - Watch for Turbo logs and errors
3. **Open Network tab** - Monitor request types
4. **Open Performance tab** - Check for memory leaks

### Expected Console Logs (Dev Mode)

When Turbo is working, you should see:
```
🚀 Turbo Drive enabled
🖱️ Turbo: Link clicked [url]
🚀 Turbo: Starting visit to [url]
📡 Turbo: Fetching [url]
🎨 Turbo: About to render
✨ Turbo: Page rendered
```

### Network Tab Verification

**With Turbo (correct):**
- Type: `fetch` or `xhr`
- Size: smaller (HTML only)
- Time: faster

**Without Turbo (problem):**
- Type: `document`
- Size: larger (full page + assets)
- Time: slower

---

## 📋 TESTING CHECKLIST

---

## 6.1 NAVIGATION TESTING (1 hour)

### Objective
Verify smooth Turbo navigation across all main routes.

### Test Cases

#### TC 6.1.1: Home → Organizations → Detail → Back

**Steps:**
1. Navigate to `https://localhost/`
2. Click "Organizations" in navbar
3. Observe navigation behavior
4. Click any organization card
5. Observe detail page load
6. Click browser back button
7. Observe navigation back

**Expected Results:**
- [ ] No white flash during navigation
- [ ] Blue/purple progress bar visible at top
- [ ] Network tab shows `fetch` request (not `document`)
- [ ] Console shows Turbo events
- [ ] Scroll position preserved on back navigation
- [ ] Page content updates correctly

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.1.2: Home → Courses → Detail → Back

**Steps:**
1. From home, click "Courses"
2. Click any course card
3. Observe course detail page
4. Click back button
5. Verify smooth navigation

**Expected Results:**
- [ ] Same as TC 6.1.1
- [ ] Course modules load correctly
- [ ] Lectures display properly

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.1.3: Home → Users → Detail → Back

**Steps:**
1. From home, click "Users"
2. Click any user card/row
3. Observe user detail page
4. Click back button

**Expected Results:**
- [ ] Same navigation behavior
- [ ] User details load correctly

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.1.4: Home → TreeFlow → Detail → Back

**Steps:**
1. From home, click "TreeFlow"
2. Click any treeflow card
3. Observe canvas page
4. Click back button

**Expected Results:**
- [ ] Canvas loads correctly
- [ ] Smooth Turbo navigation
- [ ] No duplicate canvas instances

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.1.5: Navbar Navigation

**Steps:**
1. Click each navbar link in sequence
2. Observe active page highlighting
3. Check dropdowns (if any)

**Expected Results:**
- [ ] All links work with Turbo
- [ ] Active page highlighted correctly
- [ ] Dropdowns function properly

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.2 FORMS & MODALS TESTING (2 hours)

### Objective
Verify CRUD operations work correctly with Turbo navigation.

### Test Cases

#### TC 6.2.1: Create Organization (Modal)

**Steps:**
1. Navigate to Organizations page
2. Click "New Organization" button
3. Observe modal appearance
4. **Test A: Invalid data**
   - Leave required fields empty
   - Click Submit
5. **Test B: Valid data**
   - Fill all required fields
   - Click Submit

**Expected Results:**
- [ ] Modal opens smoothly
- [ ] Focus on first input field
- [ ] Invalid submission shows errors in modal
- [ ] Modal stays open after validation error
- [ ] Valid submission succeeds
- [ ] Success message displays
- [ ] Modal closes automatically
- [ ] List updates with new organization (Turbo navigation)
- [ ] Console shows `turbo:submit-start` and `turbo:submit-end`

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.2.2: Edit Organization (Modal)

**Steps:**
1. On Organizations page, click "Edit" on any org
2. Modal opens with existing data
3. Modify name or description
4. Click Save

**Expected Results:**
- [ ] Modal pre-populated with existing data
- [ ] Save updates organization
- [ ] Changes reflected immediately
- [ ] Smooth Turbo navigation

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.2.3: Delete Organization

**Steps:**
1. Click "Delete" on any organization
2. Confirm deletion
3. Observe result

**Expected Results:**
- [ ] Confirmation dialog appears
- [ ] Delete succeeds
- [ ] Item removed from list
- [ ] Success message shows
- [ ] Page updates via Turbo

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.2.4: Course CRUD Operations

**Test same operations for Courses:**
- [ ] Create course (modal)
- [ ] Edit course (modal)
- [ ] Delete course
- [ ] Create module
- [ ] Create lecture
- [ ] Delete module
- [ ] Delete lecture

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.2.5: User CRUD Operations

**Test same operations for Users:**
- [ ] Create user (modal)
- [ ] Edit user (modal)
- [ ] Delete user

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.3 COMPLEX FEATURES TESTING (2 hours)

### Objective
Test advanced features that require careful Turbo integration.

### Test Cases

#### TC 6.3.1: Course Enrollment

**Steps:**
1. Navigate to course detail page
2. Click "Manage Enrollments"
3. Modal opens with student list
4. Test TomSelect multi-select:
   - Search for students
   - Select multiple students
   - Deselect students
5. Toggle enrollment switches
6. Click "Confirm"

**Expected Results:**
- [ ] Modal opens smoothly
- [ ] TomSelect initializes correctly
- [ ] Search works
- [ ] Multi-select functions
- [ ] Switches toggle correctly
- [ ] Count updates in real-time
- [ ] Confirm button works
- [ ] Success message displays
- [ ] Page updates via Turbo
- [ ] Enrollment list shows changes

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.3.2: Enrollment Switch View

**Steps:**
1. Navigate to enrollment switch page
2. Search for student
3. Toggle multiple switches
4. Click "Confirm"

**Expected Results:**
- [ ] Search filters results (debounced)
- [ ] Switches toggle smoothly
- [ ] Count updates
- [ ] Confirm triggers Turbo navigation
- [ ] Success notification appears
- [ ] Changes persist

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.3.3: Video Player (CRITICAL TEST)

**Steps:**
1. Navigate to student lecture page with video
2. Wait for player to load
3. Play video for 5 seconds
4. Observe player behavior
5. **Navigate away** to another page
6. **Navigate back** to same lecture page
7. Observe player initialization

**Expected Results:**
- [ ] Player loads on first visit
- [ ] Plyr interface appears
- [ ] Video plays correctly
- [ ] Progress bar works
- [ ] On navigation away: console shows `[Cleanup] Destroying video player`
- [ ] On navigation back: player reinitializes
- [ ] **NO DUPLICATE PLAYERS** (critical!)
- [ ] Only one video element exists
- [ ] Console shows proper initialization logs

**Critical Check:**
Open browser DevTools → Elements tab → Search for `<video>` tag.
- Should see: **1 video element**
- If you see 2+ video elements = FAIL

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.3.4: Mark Lecture Complete

**Steps:**
1. On lecture page, check "Mark as complete" checkbox
2. Observe progress update
3. Navigate away and back
4. Verify completion status persists

**Expected Results:**
- [ ] Checkbox toggles
- [ ] Progress updates
- [ ] Status persists

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.3.5: Drag-and-Drop Lecture Reorder

**Steps:**
1. Navigate to course with multiple lectures
2. Drag a lecture to new position
3. Observe placeholder
4. Drop lecture
5. Observe save
6. Refresh page (F5)
7. Verify order persists

**Expected Results:**
- [ ] Placeholder appears during drag
- [ ] Drop works
- [ ] Order saves automatically
- [ ] Success message shows
- [ ] Navigate away and back: order persists
- [ ] No ghost placeholders after navigation

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.3.6: TreeFlow Canvas

**Steps:**
1. Navigate to TreeFlow detail page
2. Canvas should render
3. Create new step node
4. Drag node to new position
5. Delete step from canvas
6. Observe confirmation and refresh
7. Navigate away to another TreeFlow
8. Navigate back
9. Verify canvas reinitializes correctly

**Expected Results:**
- [ ] Canvas renders on first load
- [ ] Can create nodes
- [ ] Can drag nodes
- [ ] Delete triggers confirmation
- [ ] Canvas refreshes via Turbo
- [ ] Navigate away/back: no duplicate listeners
- [ ] Console shows proper cleanup logs

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.4 SEARCH & FILTERS TESTING (1 hour)

### Objective
Verify live search works with Turbo.

### Test Cases

#### TC 6.4.1: Organizations Search

**Steps:**
1. Navigate to Organizations page
2. Click search input
3. Type search term (e.g., "acme")
4. Observe results filtering
5. Clear search (X button)
6. Observe results restore
7. Type again and press ESC key
8. Observe search clear

**Expected Results:**
- [ ] Search filters in real-time
- [ ] Debouncing works (not searching every keystroke)
- [ ] Clear button works
- [ ] ESC key clears search
- [ ] All results return after clear
- [ ] No console errors

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.4.2: Courses Search

**Repeat same tests for Courses:**
- [ ] Search works
- [ ] Clear works
- [ ] ESC works

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.4.3: Users Search

**Repeat same tests for Users:**
- [ ] Search works
- [ ] Clear works
- [ ] ESC works

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.4.4: Click Search Result

**Steps:**
1. Search for item
2. Click on result
3. Observe navigation

**Expected Results:**
- [ ] Navigate to detail page
- [ ] Smooth Turbo navigation
- [ ] Console shows `turbo:visit`

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.5 VIEW TOGGLES TESTING (1 hour)

### Objective
Verify view preferences persist across Turbo navigation.

### Test Cases

#### TC 6.5.1: Grid/List/Table View Toggle

**Steps:**
1. Navigate to Organizations page
2. Click "Grid View" button
3. Observe layout change
4. Navigate to Courses page
5. Navigate back to Organizations
6. Observe view preference

**Expected Results:**
- [ ] Grid view displays correctly
- [ ] Items arrange in grid
- [ ] Navigate away and back: **preference persists**

**Steps (continued):**
7. Click "List View"
8. Observe layout change
9. Navigate away and back
10. Verify preference persists

**Expected Results:**
- [ ] List view displays correctly
- [ ] Items arrange in list
- [ ] Preference persists

**Steps (continued):**
11. Click "Table View"
12. Observe layout change
13. Navigate away and back
14. Verify preference persists

**Expected Results:**
- [ ] Table view displays correctly
- [ ] Columns show properly
- [ ] Preference persists

**Console Check:**
Look for: `✅ View preference already set: [grid/list/table]`

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.5.2: Items Per Page

**Steps:**
1. Change items per page dropdown
2. Observe list update
3. Navigate away and back
4. Verify preference persists

**Expected Results:**
- [ ] List updates with correct item count
- [ ] Preference persists

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.6 ORGANIZATION SWITCHER TESTING (30 min)

### Objective
Verify admin organization switcher works with Turbo.

**Prerequisites:** Must be logged in as admin user.

### Test Cases

#### TC 6.6.1: Switch Organization

**Steps:**
1. Login as admin
2. Click organization dropdown in navbar
3. Observe dropdown menu
4. Click different organization
5. Observe page update

**Expected Results:**
- [ ] Dropdown opens
- [ ] Shows all organizations
- [ ] Current org highlighted
- [ ] Click org: dropdown closes
- [ ] **Smooth Turbo navigation** (no full page reload)
- [ ] Page updates with new org context
- [ ] Console shows `turbo:submit-start`
- [ ] Network tab shows `fetch` request

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.6.2: Clear Organization (All Organizations)

**Steps:**
1. Click "All Organizations" in dropdown
2. Observe result

**Expected Results:**
- [ ] Clears organization context
- [ ] Smooth Turbo navigation
- [ ] Can see all data (not filtered)

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 6.7 THEME & PREFERENCES TESTING (30 min)

### Objective
Verify theme and Bootstrap components work with Turbo.

### Test Cases

#### TC 6.7.1: Theme Toggle

**Steps:**
1. Note current theme (dark/light)
2. Click theme toggle button
3. Observe theme change
4. Navigate to different page (Turbo)
5. Observe theme persists
6. Refresh page (F5)
7. Verify theme still correct

**Expected Results:**
- [ ] Theme switches immediately
- [ ] All elements update colors
- [ ] Console shows PreferenceManager save
- [ ] Turbo navigation: theme persists
- [ ] Full refresh: theme still correct
- [ ] localStorage contains theme preference

**Console Check:**
```
💾 PreferenceManager: Saving preference theme = [dark/light]
```

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.7.2: Tooltips

**Steps:**
1. Hover over element with tooltip (e.g., icon)
2. Observe tooltip appears
3. Navigate to another page (Turbo)
4. Hover over tooltip element
5. Verify tooltip still works

**Expected Results:**
- [ ] Tooltip appears on first page
- [ ] After Turbo navigation: tooltip still works
- [ ] No duplicate tooltips
- [ ] Console shows tooltip initialization

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

#### TC 6.7.3: Dropdowns

**Steps:**
1. Click navbar dropdown
2. Observe dropdown opens
3. Close dropdown
4. Navigate to another page (Turbo)
5. Click dropdown again

**Expected Results:**
- [ ] Dropdown opens
- [ ] Dropdown closes
- [ ] After Turbo navigation: dropdown still works
- [ ] No duplicate dropdowns
- [ ] No visual glitches

**Actual Results:**
```
Date tested: ___________
Result: PASS / FAIL
Notes:


```

---

## 🔍 DEBUGGING CHECKLIST

If any test fails, check these:

### Console Errors
- [ ] Open Console tab
- [ ] Filter by "Errors" (red icon)
- [ ] Document any errors
- [ ] Take screenshot

### Network Issues
- [ ] Open Network tab
- [ ] Check if requests are `fetch` type (not `document`)
- [ ] Check response status codes
- [ ] Look for failed requests (red)

### Turbo Events
In dev mode, verify these events appear:
- [ ] `turbo:click`
- [ ] `turbo:before-visit`
- [ ] `turbo:visit`
- [ ] `turbo:before-render`
- [ ] `turbo:render`
- [ ] `turbo:load`

### Common Issues

**Issue: White flash during navigation**
- Cause: Turbo not active
- Check: Look for "🚀 Turbo Drive enabled" in console

**Issue: Forms not submitting**
- Cause: CSRF token issue
- Check: View form HTML, verify token present

**Issue: Duplicate video players**
- Cause: Player not destroyed before navigation
- Check: Console for cleanup logs
- Check: Elements tab for multiple `<video>` tags

**Issue: Tooltips not working after navigation**
- Cause: Not reinitialized on `turbo:load`
- Check: Console for tooltip initialization logs

**Issue: Full page reload instead of Turbo navigation**
- Cause: Link has `data-turbo="false"` or page excluded
- Check: Element HTML attributes
- Check: Page meta tags

---

## 📊 TEST RESULTS SUMMARY

### Overview

**Total Test Cases:** 28
**Passed:** ___
**Failed:** ___
**Skipped:** ___

**Overall Status:** PASS / FAIL / IN PROGRESS

### Failed Tests

List any failed tests here with details:

1. **TC Number:** _____
   - **Issue:**
   - **Console Errors:**
   - **Screenshots:**
   - **Severity:** Critical / High / Medium / Low

2. **TC Number:** _____
   - **Issue:**
   - **Console Errors:**
   - **Screenshots:**
   - **Severity:**

### Performance Notes

**Navigation Speed:**
- Average time for Turbo navigation: _____ms
- Average time for full page load: _____ms
- Improvement: _____%

**Memory Usage:**
- Initial load: _____MB
- After 10 navigations: _____MB
- Memory leak detected: YES / NO

### Browser Compatibility

- [ ] Chrome: PASS / FAIL
- [ ] Firefox: PASS / FAIL
- [ ] Safari: PASS / FAIL
- [ ] Mobile Chrome: PASS / FAIL
- [ ] Mobile Safari: PASS / FAIL

---

## ✅ COMPLETION CRITERIA

Phase 6 is complete when:

- [ ] All 28 test cases executed
- [ ] All critical tests pass
- [ ] No console errors during normal operation
- [ ] No memory leaks detected
- [ ] No duplicate elements (tooltips, players, etc.)
- [ ] All forms and modals work correctly
- [ ] Navigation is smooth with progress bar
- [ ] Preferences persist across navigation
- [ ] Results documented in this guide

---

## 🚨 ROLLBACK PROCEDURE

If critical issues found:

1. **Disable Turbo:**
   ```javascript
   // In assets/app.js, comment line 4:
   // import * as Turbo from '@hotwired/turbo';
   ```

2. **Clear cache:**
   ```bash
   docker-compose exec app php bin/console cache:clear
   ```

3. **Hard refresh browser:**
   - Ctrl+Shift+Delete (clear cache)
   - Ctrl+F5 (hard refresh)

4. **Verify:** Site works without Turbo

---

## 📝 NOTES

Add any additional observations, issues, or recommendations here:

```





```

---

**Testing Started:** ___________
**Testing Completed:** ___________
**Tester:** ___________
**Status:** ✅ COMPLETE / ❌ FAILED / 🔄 IN PROGRESS
