# Instalační Průvodce - Budget Control

Kompletní průvodce instalací a konfigurací aplikace Budget Control.

## Předpoklady

Před instalací se ujistěte, že máte:

- **PHP 8.0 nebo vyšší** (ověřeno `php -v`)
- **SQLite 3.x** (obvykle součást PHP, ověřete `php -m | grep sqlite`)
- **Web server** (Apache, Nginx, nebo PHP built-in server)
- **Git** (na stažení projektu)
- **Přístup do terminálu/příkazového řádku**

## Krok 1: Stažení projektu

```bash
# Klonujte GitHub repo
git clone https://github.com/yourusername/budget-control.git
cd budget-control/budget-app

# Nebo stáhněte ZIP a rozbalte
unzip budget-control.zip
cd budget-control/budget-app
```

## Krok 2: Nastavení oprávnění (Linux/Mac)

```bash
# Umožněte zápis do databázové složky
chmod -R 755 .
chmod -R 777 database/
chmod -R 777 uploads/
chmod -R 777 public/assets/

# Zkontrolujte vlastníka
ls -la database/
```

## Krok 3: Konfigurace prostředí

```bash
# Zkopírujte šablonový .env soubor
cp .env.example .env

# Upravte .env podle potřeby (editor: nano, vim, VS Code apod.)
nano .env
```

### Důležité nastavení .env

```env
# Debug mode pro vývoj (vypněte na produkci)
APP_DEBUG=true

# Cesta k databázi (výchozí je v pořádku)
DATABASE_PATH=./database/budget.db

# Pokud máte OpenAI klíč
OPENAI_API_KEY=sk-...
```

## Krok 4: Inicializace databáze

Databáze se vytvoří automaticky. Pokud chcete ji předvyplnit s tips:

```bash
# Příkazový řádek SQLite
sqlite3 database/budget.db < database/schema.sql
sqlite3 database/budget.db < database/seeds.sql

# Ověřte, že tabulky existují
sqlite3 database/budget.db ".tables"
```

## Krok 5: Spuštění aplikace

### Možnost A: Built-in PHP Server (Vývoj)

```bash
# Nejjednoduší způsob pro lokální vývoj
php -S localhost:8000 -t public/

# Pak otevřete http://localhost:8000 v prohlížeči
```

### Možnost B: Apache

1. **Umístěte projekt do Apache root:**
   - Linux: `/var/www/html/budget-control`
   - macOS: `/Library/WebServer/Documents/budget-control`
   - Windows: `C:\xampp\htdocs\budget-control`

2. **Povolte mod_rewrite:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **Vytvořte Virtual Host:**
   ```apache
   # /etc/apache2/sites-available/budget-control.conf
   <VirtualHost *:80>
       ServerName budget-control.local
       DocumentRoot /var/www/html/budget-control/public

       <Directory /var/www/html/budget-control/public>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted

           <IfModule mod_rewrite.c>
               RewriteEngine On
               RewriteBase /
               RewriteCond %{REQUEST_FILENAME} !-f
               RewriteCond %{REQUEST_FILENAME} !-d
               RewriteRule ^(.*)$ index.php?/$1 [QSA,L]
           </IfModule>
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/budget-control-error.log
       CustomLog ${APACHE_LOG_DIR}/budget-control-access.log combined
   </VirtualHost>
   ```

4. **Aktivujte a restartujte:**
   ```bash
   sudo a2ensite budget-control
   sudo systemctl restart apache2
   ```

5. **Přidejte do /etc/hosts:**
   ```
   127.0.0.1  budget-control.local
   ```

6. **Otevřete http://budget-control.local**

### Možnost C: Nginx

1. **Vytvořte server block:**
   ```nginx
   # /etc/nginx/sites-available/budget-control
   server {
       listen 80;
       server_name budget-control.local;
       root /var/www/budget-control/public;

       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/run/php/php8.0-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       }

       error_log /var/log/nginx/budget-control-error.log;
       access_log /var/log/nginx/budget-control-access.log;
   }
   ```

2. **Aktivujte:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/budget-control /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

## Krok 6: První přihlášení

1. Otevřete aplikaci v prohlížeči
2. Klikněte na "Registrovat"
3. Vyplňte svůj e-mail a heslo
4. Přihlaste se
5. Vytvořte své první účty

## Troubleshooting

### Chyba: "Permission denied" na databázi

```bash
# Nastavte správná oprávnění
chmod 777 database/
chmod 666 database/budget.db

# Nebo změní vlastníka
sudo chown www-data:www-data database/ -R
```

### Chyba: "SQLite module not enabled"

```bash
# Zkontrolujte PHP instalaci
php -m | grep sqlite

# Pokud chybí, nainstalujte:
# Ubuntu/Debian
sudo apt-get install php-sqlite3

# macOS
brew install php sqlite
```

### Chyba: "Class not found"

- Zajistěte, že jsou soubory správně umístěny
- Zkontrolujte, že PHP má přístup ke všem souborům
- Ověřte PHP verzi (musí být 8.0+)

### Prázdný dashboard

- Vytvořte účet
- Přidejte transakce nebo importujte CSV
- Refreshujte stránku (Ctrl+F5)

### CSV import nefunguje

1. Zkontrolujte formát data (dd.mm.yyyy)
2. Zkontrolujte kódování (musí být UTF-8)
3. Zkontrolujte CSV strukturu (3-4 sloupce)
4. Zkontrolujte oprávnění `uploads/` složky

## Bezpečnost

### Pro produkci

```env
# Vypněte debug mode
APP_DEBUG=false

# Použijte silné heslo pro databázi (pokud MySQL)
DATABASE_PASSWORD=very_strong_password_123

# Nastavte HTTPS
APP_URL=https://budget-control.com

# Vytvořte backupy
```

### Backupy databáze

```bash
# Manuální backup
sqlite3 database/budget.db ".backup backup.db"

# Automatizovaný backup (cron)
0 2 * * * sqlite3 /var/www/budget-control/database/budget.db ".backup /backups/budget-$(date +\%Y\%m\%d).db"
```

## Aktualizace

Když vyjde nová verze:

```bash
# Stáhněte nejnovější kód
git pull origin main

# Spusťte migrace (pokud existují)
php migrate.php

# Vymažte cache (pokud existuje)
rm -rf storage/cache/*
```

## Podpora a pomoc

- 📖 [Dokumentace](README.md)
- 🐛 [Hlášení chyb](https://github.com/yourusername/budget-control/issues)
- 💬 [Diskuse](https://github.com/yourusername/budget-control/discussions)

## Poznámky

- Aplikace používá SQLite, která je ideální pro osobní použití
- Pro velké nasazení zvažte migraci na MySQL/PostgreSQL
- Pravidelně zálohujte svou databázi
- Držujte PHP a SQLite aktualizované

---

**Vítejte v Budget Control! Budeme vám pomáhat spravovat vaše finanče. 💰**
