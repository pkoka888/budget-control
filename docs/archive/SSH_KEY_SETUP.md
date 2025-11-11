# 🔑 SSH Key Setup for backup@89.203.173.196

**Generated**: November 9, 2025
**User**: backup
**Server**: 89.203.173.196:2222
**Key Type**: RSA 4096-bit

---

## 📋 SSH Key Information

### **Private Key Location (LOCAL)**
```
~/.ssh/backup_key
```

### **Public Key (for server)**
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDaWuljylqSNPfL14uz3+AG7+jZOjzSpnc6hyCenfuKpWGicoDJYOo0KvKg7140QPXNmHFydyW+xj7WJX9xqdepiZGR3ywSLZnLK9fxz0r4ZM+AIGe/m87PhA+TKQh5Pym7sZTMs8LTV5SihzaiX9tCW29A+t5z4GJqpG/pZls/c6mfwy2Ytf1cnhH6LokmJf//g1wobn8pbS+tX//0M/i7am6khQRRz3ZMinkJZq5bdtBNbxSTIRqIL1DCrczj9JlhR6lodCbGNb/aQTCWT25cg98kuZ5X8OQbYa/KUicAoumb+SsH/raNXUJ0oTuXVPRok2FPfo6mcfWClwCr59xTBJkj/J735zvt16AiKT6wdvexj/nijHsS4pVZ8+2Kdr8zyVPKMvrH27I6C51//99dQMBcvm+Gvl5XuFw8nlF6IMHU0rNY6+MSeAvQRMjE4RBNKnRBpnYT1iMZe7TayZmkgmm+etgL1Uey508J7AgA1NQda3JEpQB2mnsqN4rursd0CIQ0XVYlX2GqaPjckTlFn1DAOzyzresDqYLEEKBx5XMVVI3xxz66nUWRG+L8XtraOB7uRUh7T1xUeI5FOcrCMczfzF4R5Y8pC6ULArLVDx8zXjsmRp2lfCm2lf6z69P0GVprbk874TfnBdjpp/TdUnv+TkMno8d0sKp3I4pglQ== backup@89.203.173.196
```

---

## 🔧 Setup on Server (Required Steps)

To complete SSH key authentication, you need to:

### **1. Log into server (initial connection needed)**

Using temporary password method or console access:
```bash
ssh -p 2222 backup@89.203.173.196
# Enter password when prompted
```

### **2. Create .ssh directory**
```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
```

### **3. Add public key to authorized_keys**
```bash
cat >> ~/.ssh/authorized_keys << 'EOF'
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDaWuljylqSNPfL14uz3+AG7+jZOjzSpnc6hyCenfuKpWGicoDJYOo0KvKg7140QPXNmHFydyW+xj7WJX9xqdepiZGR3ywSLZnLK9fxz0r4ZM+AIGe/m87PhA+TKQh5Pym7sZTMs8LTV5SihzaiX9tCW29A+t5z4GJqpG/pZls/c6mfwy2Ytf1cnhH6LokmJf//g1wobn8pbS+tX//0M/i7am6khQRRz3ZMinkJZq5bdtBNbxSTIRqIL1DCrczj9JlhR6lodCbGNb/aQTCWT25cg98kuZ5X8OQbYa/KUicAoumb+SsH/raNXUJ0oTuXVPRok2FPfo6mcfWClwCr59xTBJkj/J735zvt16AiKT6wdvexj/nijHsS4pVZ8+2Kdr8zyVPKMvrH27I6C51//99dQMBcvm+Gvl5XuFw8nlF6IMHU0rNY6+MSeAvQRMjE4RBNKnRBpnYT1iMZe7TayZmkgmm+etgL1Uey508J7AgA1NQda3JEpQB2mnsqN4rursd0CIQ0XVYlX2GqaPjckTlFn1DAOzyzresDqYLEEKBx5XMVVI3xxz66nUWRG+L8XtraOB7uRUh7T1xUeI5FOcrCMczfzF4R5Y8pC6ULArLVDx8zXjsmRp2lfCm2lf6z69P0GVprbk874TfnBdjpp/TdUnv+TkMno8d0sKp3I4pglQ== backup@89.203.173.196
EOF
```

### **4. Set correct permissions**
```bash
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

### **5. Verify setup**
```bash
cat ~/.ssh/authorized_keys
```

---

## ✅ Test SSH Key Connection

Once server is configured with the public key:

```bash
# Test SSH connection with key
ssh -i ~/.ssh/backup_key -p 2222 backup@89.203.173.196

# Should connect without password prompt
```

---

## 📝 Key Details

| Property | Value |
|----------|-------|
| Algorithm | RSA |
| Key Size | 4096 bits |
| Fingerprint | SHA256:fwHI3YDE+98uYbshBz9NJyh0dXtqCGaYxLGgsd6cQKM |
| User | backup |
| Server | 89.203.173.196 |
| Port | 2222 |
| Local Path | ~/.ssh/backup_key |
| Comment | backup@89.203.173.196 |

---

## 🔒 Security Notes

1. **Private key file** (`backup_key`):
   - Keep this SECURE
   - Never share or commit to version control
   - File permissions: 600 (owner read/write only)

2. **Public key** (shown above):
   - Safe to share and publish
   - Added to server's authorized_keys
   - Only allows authentication, no other access

3. **Key Passphrase**:
   - This key has NO passphrase
   - For production, consider adding passphrase: `ssh-keygen -p -f ~/.ssh/backup_key`

---

## 🚀 Using the Key

Once configured on server:

```bash
# SSH into server
ssh -i ~/.ssh/backup_key -p 2222 backup@89.203.173.196

# Run commands
ssh -i ~/.ssh/backup_key -p 2222 backup@89.203.173.196 'ls -la'

# SCP files
scp -i ~/.ssh/backup_key -P 2222 file.txt backup@89.203.173.196:~/
```

---

## ⚠️ Next Step

**You need to**:
1. Add the public key to the server's `~/.ssh/authorized_keys`
2. Or provide the server's password so we can do it remotely
3. Then we can proceed with full server analysis

Once SSH key is set up, we'll be able to:
- ✅ Analyze complete server configuration
- ✅ Check disk space and resources
- ✅ Verify software availability
- ✅ Plan and execute deployment

---

*SSH Key Setup*
*Version 1.0 - November 9, 2025*
