# Organization Switcher - Testing Guide

## ✅ Fix Applied

The organization switcher was always showing "All Organizations" because the `SubdomainOrganizationSubscriber` was clearing the organization context on every request to the root domain (`localhost`), even when an admin had manually selected an organization via the switcher.

**Solution:** Modified the subscriber to preserve manually selected organizations when on root domain.

---

## 🧪 Testing Steps

### Test 1: Fresh Login (No Organization Selected)

1. **Clear your browser cookies** (important!)
2. Go to: https://localhost/login
3. Login: `admin@infinity.local` / `admin123`
4. **Expected Result:**
   - ✅ Navbar shows organization dropdown with building icon
   - ✅ Dropdown text shows: **"All Organizations"**
   - ✅ This is correct because no organization is selected yet

---

### Test 2: Select Organization via Switcher

1. Still on https://localhost
2. Click the **organization dropdown** (building icon, left of user profile)
3. You should see:
   - "All Organizations (Root)" option
   - List of 5 organizations (Acme, Globex, Wayne, Stark, Umbrella)
4. Click **"Acme Corporation"**
5. **Expected Result:**
   - ✅ Page refreshes
   - ✅ Flash message: "Switched to organization: Acme Corporation"
   - ✅ Navbar dropdown text NOW shows: **"Acme Corporation"** (not "All Organizations"!)
6. Navigate to: https://localhost/user
7. **Expected Result:**
   - ✅ You only see users from Acme Corporation
   - ✅ Data is filtered!

---

### Test 3: Switch to Different Organization

1. Still logged in as admin
2. Click organization dropdown again
3. Now it should show "Acme Corporation" as current
4. Click **"Globex Corporation"**
5. **Expected Result:**
   - ✅ Navbar updates to: **"Globex Corporation"**
   - ✅ Flash message: "Switched to organization: Globex Corporation"
6. Navigate to: https://localhost/user
7. **Expected Result:**
   - ✅ You only see users from Globex (different users than Acme!)

---

### Test 4: Clear Organization (Back to All)

1. Click organization dropdown
2. Click **"All Organizations (Root)"**
3. **Expected Result:**
   - ✅ Navbar reverts to: **"All Organizations"**
   - ✅ Flash message: "Organization context cleared. You are now accessing as admin."
4. Navigate to: https://localhost/user
5. **Expected Result:**
   - ✅ You see ALL users from ALL organizations
   - ✅ No filtering!

---

### Test 5: Organization Persists Across Pages

1. Select "Stark Industries" via switcher
2. **Expected:** Navbar shows "Stark Industries"
3. Navigate to: https://localhost/
4. **Expected:** Navbar STILL shows "Stark Industries" (persisted!)
5. Navigate to: https://localhost/user
6. **Expected:** Only see Stark Industries users
7. Navigate to: https://localhost/organization
8. **Expected:** Navbar STILL shows "Stark Industries"

---

### Test 6: Subdomain Access (Regular User)

1. **Logout** from admin
2. Go to: https://acme-corporation.localhost/login
3. Login: `wile.coyote@acme.corp` / `password123`
4. **Expected Result:**
   - ✅ Login succeeds
   - ✅ NO organization switcher visible (regular users can't switch)
   - ✅ User dropdown shows "Acme Corporation" below user info
5. Navigate to: https://acme-corporation.localhost/user
6. **Expected Result:**
   - ✅ Only see Acme users
   - ✅ Data isolation working!

---

## 🔧 Troubleshooting

### Issue: Still Shows "All Organizations" After Switching

**Solution:**
```bash
# 1. Clear browser cookies completely
# 2. Clear Symfony cache
docker-compose exec app php bin/console cache:clear

# 3. Restart app container
docker-compose restart app

# 4. Try again with fresh login
```

### Issue: Organization Not Persisting

**Check session storage:**
```bash
# Check if session is being stored
docker-compose exec app php bin/console debug:container --parameter=session.save_path

# Check Redis if using Redis sessions
docker-compose exec redis redis-cli keys "*session*"
```

### Issue: Can't See Organization Dropdown

**Verify admin role:**
```bash
docker-compose exec app php bin/console doctrine:query:sql "
SELECT u.email, string_agg(r.name, ', ') as roles
FROM \"user\" u
JOIN user_roles ur ON ur.user_id = u.id
JOIN role r ON r.id = ur.role_id
WHERE u.email = 'admin@infinity.local'
GROUP BY u.email"
```

Should show: `admin` role

---

## 📊 Expected Behavior Summary

| Scenario | URL | User | Navbar Display | Data Filtering |
|----------|-----|------|----------------|----------------|
| Fresh login | localhost | admin | "All Organizations" | None (see all) |
| After switching to Acme | localhost | admin | "Acme Corporation" | Only Acme data |
| After switching to Globex | localhost | admin | "Globex Corporation" | Only Globex data |
| After clearing org | localhost | admin | "All Organizations" | None (see all) |
| Subdomain access | acme-corporation.localhost | regular user | Shows in user dropdown | Only Acme data |

---

## ✅ Success Criteria

- [ ] Navbar shows "All Organizations" on fresh admin login
- [ ] Navbar updates to organization name after switching
- [ ] Organization name persists across page navigation
- [ ] Data is filtered correctly based on selected organization
- [ ] Can switch between organizations and see different data
- [ ] Can clear organization to see all data
- [ ] Regular users don't see organization switcher
- [ ] Subdomain access works for regular users

---

## 🎯 Test It Now!

1. **Clear browser cookies**
2. **Login as admin**: https://localhost/login
3. **Use organization switcher**
4. **Watch the navbar update!**

The organization name should now display correctly! 🎉