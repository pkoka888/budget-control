# Budget Control - Login Guide

## ✅ Test User Created!

A test user has been successfully created in the system.

### Login Credentials

```
📧 Email:    test@example.com
🔐 Password: password123
```

---

## How to Login

### Option 1: Direct Login (Recommended)

1. Open: http://localhost:8080/login
2. Enter email: `test@example.com`
3. Enter password: `password123`
4. Click "Přihlásit se" (Sign In)

### Option 2: From Home Page

1. Open: http://localhost:8080
2. You'll be redirected to http://localhost:8080/login
3. Use credentials above

---

## What You'll See After Login

After successful login, you'll see:

✅ **Dashboard** - Overview of your finances
✅ **Sidebar Menu** with options for:
  - 📊 Dashboard
  - 🏦 Accounts (Účty)
  - 💰 Transactions (Transakce)
  - 🏷️ Categories (Kategorie)
  - 📈 Budgets (Rozpočty)
  - 💹 Investments (Investice)
  - 📥 CSV Import
  - 🎯 Goals (Cíle)
  - 📋 Reports (Zprávy)
  - 💡 Tips & Guides (Tipy)
  - ⚙️ Settings (Nastavení)
  - 🚪 Logout (Odhlásit se)

---

## Features You Can Try

### 1. **Add an Account**
- Go to "Accounts" → "Create Account"
- Add a checking, savings, or investment account
- Set initial balance

### 2. **Create a Budget**
- Go to "Budgets"
- Set monthly budget for categories
- Track spending against budget

### 3. **Add Transactions**
- Go to "Transactions" → "Add Transaction"
- Record income or expenses
- Categorize automatically

### 4. **View Reports**
- Go to "Reports"
- Choose Monthly, Yearly, Net Worth, or Analytics
- See visual breakdowns

### 5. **Manage Goals**
- Go to "Goals"
- Set financial goals
- Track progress toward targets

---

## If Login Fails

### Issue: "Neplatné přihlašovací údaje" (Invalid Credentials)

**Solution 1: Double-check credentials**
```
- Email: test@example.com (exactly this)
- Password: password123 (exactly this)
- No spaces before/after
```

**Solution 2: Create new account**
1. Go to http://localhost:8080/register
2. Enter name, email, and password
3. Click "Registrovat se"
4. This creates a new account and logs you in

**Solution 3: Clear browser cache**
- Clear cookies for localhost:8080
- Close browser tabs
- Try again

---

## Session & Logout

### Session Timeout
- Sessions expire after **3600 seconds (1 hour)** of inactivity
- You'll be logged out automatically
- Need to login again to continue

### Manual Logout
1. Click the user avatar or "Odhlásit se" in sidebar
2. You'll be redirected to login page
3. To login again, use credentials above

---

## Database Info

**User Account Details:**
- Name: Test User
- Email: test@example.com
- Password: password123 (hashed with bcrypt)
- Currency: CZK (Czech Koruna)
- Timezone: Europe/Prague
- Created: Today

---

## Account Features Available

✅ **Fully Functional:**
- Login/Register/Logout
- Session management
- All dashboard features
- Account management
- Transaction tracking
- Budget setup
- Goal setting
- Report generation
- CSV import/export
- Investments tracking
- API endpoints (38+)

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't access login page | Verify Docker is running: `docker ps` |
| Login fails with valid credentials | Clear cookies and try again |
| Session expires | Login again with same credentials |
| Can't see sidebar | Make sure you're logged in (check URL) |
| Features not working | Check browser console for errors |

---

## Next Steps

1. **Login** with credentials above
2. **Explore** the dashboard and menus
3. **Create** some test data (accounts, transactions)
4. **Try** different features
5. **Report** any issues

---

**Happy Budgeting! 💰**

For more help, check:
- QUICK_START_GUIDE.md - App startup
- FINAL_STATUS_REPORT.md - Technical details
- TEST_SUMMARY.md - Test results
