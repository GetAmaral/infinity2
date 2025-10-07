# PHASE 6: QUICK REFERENCE CARD

**🚀 Ready to test Turbo!**

---

## 🔑 LOGIN

**URL:** `https://localhost/login`
**Email:** `admin@infinity.ai`
**Password:** `1`

---

## ✅ WHAT TO CHECK

### 1. Smooth Navigation ✨
- ✅ No white flash between pages
- ✅ Blue/purple progress bar at top
- ✅ Console: "🚀 Turbo Drive enabled"
- ✅ Network tab: `fetch` requests (not `document`)

### 2. Forms Work 📝
- ✅ Modals open/close
- ✅ Forms submit successfully
- ✅ Validation displays errors
- ✅ Success messages appear

### 3. No Duplicates ⚠️
- ✅ Only 1 video player (check Elements tab)
- ✅ Tooltips don't duplicate
- ✅ No ghost drag-drop placeholders

### 4. Preferences Persist 💾
- ✅ Theme persists (dark/light)
- ✅ View persists (grid/list/table)
- ✅ Organization context persists

### 5. Complex Features 🎯
- ✅ Video plays correctly
- ✅ Drag-and-drop works
- ✅ TreeFlow canvas renders
- ✅ Enrollment management works
- ✅ Search filters results

---

## 🔍 DEVTOOLS CHECKLIST

### Console Tab
```
🚀 Turbo Drive enabled              ← Should see
🖱️ Turbo: Link clicked              ← Should see
🚀 Turbo: Starting visit             ← Should see
✨ Turbo: Page rendered              ← Should see
❌ ANY RED ERRORS                    ← Should NOT see
```

### Network Tab
```
Type: fetch ✅                       ← Correct
Type: document ❌                    ← Wrong (full reload)
```

### Elements Tab
```
Search: <video>
Result: 1 element ✅                 ← Correct
Result: 2+ elements ❌               ← FAIL (duplicate players)
```

---

## 🧪 QUICK TESTS

### Test 1: Navigation (2 min)
1. Click "Organizations" → Should see progress bar
2. Click any org card → Smooth navigation
3. Click back button → Instant (from cache)
4. Check console → No errors

### Test 2: Video Player (3 min)
1. Go to student lecture with video
2. Play video → Works
3. Navigate away → Console: "Destroying video player"
4. Come back → Only 1 player loads

### Test 3: Forms (5 min)
1. Click "New Organization"
2. Modal opens
3. Submit empty → Errors show in modal
4. Fill correctly → Submit → Success → List updates

### Test 4: Theme (1 min)
1. Toggle theme (dark ↔ light)
2. Navigate to another page
3. Theme persists ✅

### Test 5: Search (2 min)
1. Type in search box
2. Results filter
3. Clear search (X button)
4. All results return

---

## ❌ RED FLAGS (STOP IF YOU SEE)

1. **Console errors** → Stop, report error
2. **White flash** → Turbo not working
3. **Type: document in Network** → Full reload (wrong)
4. **Duplicate video players** → Cleanup not working
5. **Forms don't submit** → CSRF issue
6. **Blank page** → Critical error

---

## 📝 FULL TEST GUIDE

**See:** `PHASE_6_TESTING_GUIDE.md`
- 28 detailed test cases
- 8 hours comprehensive testing
- Expected results for each test

---

## 🚨 ROLLBACK (IF NEEDED)

```bash
# 1. Comment line 4 in assets/app.js:
# import * as Turbo from '@hotwired/turbo';

# 2. Clear cache
docker-compose exec app php bin/console cache:clear

# 3. Hard refresh browser (Ctrl+F5)
```

---

## 📊 TEST RESULTS

**Automated Tests:** ✅ 18/18 PASSED

**Manual Tests:** 🔄 IN PROGRESS

Mark as you go:
- [ ] Navigation (5 tests)
- [ ] Forms & Modals (5 tests)
- [ ] Complex Features (6 tests)
- [ ] Search & Filters (4 tests)
- [ ] View Toggles (2 tests)
- [ ] Organization Switcher (2 tests)
- [ ] Theme & Preferences (3 tests)

**Total:** 0/28 complete

---

## 🎯 SUCCESS = ALL GREEN

✅ No white flash
✅ Progress bar appears
✅ Network shows `fetch`
✅ Console: Turbo logs, no errors
✅ Forms work
✅ Modals work
✅ Video player: no duplicates
✅ Preferences persist
✅ All features work

---

**Happy Testing! 🚀**
