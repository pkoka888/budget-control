# Budget Control - Správce Osobních Financí

Moderní, jednoduchý a bezpečný správce osobních financí postavený na PHP, SQLite a HTML s podporou AI doporučení.

## Features

✅ **Dashboard s vizualizacemi** - Sledujte své finance v reálném čase
✅ **Import CSV** - Snadno importujte transakce z bankovního výpisu (ČSOB, ČEZ formát)
✅ **Automatická kategorizace** - AI-powered kategorizace transakcí
✅ **Analýza výdajů** - Detailní rozbor výdajů podle kategorií
✅ **Rozpočtování** - Nastavujte rozpočty a sledujte jejich dodržování
✅ **Spravování investic** - Sledujte své investiční portfolio
✅ **Finanční cíle** - Plánujte a sledujte dosažení finančních cílů
✅ **Tipy a průvodce** - Naučte se lepšímu řízení financí
✅ **AI doporučení** - Personalizovaná doporučení na základě vašich financí
✅ **Net Worth tracking** - Sledujte svou celkovou čistou hodnotu

## Technologie

- **Backend**: PHP 8.0+
- **Databáze**: SQLite 3.x
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Grafy**: Chart.js, D3.js
- **Framework**: Vlastní lightweight framework (bez závislostí)
- **AI**: OpenAI API (volitelně)

## Instalace

### Požadavky

- PHP 8.0 nebo vyšší
- SQLite 3.x (obvykle je součástí PHP)
- Web server (Apache, Nginx)

### Kroky instalace

1. **Klonujte nebo stáhněte projekt**
```bash
git clone https://github.com/yourusername/budget-control.git
cd budget-control
```

2. **Nastavte oprávnění**
```bash
chmod -R 755 .
chmod -R 777 database/
chmod -R 777 uploads/
```

3. **Vytvořte .env soubor**
```bash
cp .env.example .env
```

4. **Upravte .env dle potřeby**
```env
APP_NAME="Budget Control"
APP_DEBUG=true
TIMEZONE="Europe/Prague"
CURRENCY="CZK"
DATABASE_PATH="./database/budget.db"
OPENAI_API_KEY="" # Volitelně pro AI doporučení
```

5. **Inicializujte databázi**

Databáze se vytvoří automaticky při prvním spuštění. Pokud ji chcete naplnit předem:

```bash
sqlite3 database/budget.db < database/schema.sql
sqlite3 database/budget.db < database/seeds.sql
```

6. **Spusťte aplikaci**

Vestavěný server PHP:
```bash
php -S localhost:8000 -t public/
```

Nebo jej nasaďte na web server (viz níže).

## Konfigurace Web Serveru

### Apache

Ujistěte se, že máte povoleny `mod_rewrite`:

```apache
<Directory /path/to/budget-control/public>
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
```

### Nginx

```nginx
server {
    listen 80;
    server_name budget-control.local;
    root /path/to/budget-control/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Použití

### Prvnímu spuštění

1. Přejděte na `http://localhost:8000`
2. Registrujte si účet
3. Přihlaste se
4. Vytvořte si účty (Běžný účet, Spořicí účet, atd.)
5. Importujte transakce z CSV

### Import CSV

1. Jděte na "Import CSV"
2. Vyberete svůj bankovní účet
3. Nahrajte CSV soubor z vaší banky
4. Aplikace automaticky:
   - Parsuje data
   - Detekuje duplikáty
   - Kategorisuje transakce
   - Importuje data

Podporované formáty:
- Český formát (dd.mm.yyyy, desetinná čárka)
- ISO formát (yyyy-mm-dd)
- US formát (mm/dd/yyyy)

### Dashboard

Dashboard zobrazuje:
- Měsíční příjmy a výdaje
- Čistou hodnotu majetku (net worth)
- Finanční zdraví (skóre 0-100)
- Top kategorie výdajů
- Trend výdajů
- AI doporučení
- Poslední transakce

