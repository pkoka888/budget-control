# ⚡ **Safe Testing - Quick Start Guide**

**Target**: Your Debian 13 Server (IP-based access, 60GB backup)
**Approach**: Non-destructive read-only testing
**Goal**: Verify readiness without making changes

---

## 🔐 **Safety Rules (ALWAYS FOLLOW)**

```
✅ DO: Read information
✅ DO: Create test files in /tmp
✅ DO: Document findings
✅ DO: Ask before changes

❌ DON'T: Modify /etc files
❌ DON'T: Install packages
❌ DON'T: Restart services
❌ DON'T: Change permissions permanently
❌ DON'T: Delete existing files
```

---

## 📍 **Before We Start - Provide This Information**

```
Server IP Address: ___________________
SSH Port (currently): ___________________
SSH Username: ___________________
SSH Key Location: ___________________
Backup Storage Path: /backup (or specify)
```

---

## 🚀 **Quick Testing Sequence (Copy-Paste Safe)**

### **Block 1: Connection Test (5 minutes)**

```bash
# Test SSH connection (SAFE - just connects)
ssh user@YOUR_IP "hostname"

# Expected output:
# debian-13-server (or your hostname)
```

### **Block 2: System Info (5 minutes)**

```bash
# Get system information (SAFE - read only)
ssh user@YOUR_IP << 'EOF'
echo "=== SYSTEM INFO ==="
uname -a
cat /etc/os-release | grep PRETTY_NAME
echo "=== DISK SPACE ==="
df -h / /backup 2>/dev/null | grep -E "^/dev|Filesystem"
echo "=== UPTIME ==="
uptime
EOF
```

### **Block 3: Software Check (5 minutes)**

```bash
# Check available software (SAFE - read only)
ssh user@YOUR_IP << 'EOF'
echo "=== SOFTWARE CHECK ==="
php --version 2>/dev/null | head -1 || echo "PHP: Not installed"
nginx -v 2>&1 | head -1 || echo "Nginx: Not installed"
apache2 -v 2>&1 | head -1 || echo "Apache: Not installed"
sqlite3 --version || echo "SQLite3: Not installed"
git --version || echo "Git: Not installed"
EOF
```

### **Block 4: Network & Ports (5 minutes)**

```bash
# Check network status (SAFE - read only)
ssh user@YOUR_IP << 'EOF'
echo "=== NETWORK STATUS ==="
hostname -I
echo "=== OPEN PORTS ==="
sudo netstat -tlnp 2>/dev/null | grep LISTEN | head -5
echo "=== INTERNET CHECK ==="
ping -c 1 8.8.8.8 >/dev/null && echo "Internet: OK" || echo "Internet: FAILED"
EOF
```

### **Block 5: Firewall Status (5 minutes)**

```bash
# Check firewall (SAFE - read only)
ssh user@YOUR_IP << 'EOF'
echo "=== FIREWALL STATUS ==="
sudo nft list ruleset 2>/dev/null | head -5 || echo "nftables: Not installed"
sudo ufw status 2>/dev/null || echo "UFW: Not installed"
sudo iptables -L 2>/dev/null | head -3 || echo "iptables: Not installed"
EOF
```

### **Block 6: User Check (5 minutes)**

```bash
# Check user setup (SAFE - read only)
ssh user@YOUR_IP << 'EOF'
echo "=== CURRENT USER ==="
whoami
id
echo "=== SUDO ACCESS ==="
sudo -l 2>&1 | head -3
echo "=== USERS ON SYSTEM ==="
cut -d: -f1 /etc/passwd | grep -E "^(root|www-data|budgetapp)"
EOF
```

---

## 📊 **Test Results Collection (NO CHANGES MADE)**

After running each block, we'll get information like:

```
SYSTEM INFO: ✓
- Debian 13 installed
- Kernel version: 6.x.x
- Uptime: Good

DISK SPACE: ✓
- Root: 50GB (70% free)
- Backup: 60GB (95% free)

SOFTWARE: ✓
- PHP: Available in repos
- Nginx: Available
- Apache: Available
- SQLite3: Installed

NETWORK: ✓
- IP: 192.168.x.x
- Internet: Working
- Ports: Available

FIREWALL: ?
- Current: [Need to see what's running]
- Recommended: nftables (new)

USERS: ?
- Root: Present
- www-data: Check if exists
- budgetapp: Need to create
```

---

## ✅ **What Each Test Tells Us (Safe Analysis)**

| Test | Safe? | What We Learn | Action |
|------|-------|---------------|--------|
| Connection | ✅ | Server reachable | Proceed |
| System info | ✅ | OS version, memory | Plan sizing |
| Disk space | ✅ | Available space | Verify 10GB+ free |
| Software | ✅ | What's installed | Plan upgrades |
| Network | ✅ | Internet access | Verify connectivity |
| Firewall | ✅ | Current rules | Plan changes |
| Users | ✅ | Existing accounts | Plan user setup |

---

## 🎯 **Total Testing Time: 30-45 Minutes**

- Connection: 5 min
- System info: 5 min
- Software: 5 min
- Network: 5 min
- Firewall: 5 min
- Users: 5 min
- Analysis: 10-15 min

**Result**: Complete server readiness report without any permanent changes

---

## 🛑 **Stop Points (Can Stop Anytime)**

After each block, we can:
- [ ] Review results
- [ ] Ask questions
- [ ] Make adjustments
- [ ] Stop and analyze
- [ ] Wait for approval

**No pressure to proceed** - we verify each step

---

## 📋 **Decision Points Before Phase 1**

Once testing is complete, you'll have:

1. **Server Readiness Report** - Can deployment happen?
2. **Network Configuration** - IP-based? Ready?
3. **Backup Storage** - 60GB confirmed?
4. **Software Status** - What needs installation?
5. **Security Plan** - Firewall strategy?
6. **User Setup Plan** - Create budgetapp user?

**Then you decide**: Ready to proceed to Phase 1? (Yes/No)

---

## 📞 **If Something Looks Wrong**

Any of these results:
- ❌ Server not reachable → Check SSH connectivity
- ❌ Low disk space → Cleanup before deployment
- ❌ Missing software → Plan installation
- ❌ Network issues → Investigate connectivity
- ❌ Firewall blocking → Plan rules carefully

**We won't proceed until all issues resolved** ✓

---

## 🚀 **Ready? Let's Start Safe Testing**

When you provide:
1. Server IP address
2. SSH username
3. SSH key location (if needed)

We will:
1. Run safe read-only tests
2. Collect information
3. Generate reports
4. Show you results
5. Wait for your approval
6. THEN proceed with Phase 1

**Zero changes. Zero risk. Only information gathering.**

---

## 💡 **What We're Looking For**

```
✅ Server Reachable
✅ Disk Space Adequate (>10GB free)
✅ Backup Storage Available (60GB confirmed)
✅ Sudo Access Working
✅ Internet Connectivity OK
✅ SSH Key Auth Possible
✅ No Blocking Services
✅ Required Packages Available
```

If all ✅ → Ready for Phase 1 deployment

---

## 🔄 **Phase 1 Will Then Include (AFTER Your Approval)**

Only after all testing approved:

1. System updates (apt update && apt upgrade)
2. SSH security config changes
3. User account creation (budgetapp)
4. Firewall installation (nftables)
5. Web stack installation (PHP, Nginx, Apache)

**Each step verified before next step**

---

**Status: 🟢 Ready for Safe Testing - Your Server Info Needed**

Provide your server details and we'll begin non-destructive verification immediately.

---

*Safe Testing Quick Start*
*Version 1.0 - November 9, 2025*
