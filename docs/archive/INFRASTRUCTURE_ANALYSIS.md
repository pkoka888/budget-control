# Infrastructure Analysis & Deployment Strategy
**Budget Control Application Deployment**
**Date**: November 9, 2025
**Server**: 89.203.173.196:2222

---

## Current Server Infrastructure

### Active Services & Resources

#### 1. **Firewall (UFW/iptables)** ✅ ACTIVE
- Status: Active with DROP policy (deny all by default)
- Open Ports:
  - **2222/TCP** - SSH (24,618 packets, heavy traffic)
  - **8080/TCP** - HTTP alternative (Apache2)
  - **8443/TCP** - HTTPS alternative (Apache2)
  - **3306/TCP** - MariaDB (localhost access)
  - **5000/TCP** - Python app server
  - **5452/TCP** - Custom port
  - **2233/TCP** - Limited to 192.168.1.61 (Proxmox/VNC)
  - **6333, 6334, 11434** - Docker services (Ollama, Qdrant)

#### 2. **Apache2 Web Server** ✅ RUNNING
- Status: Active (running 3+ days)
- Memory: 20.2MB (6 worker processes)
- Listening on: Port 80, 8080, 8443
- Uptime: Stable with daily reloads
- Last reload: Nov 9 00:00:01 (automated)

#### 3. **Nginx Web Server** ❌ FAILED
- Status: Failed to start
- Last failure: Nov 6 01:24:06
- Error: Port binding failure
  - Port 8080: "Address already in use" (Apache2 conflict)
  - Port 80: "Address already in use" (Apache2 conflict)
- Configured on: 127.0.0.1:8081 (localhost only, not serving external traffic)

#### 4. **MariaDB Database** ✅ RUNNING
- Status: Active for 3 weeks 4 days
- Version: 10.11.14-MariaDB
- Memory: 13.3GB (heavy use)
- Port: 127.0.0.1:3306 (localhost only)
- **⚠️ Security Issue**: Multiple authentication failures detected
  - Failed attempts: backups@localhost, root@localhost, test_user, dashboard, deployment_user
  - Action required: Reset user credentials, remove test accounts

#### 5. **Docker Services** ✅ RUNNING
- Ollama (port 11434): AI/LLM service
- Qdrant (ports 6333, 6334): Vector database
- Isolated networks (br-27f6877828b4, docker0)
- No conflicts with Budget Control deployment

#### 6. **Network Configuration** ✅
- Primary IP: 192.168.1.60 (internal)
- External IP: 89.203.173.196 (public)
- DNS: Working (8.8.8.8:53 reachable)
- HTTPS: Working (cloudflare 1.1.1.1:443 reachable)
- HTTP: Blocked by Cisco firewall upstream (port 80 blocked at network level)
- ICMP: Working (ping 4.6-5.4ms latency)
- SSH Tunnels: 3 active backup sessions to external IPs

---

## Port Conflict Analysis

### The Problem: Apache2 ↔ Nginx Conflict

**Current State:**
- Apache2 occupies ports 80, 8080, 8443
- Nginx tried to start but failed (ports already in use)
- Both are configured for same ports
- Nginx is now disabled

**Impact on Budget Control:**
Your deployment plan requires:
- **Port 8080** → HTTP (external)
- **Port 8443** → HTTPS (external)
- **Internal ports** → PHP-FPM (7070, 9000, etc.)

Apache2 is currently blocking both ports.

### Solutions

#### **Option A: Remove Apache2, Use Nginx Only** (Recommended)
**Advantages:**
- Nginx is lighter weight (less memory, fewer processes)
- Better reverse proxy performance
- Cleaner configuration
- Supports modern HTTP/2

**Steps:**
1. Stop Apache2: `sudo systemctl stop apache2`
2. Disable Apache2: `sudo systemctl disable apache2`
3. Remove Apache2: `sudo apt-get remove apache2 -y`
4. Enable Nginx: `sudo systemctl enable nginx`
5. Start Nginx: `sudo systemctl start nginx`

**Risks:** Low (Apache2 can be reinstalled if needed)

#### **Option B: Keep Apache2, Configure Nginx on Different Ports**
**Advantages:**
- Preserves Apache2 (if other services depend on it)
- Nginx on alternative ports (8081, 8082, etc.)

**Disadvantages:**
- More complexity
- Two web servers consuming resources
- Users access app via non-standard ports

#### **Option C: Reconfigure Apache2 for Budget Control**
**Advantages:**
- Single web server
- Uses Apache2 as reverse proxy

**Disadvantages:**
- Apache2 is heavier than Nginx
- More complex configuration
- Less optimal for modern apps

---

## Recommendation: Option A (Remove Apache2)

**Reasoning:**
1. Budget Control requires minimal web server features
2. Nginx is optimal for reverse proxy (our use case)
3. Apache2 appears to be legacy/unused (no recent activity except 24h reload)
4. Frees up memory (Apache2 uses 20.2MB)
5. Reduces security surface area (fewer moving parts)