### Rozpočtování

1. Jděte na "Rozpočty"
2. Vytvořte rozpočet pro kategorii
3. Nastavte částku a měsíc
4. Sledujte obsazení rozpočtu

### AI Doporučení

Pokud máte nastavit OpenAI API klíč, aplikace vám bude:
- Analyzovat finanční data
- Detekovat anomálie
- Dávat personalizovaná doporučení
- Identifikovat úspory

## Datová struktura

### Tabulky

- **users** - Uživatelé
- **accounts** - Bankovní účty
- **transactions** - Transakce
- **categories** - Kategorie výdajů
- **budgets** - Rozpočty
- **merchants** - Obchodní partnery (pro učení)
- **investments** - Investiční portfolio
- **goals** - Finanční cíle
- **ai_recommendations** - AI doporučení
- **csv_imports** - Historie importů

## API Endpoints

```
GET  /api/transactions/categorize
POST /api/transactions/categorize
GET  /api/analytics/:period
GET  /api/recommendations
```

## Bezpečnost

- Hesla jsou hashována (password_hash)
- Session-based autentizace
- CSRF ochrana (pokud bude implementována)
- SQL injekce ochrana (PDO prepared statements)
- XSS ochrana (htmlspecialchars)

## Troubleshooting

### Databáze se neotevírá
```
chmod 777 database/
chmod 777 database/budget.db
```

### CSV import nefunguje
- Zkontrolujte formát data (dd.mm.yyyy)
- Zkontrolujte kódování souboru (UTF-8)
- Zkontrolujte oddělování sloupců (čárka nebo bod)

### AI doporučení nefungují
- Zkontrolujte, zda máte nastaven OPENAI_API_KEY
- Zkontrolujte, zda je API klíč platný
- Zkontrolujte kredity OpenAI účtu

## Vývoj

### Struktura projektu

```
budget-control/
├── public/              # Web root
│   ├── index.php        # Entry point
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
├── src/
│   ├── Application.php
│   ├── Router.php
│   ├── Database.php
│   ├── Config.php
│   ├── Controllers/     # Kontrolléry
│   └── Services/        # Byznysová logika
├── views/               # Šablony
├── database/
│   ├── schema.sql
│   └── seeds.sql
└── config/              # Konfigurační soubory
```

### Přidání nového controlleru

```php
<?php
namespace BudgetApp\Controllers;

class MyController extends BaseController {
    public function myAction() {
        echo $this->render('my-template', ['data' => 'value']);
    }
}
```

### Přidání nové trasy

V `Application.php`:

```php
$this->router->get('/myroute', 'MyController@myAction');
$this->router->post('/myroute', 'MyController@create');
```

## Přispívání

Přispívání jsou vítána! Prosím:

1. Forkněte projekt
2. Vytvořte feature branch (`git checkout -b feature/AmazingFeature`)
3. Commitujte změny (`git commit -m 'Add AmazingFeature'`)
4. Pushujte na branch (`git push origin feature/AmazingFeature`)
5. Otevřete Pull Request

## Licence

Tento projekt je pod licencí MIT. Viz `LICENSE` soubor pro více informací.

## Autor

Vytvořeno s ❤️ pro lepší správu osobních financí

## Podpora

Máte otázky nebo chyby? Otevřete issue na GitHub.

## Plánované funkce

- [ ] Mobilní aplikace
- [ ] 2FA autentizace
- [ ] Bank sync (Plaid integration)
- [ ] Multi-currency support
- [ ] Exporty do PDF/Excel
- [ ] Sdílení financí v rodině
- [ ] Více jazyků
- [ ] Dark mode
- [ ] Plánování důchodu
- [ ] Daňové plánování

## MěměmeChangelog

### v1.0.0 (2025-11-08)
- Iniciální release
- Dashboard, CSV import, rozpočetování
- AI doporučení
- Finanční analýza
