# 🚀 Server SSH Deployment Commands

## 📋 SSH Bağlantısı ve İlk Kurulum

### 1. Server'a Bağlan
```bash
# SSH ile server'a bağlan (örnek)
ssh username@trek-turkey.com
# veya
ssh username@[server-ip]
```

### 2. Deployment Directory'ye Git
```bash
# Ana web directory'ye git
cd /var/www/vhosts/trek-turkey.com/httpdocs

# Mevcut dosyaları backup'la (opsiyonel)
mkdir -p backup-$(date +%Y%m%d)
cp -r * backup-$(date +%Y%m%d)/ 2>/dev/null || true

# Directory'yi temizle
rm -rf *
```

### 3. GitHub Repository'yi Clone Et
```bash
# Repository'yi clone et
git clone https://github.com/riotebike-byte/trek-sales-hub-clean.git .

# Verify files
ls -la

# Should see:
# simple-proxy.js
# package.json  
# real-bicycle-dashboard.html
# data-analyzer.html
# admin-panel.html
# main-dashboard.html
# webhook-deploy.php
# deploy-from-github.sh
```

### 4. Node.js Dependencies Install
```bash
# Production dependencies install
npm install --production

# Verify installation
npm list --depth=0

# Should show:
# ├── cors@2.8.5
# ├── express@4.18.2
# └── node-fetch@3.3.2
```

### 5. File Permissions Ayarla
```bash
# Proper ownership
chown -R psaadm:psaadm .

# Proper permissions
chmod -R 755 .

# Make scripts executable
chmod +x simple-proxy.js
chmod +x deploy-from-github.sh

# Verify permissions
ls -la simple-proxy.js
```

## 🔧 Plesk Panel Konfigürasyonu

### 1. Plesk Panel'e Git
- https://[server]:8443 adresine git
- Domain: trek-turkey.com seç

### 2. Node.js Application Setup
```
Hosting & DNS → Node.js

Enable Node.js: ✅
Node.js version: 18.x.x (latest)
Application mode: production
Application startup file: simple-proxy.js
Application root: /
```

### 3. Environment Variables Ekle
```
NODE_ENV = production
PORT = 3001
```

### 4. Application'ı Başlat
- "NPM install" butonuna bas (dependencies için)
- "Restart App" butonuna bas
- Status: "Running" olmalı

## 🧪 Deployment Test

### 1. Health Check
```bash
# Server'da test
curl http://localhost:3001/health

# External test
curl https://trek-turkey.com/health
```

### 2. API Test
```bash
# API endpoint test
curl https://trek-turkey.com/api/b2b/products | head -200
```

### 3. Dashboard Test
```bash
# Dashboard erişim test
curl -I https://trek-turkey.com/real-bicycle-dashboard.html
curl -I https://trek-turkey.com/data-analyzer.html
```

## 🔄 Future Updates (Git Pull)

### Güncelleme için:
```bash
# Server'da:
cd /var/www/vhosts/trek-turkey.com/httpdocs

# Latest changes'i çek
git pull origin main

# Dependencies güncelle (gerekirse)
npm install --production

# Plesk'te application restart et
# Panel → Node.js → Restart App
```

## 📊 Verification Checklist

Deployment'tan sonra bunları kontrol et:

### ✅ Server Status:
- [ ] SSH bağlantısı başarılı
- [ ] Git clone tamamlandı
- [ ] npm install başarılı
- [ ] File permissions doğru
- [ ] Plesk Node.js enabled

### ✅ Application Status:
- [ ] Node.js app "Running" status
- [ ] Port 3001 listening
- [ ] Environment variables set
- [ ] No error logs

### ✅ Functionality Test:
- [ ] Health check: https://trek-turkey.com/health
- [ ] API response: https://trek-turkey.com/api/b2b/products
- [ ] Dashboard loads: https://trek-turkey.com/real-bicycle-dashboard.html
- [ ] Data analyzer works: https://trek-turkey.com/data-analyzer.html

### ✅ Features Working:
- [ ] Real BizimHesap API data loading
- [ ] Tax calculations showing correct rates
- [ ] MADONE products: 35% tax
- [ ] DSW categories: 70% tax
- [ ] Landed cost calculations working

## 🔧 Troubleshooting

### Common Issues:

1. **Permission Denied:**
   ```bash
   sudo chown -R psaadm:psaadm /var/www/vhosts/trek-turkey.com/httpdocs
   ```

2. **Port Already in Use:**
   ```bash
   # Check what's using port
   netstat -tulpn | grep :3001
   
   # Kill process if needed
   sudo kill -9 [PID]
   ```

3. **NPM Install Fails:**
   ```bash
   # Clear npm cache
   npm cache clean --force
   
   # Remove node_modules and reinstall
   rm -rf node_modules
   npm install --production
   ```

4. **Git Clone Issues:**
   ```bash
   # Force fresh clone
   rm -rf .git
   git clone https://github.com/riotebike-byte/trek-sales-hub-clean.git .
   ```

Ready to start deployment! 🚀