**Deployment Plan with Option A:**
1. Stop & disable Apache2 (prevents conflicts)
2. Verify Nginx starts successfully on 80/8080/8443
3. Deploy Budget Control application
4. Configure Nginx as reverse proxy
5. Set up SSL/TLS with Let's Encrypt

---

## MariaDB Security Findings

### Issues Detected
- Authentication failures from multiple users:
  - backups@localhost
  - root@localhost
  - test_user@localhost
  - dashboard@localhost
  - deployment_user@localhost
  - dashboard_user@localhost

### Action Required Before Deployment
```bash
# 1. Secure MariaDB root account
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"

# 2. Remove test accounts
sudo mysql -e "DROP USER IF EXISTS 'test_user'@'localhost';"
sudo mysql -e "DROP USER IF EXISTS 'dashboard'@'localhost';"
sudo mysql -e "DROP USER IF EXISTS 'deployment_user'@'localhost';"
sudo mysql -e "DROP USER IF EXISTS 'dashboard_user'@'localhost';"

# 3. Create Budget Control database user
sudo mysql -e "CREATE USER 'budgetapp'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON budget_control.* TO 'budgetapp'@'localhost';"

# 4. Flush privileges
sudo mysql -e "FLUSH PRIVILEGES;"
```

---

## Network Connectivity Notes

### Cisco Firewall Restriction
- **Port 80 (HTTP)** is blocked by upstream Cisco firewall
- Server-level iptables allows it, but network ACL blocks
- **Workaround**: Use HTTPS (port 443) or alternative
- **Budget Control Impact**: Minimal (app supports HTTPS)

### SSH Access
- 3 active SSH tunnels to external IPs
- SSH hardening already configured
- Passwordless key-based auth: Enabled
- Status: Secure

### DNS & HTTPS
- Both working correctly
- No connectivity issues detected

---

## Deployment Decision Matrix

| Component | Current State | Action | Priority |
|-----------|---------------|--------|----------|
| Apache2 | Running on 8080/8443 | Remove for Nginx | P0 (Blocking) |
| Nginx | Failed to start | Fix port conflict | P0 (Blocking) |
| MariaDB | Running with failed auth | Secure user accounts | P1 (Important) |
| Firewall | Configured correctly | Verify rules | P2 (Monitor) |
| SSH | Passwordless auth working | ✓ Ready | ✓ Complete |
| /backup | Created with 611GB | ✓ Ready | ✓ Complete |
| PHP 8.2 | Installed | ✓ Ready | ✓ Complete |

---

## Revised Deployment Phase

### Phase 1A: Infrastructure Preparation (1-2 hours)
1. **Stop & Remove Apache2**
   - `sudo systemctl stop apache2`
   - `sudo systemctl disable apache2`
   - `sudo apt-get remove apache2 -y`

2. **Start Nginx**
   - `sudo systemctl enable nginx`
   - `sudo systemctl start nginx`
   - Verify: `sudo netstat -tlnp | grep nginx`

3. **Secure MariaDB**
   - Reset root password
   - Remove test user accounts
   - Create budgetapp database user

4. **Verify Firewall**
   - Confirm ports 2222, 8080, 8443 are open
   - Test connectivity: `curl http://localhost:8080`

### Phase 1B: System Hardening (1 hour)
1. System updates (apt upgrade)
2. Create budgetapp user
3. Configure SSH hardening
4. Enable monitoring (fail2ban, auditd)

### Phase 2: Application Deployment (1-2 hours)
1. Copy Budget Control files to /var/www/budget-control
2. Set permissions for budgetapp user
3. Configure PHP-FPM
4. Configure Nginx reverse proxy

### Phase 3: SSL & Security (1-2 hours)
1. Install Certbot
2. Obtain Let's Encrypt certificate
3. Configure HTTPS on 8443
4. Redirect HTTP → HTTPS

### Phase 4: Testing & Go-Live (1-2 hours)
1. Functional testing (create account, transactions, etc.)
2. Security testing (SSL/TLS, headers, etc.)
3. Performance testing (load, response time)
4. Go-live

**Total Estimated Time**: 6-8 hours

---

## Critical Decision Required

**Can we remove Apache2 and use Nginx exclusively?**

⚠️ **This decision blocks Phase 1 deployment**

Options:
1. ✅ **YES** - Remove Apache2, use Nginx only (recommended)
2. ❌ **NO** - Keep Apache2, need alternative strategy
3. ⏸️ **INVESTIGATE** - Check if Apache2 is needed for other services

**Your decision determines deployment path**

---

## Next Steps

1. **Decide**: Apache2 removal approach (Option A/B/C)
2. **Secure**: MariaDB user accounts
3. **Verify**: Nginx startup after Apache2 removal
4. **Deploy**: Budget Control application
5. **Test**: Application functionality
6. **Go-Live**: Point external traffic to app

---

*Infrastructure Analysis Report*
*Prepared for: Budget Control Deployment*
*Status: Awaiting decision on Apache2 removal*
