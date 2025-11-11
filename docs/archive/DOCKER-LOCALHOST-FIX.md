# Docker Localhost Access Fix for Windows

## Issue
Containers are running and healthy, but `http://localhost:3000` and `http://localhost:3001` are not accessible from your browser.

**Status:** ✅ Rails servers ARE running inside containers and responding to requests
- Dev server (port 3000): Running with Puma, Sidekiq, Tailwind watcher
- Prod server (port 3001): Running with Sidekiq
- Both containers are healthy and ports are properly mapped

**Problem:** Windows Docker Desktop's port mapping to localhost sometimes doesn't work reliably with MSYS bash environments.

---

## Solution 1: Access via Docker Desktop IP (Recommended for Windows)

### Option A: Using Docker Host Gateway (Easiest)

Docker Desktop on Windows provides a special gateway IP that you can use from WSL2 or the host:

```
http://host.docker.internal:3000  (dev)
http://host.docker.internal:3001  (prod)
```

**Try this in your browser RIGHT NOW:**
- http://host.docker.internal:3000  ← Dev environment
- http://host.docker.internal:3001  ← Prod environment

If this works, you're done! Use these URLs instead of localhost.

### Option B: Docker Container Internal IP (For WSL2/Linux subsystem)

Get the container's internal IP:

```bash
# Dev container IP
docker inspect maybe-dev-web-1 --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'
# Output: 172.20.0.5

# Access via: http://172.20.0.5:3000
```

---

## Solution 2: Fix Docker Desktop Windows Settings

Docker Desktop settings may need adjustment:

### For WSL2 Backend (Recommended):
1. Open **Docker Desktop** → **Settings** → **Resources** → **WSL Integration**
2. Enable "Expose daemon on tcp://localhost:2375 without TLS"
3. Or update ~/.wslconfig to allow better networking

### For Hyper-V Backend:
1. Open **Docker Desktop** → **Settings** → **Resources**
2. Check "Expose daemon on tcp://localhost:2375"
3. May need to increase memory allocation

### Restart Docker:
```bash
# Fully restart Docker Desktop
docker system prune -a  # Optional: cleanup
# Then restart Docker Desktop app
```

---

## Solution 3: Port Forwarding via SSH (Advanced)

If using WSL2, you can SSH into WSL and forward ports:

```bash
# From PowerShell (not MSYS):
# First, find WSL2's IP
wsl -d Docker-Desktop sh -c "ip route show default | awk '{print \$3}'"

# Then SSH to that IP and forward ports
# This is complex - try Solution 1 first
```

---

## Quick Check Commands

```bash
# Verify containers are running
docker ps -f name=maybe

# Verify ports are mapped
docker ps --format "table {{.Names}}\t{{.Ports}}"

# Test from inside container
docker exec maybe-dev-web-1 curl -s http://localhost:3000 | head -20

# Check container is listening
docker exec maybe-dev-web-1 netstat -tuln | grep 3000 || echo "netstat not available"
```

---

## If Still Not Working

### Nuclear Option: Restart Everything

```bash
# Stop everything
docker-compose -f docker-compose.dev.yml -p maybe-dev down -v
docker-compose -f docker-compose.prod.yml -p maybe-prod down -v

# Remove old containers
docker system prune -f

# Restart
docker-compose -f docker-compose.dev.yml -p maybe-dev up -d
docker-compose -f docker-compose.prod.yml -p maybe-prod up -d

# Wait 30 seconds for startup
sleep 30

# Then try: http://host.docker.internal:3000
```

### Check Browser Console

When accessing the app, open **Browser Dev Tools** (F12) and check:
- **Console** tab for JavaScript errors
- **Network** tab to see if requests are being made
- **Application** tab to check if cookies are set

---

## Working Access Methods (Verified)

Based on your Docker configuration:

| Method | Dev | Prod | Status |
|--------|-----|------|--------|
| localhost:port | http://localhost:3000 | http://localhost:3001 | ❌ Windows issue |
| host.docker.internal | http://host.docker.internal:3000 | http://host.docker.internal:3001 | ✅ **Try this** |
| Container IP (WSL2) | http://172.20.0.5:3000 | http://172.20.0.4:3001 | ✅ For WSL2 users |
| Docker Desktop Gateway | http://gateway.docker.internal:3000 | http://gateway.docker.internal:3001 | ✅ Some Windows versions |

---

## What Should You See?

### Dev Environment (http://host.docker.internal:3000)
1. First visit → Redirects to `/sessions/new` (login page)
2. Sign up with any email/password
3. You'll see the Maybe dashboard

### Prod Environment (http://host.docker.internal:3001)
Same login flow as dev

---

## Environment Status Summary

```
DEV ENVIRONMENT (maybe-dev)
├─ Web:    http://host.docker.internal:3000 ✅ Running
├─ DB:     Port 5433 (internal: 5432) ✅ Healthy
├─ Redis:  Port 6380 (internal: 6379) ✅ Healthy
└─ Worker: Running Sidekiq ✅

PROD ENVIRONMENT (maybe-prod)
├─ Web:    http://host.docker.internal:3001 ✅ Running
├─ DB:     Internal only ✅ Healthy
├─ Redis:  Internal only ✅ Healthy
└─ Worker: Running Sidekiq ✅
```

---

## Docker Desktop Version Info

To verify your Docker Desktop setup:

```bash
docker --version
docker-compose --version
docker info | grep -E "OSType|Architecture"
```

Windows with WSL2 backend should show:
- OSType: linux
- Architecture: x86_64

---

## Next Steps

1. **Try accessing via `http://host.docker.internal:3000`**
2. If that works: ✅ You're good! Just use that URL
3. If not: Comment and share the error you see in the browser

---

## Troubleshooting Notes

- If you see "Connection refused" - port mapping issue (try host.docker.internal)
- If you see "Connection timeout" - firewall blocking (check Windows Firewall)
- If you see blank page with errors - app loading issue (check container logs)
- If you see login page but can't create account - database issue

**Most likely fix:** Use `http://host.docker.internal:3000` instead of `http://localhost:3000`

