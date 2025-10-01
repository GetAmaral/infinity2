# 🔐 Infinity - Login Credentials

## 👨‍💼 Admin Account

**Access:** Root domain ONLY (no organization subdomain)

- **URL:** https://localhost/login
- **Email:** `admin@infinity.local`
- **Password:** `admin123`
- **Role:** ROLE_ADMIN
- **Organization:** None (can access all)
- **Features:**
  - Can login to root domain
  - Can login to ANY organization subdomain
  - Can switch organizations via navbar dropdown
  - Can see all data when no org selected

---

## 👥 Organization Users

### 🚀 Acme Corporation
**Subdomain:** https://acme-corporation.localhost

| Email | Password | Role | Name |
|-------|----------|------|------|
| `wile.coyote@acme.corp` | `password123` | Manager | Wile E. Coyote |
| `marvin.martian@acme.corp` | `password123` | User | Marvin the Martian |
| `porky.pig@acme.corp` | `password123` | User | Porky Pig |

### 🌐 Globex Corporation
**Subdomain:** https://globex-corporation.localhost

| Email | Password | Role | Name |
|-------|----------|------|------|
| `hank.scorpio@globex.com` | `password123` | Manager | Hank Scorpio |
| `homer.simpson@globex.com` | `password123` | User | Homer Simpson |
| `mindy.simmons@globex.com` | `password123` | User | Mindy Simmons |

### 🦇 Wayne Enterprises
**Subdomain:** https://wayne-enterprises.localhost

| Email | Password | Role | Name |
|-------|----------|------|------|
| `bruce.wayne@wayneenterprises.com` | `password123` | Manager | Bruce Wayne |
| `lucius.fox@wayneenterprises.com` | `password123` | User | Lucius Fox |

### 🦾 Stark Industries
**Subdomain:** https://stark-industries.localhost

| Email | Password | Role | Name |
|-------|----------|------|------|
| `tony.stark@starkindustries.com` | `password123` | Manager | Tony Stark |
| `pepper.potts@starkindustries.com` | `password123` | User | Pepper Potts |
| `james.rhodes@starkindustries.com` | `password123` | User | James Rhodes |
| `happy.hogan@starkindustries.com` | `password123` | User | Happy Hogan |

### ☂️ Umbrella Corporation
**Subdomain:** https://umbrella-corporation.localhost

| Email | Password | Role | Name |
|-------|----------|------|------|
| `albert.wesker@umbrella.corp` | `password123` | Manager | Albert Wesker |
| `william.birkin@umbrella.corp` | `password123` | User | William Birkin |

---

## 🔒 Security Rules

### Regular Users (ROLE_USER)
- ✅ **CAN** login to their organization subdomain
- ❌ **CANNOT** login to other organization subdomains
- ❌ **CANNOT** login to root domain (localhost)
- ✅ **CAN** only see their organization's data
- ❌ **CANNOT** switch organizations

**Example:**
```bash
# ✅ This works:
URL: https://acme-corporation.localhost/login
User: wile.coyote@acme.corp
Result: Success! Sees only Acme data

# ❌ This fails:
URL: https://globex-corporation.localhost/login
User: wile.coyote@acme.corp (wrong org!)
Result: Error - "You do not have access to this organization"

# ❌ This fails:
URL: https://localhost/login
User: wile.coyote@acme.corp (no org!)
Result: Error - "You must access through your organization subdomain"
```

### Admin Users (ROLE_ADMIN, ROLE_SUPER_ADMIN)
- ✅ **CAN** login to root domain (localhost)
- ✅ **CAN** login to ANY organization subdomain
- ✅ **CAN** switch organizations via dropdown
- ✅ **CAN** see all data when no org selected
- ✅ **CAN** see filtered data when org selected

**Example:**
```bash
# ✅ Root domain access:
URL: https://localhost/login
User: admin@infinity.local
Result: Success! Sees all organizations

# ✅ Any subdomain access:
URL: https://acme-corporation.localhost/login
User: admin@infinity.local
Result: Success! Context set to Acme

# ✅ Organization switcher:
- Login as admin
- Click organization dropdown in navbar
- Select different organization or "All Organizations"
- Data filters automatically
```

---

## 🧪 Testing Tenant Isolation

### Test 1: Regular User - Correct Subdomain ✅
```bash
1. Go to: https://acme-corporation.localhost/login
2. Login: wile.coyote@acme.corp / password123
3. Expected: ✅ Success
4. Navigate to /user
5. Expected: Only see Acme users
```

### Test 2: Regular User - Wrong Subdomain ❌
```bash
1. Go to: https://globex-corporation.localhost/login
2. Login: wile.coyote@acme.corp / password123
3. Expected: ❌ Error - access denied
```

### Test 3: Admin - Root Domain ✅
```bash
1. Go to: https://localhost/login
2. Login: admin@infinity.local / admin123
3. Expected: ✅ Success
4. Navigate to /user
5. Expected: See ALL users from ALL organizations
```

### Test 4: Admin - Organization Switcher ✅
```bash
1. Login as admin (any domain)
2. Look for organization dropdown (left of user profile)
3. Click dropdown
4. Select "Acme Corporation"
5. Navigate to /user
6. Expected: Now only see Acme users
7. Switch to "All Organizations (Root)"
8. Navigate to /user
9. Expected: See all users again
```

---

## 🔧 Password Reset (If Needed)

If you need to reset any password:

```bash
# Generate new hash
docker-compose exec app php bin/console security:hash-password YOUR_PASSWORD

# Update in database
docker-compose exec app php bin/console doctrine:query:sql "UPDATE \"user\" SET password = 'HASH_FROM_ABOVE' WHERE email = 'user@example.com'"
```

---

## 🎯 Quick Start

**For Admin Testing:**
1. Open: https://localhost/login
2. Login: `admin@infinity.local` / `admin123`
3. Explore: Use organization switcher to navigate between organizations

**For User Testing:**
1. Open: https://acme-corporation.localhost/login
2. Login: `wile.coyote@acme.corp` / `password123`
3. Verify: Only see Acme Corporation data

**For Isolation Testing:**
1. Login to Acme as wile.coyote@acme.corp
2. Note the users visible
3. Logout
4. Login to Globex as hank.scorpio@globex.com
5. Note completely different set of users (isolated!)

---

All passwords have been verified and are ready for testing! 🎉