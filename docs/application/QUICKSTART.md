# Quick Start - Budget Control

Spustěte aplikaci v **5 minut** bez složitosti.

## Pro Ungrouped (Windows, Mac, Linux)

### 1. Příprava (1 min)

```bash
# Přejděte do složky
cd budget-control/budget-app

# Zkopírujte config
cp .env.example .env

# (Mac/Linux) Nastavte oprávnění
chmod 777 database/ uploads/ -R
```

### 2. Spuštění (1 min)

```bash
# Spusťte built-in PHP server
php -S localhost:8000 -t public/
```

Měli byste vidět:
```
[Mon Nov 08 15:30:45 2025] PHP 8.x Development Server started at http://localhost:8000
[Mon Nov 08 15:30:45 2025] Listening on http://localhost:8000
```

### 3. Otevřete aplikaci (1 min)

```
http://localhost:8000
```

### 4. Registrace (1 min)

1. Klikněte "Registrovat"
2. Zadejte:
   - Email: `test@example.com`
   - Heslo: `SecurePass123!`
3. Klikněte "Registrovat"

### 5. První kroky (1 min)

```
✓ Dashboard se otevře automaticky
✓ Jděte na "Účty" → Vytvořit nový účet
✓ Vyberte typ: "Běžný účet"
✓ Jméno: "Moje první účet"
✓ Klikněte "Vytvořit"
```

---

## Import CSV

Máte CSV soubor od banky?

1. Jděte na **"Import CSV"**
2. Vyberte účet
3. Nahrajte `Finance - Prijem-Vydej.csv`
4. Klikněte "Importovat"

**Během 10 sekund** budete mít:
- 1000+ transakcí
- Automatickou kategorizaci
- Vygenerované reporty

---

## Co dělat dál?

### 👀 Prozkoumat
- **Dashboard**: Přehled vašich financí
- **Transakce**: Detailní seznam
- **Tipy**: Naučte se spravovat peníze
- **Rozpočty**: Nastavte rozpočet

### ⚙️ Konfigurovat (Volitelné)
- **Nastavení** → Profil
- **Kategorie** → Přidejte svoje
- **Rozpočty** → Nastavte limity

### 🤖 AI (Volitelné)
Pokud máte OpenAI API klíč:

```bash
# Upravte .env
nano .env
# OPENAI_API_KEY=sk-xxxxxx

# Restartujte server
# Klikněte "Generovat doporučení"
```

---

## Troubleshooting

### ❌ "Failed to create stream"
```bash
# Nový terminál, znovu spusťte
php -S localhost:8000 -t public/
```

### ❌ "database folder permission"
```bash
# Mac/Linux
chmod 777 database/ -R

# Windows (Admin Command Prompt)
icacls database /grant Everyone:F
```

### ❌ "Blank page"
```bash
# Zkontrolujte PHP verzi (musí být 8.0+)
php -v

# Zkontrolujte si výstup:
# public/index.php
```

### ❌ "SQLite not found"
```bash
# Linux/Mac
php -m | grep sqlite

# Nainstalujte (pokud chybí)
sudo apt install php-sqlite3  # Debian/Ubuntu
brew install php sqlite        # Mac
```

---

## Příklady CSV

Očekávaný formát vašeho CSV:

```csv
04.11.2025,WWW.PERPLEXITY.AI,524.20,50000.00
31.10.2025,MÓJAFIRMA S.R.O.,50000.00,49475.80
30.10.2025,KAUFLAND,1234.50,49475.80
```

**Sloupce:**
1. Datum (dd.mm.yyyy)
2. Popis/Obchodní partner
3. Částka (kladná/záporná)
4. Zůstatek (volitelné)

---

## Dalšími Zdroje

| Dokument | Obsah |
|----------|-------|
| `README.md` | Úplná dokumentace |
| `INSTALLATION.md` | Detailní instalace |
| `PROJECT_SUMMARY.md` | Architektura |

---

## Potřebujete pomoc?

```bash
# Resetujte databázi
rm database/budget.db
# Aplikace ji znovu vytvoří

# Spusťte seeders (tipy)
sqlite3 database/budget.db < database/seeds.sql

# Zkontrolujte tabulky
sqlite3 database/budget.db ".tables"
```

---

## Další Kroky

Jakmile se seznámíte:

1. **Proveďte CSV import** - vaše reálná data
2. **Nastavte rozpočty** - sledujte výdaje
3. **Čtěte tipy** - naučte se spravovat peníze
4. **Sledujte finanční zdraví** - skóre 0-100

---

## Poznámky

- Hesla jsou bezpečně hashována
- Data se ukládají lokálně v SQLite
- Bez internetu → bez cloudové synchronizace
- Pro backup: stačí zkopírovat `database/budget.db`

---

**Hotovo! Užijte si správu svých financí. 💰**

Otázky? Přečtěte si README.md nebo otevřete issue.
