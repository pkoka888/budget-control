# Maybe Finance & Personal Finance Ecosystem - GitHub Repository Guide

A comprehensive directory of GitHub repositories related to Maybe Finance and the broader personal finance ecosystem, including official repos, forks, integrations, and alternatives.

**Last Updated:** November 7, 2025
**Total Repositories Listed:** 100+

---

## Table of Contents

1. [Official Maybe Finance Repositories](#1-official-maybe-finance-repositories)
2. [Community Forks & Projects](#2-community-forks--projects)
3. [Integrations & Tools](#3-integrations--tools)
4. [Alternative Personal Finance Apps](#4-alternative-personal-finance-apps)
5. [Finance Data & Import Tools](#5-finance-data--import-tools)
6. [Transaction Categorization & AI](#6-transaction-categorization--ai-tools)
7. [Additional Notable Projects](#7-additional-notable-projects)
8. [Quick Reference](#quick-reference)

---

## 1. Official Maybe Finance Repositories

### Primary Repository

| Name | URL | Stars | Language | Updated | Status |
|------|-----|-------|----------|---------|--------|
| **maybe** | https://github.com/maybe-finance/maybe | 53,354 ⭐ | Ruby, JS, HTML, CSS | 2025-11-07 | **ACTIVE - MAIN REPO** |

**Description:** The official Maybe Finance personal finance application. Self-hosted, open-source, privacy-first approach to personal finance management.

**Key Features:**
- Rails 7.2 backend with Hotwire frontend
- Multi-account tracking (checking, savings, investments, crypto, loans, properties)
- Transaction management with categorization
- CSV import functionality
- Plaid integration for bank syncing
- Multi-currency support
- API access with OAuth2 and API keys
- Sidekiq background jobs
- Docker support

**Forks:** 4,569 (very active community)

---

### Other Official Maybe Finance Projects

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **maybe-archive** | https://github.com/maybe-finance/maybe-archive | 357 | TypeScript | 2025-11-06 | Archive of previous TypeScript version |
| **marketing** | https://github.com/maybe-finance/marketing | 54 | Ruby | 2025-10-24 | Official marketing/public website |
| **marketing-archive** | https://github.com/maybe-finance/marketing-archive | 72 | TypeScript | 2025-10-06 | Archived marketing site |
| **synth-archive** | https://github.com/maybe-finance/synth-archive | 65 | Ruby | 2025-10-07 | Fintech tools/market data service (archived) |
| **emojis** | https://github.com/maybe-finance/emojis | 15 | N/A | 2025-10-06 | Custom Maybe-styled emoji set |
| **synth-mcp** | https://github.com/maybe-finance/synth-mcp | 2 | TypeScript | 2025-08-16 | Model Context Protocol for Synth |

---

## 2. Community Forks & Projects

### Major Community Fork: "Sure"

| Name | URL | Stars | Language | Updated | Status |
|------|-----|-------|----------|---------|--------|
| **sure** | https://github.com/we-promise/sure | 728 | Ruby | 2025-11-06 | **ACTIVE FORK** |
| **we-promise/website** | https://github.com/we-promise/website | 10 | Ruby | 2025-11-02 | Community website |

**⚠️ Important:** NOT affiliated with or endorsed by Maybe Finance Inc.

**Description:** Community-maintained fork of Maybe Finance focusing on community governance and features.

---

### Other Community Projects

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **Driztek/maybe-finance** | https://github.com/Driztek/maybe-finance | 2 | Ruby | 2025-11-07 | Personal fork |
| **Driztek/Maybe-Finance-Dashboard** | https://github.com/Driztek/Maybe-Finance-Dashboard | 4 | C# | 2025-11-07 | Money Manager Ex dashboard |

---

## 3. Integrations & Tools

### SimpleFIN Integration (Popular)

SimpleFIN provides aggregated financial account access. Multiple projects connect Maybe to SimpleFIN:

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **simplefin_to_maybe** | https://github.com/steveredden/simplefin_to_maybe | 28 | Ruby | 2025-08-06 | Sync SimpleFIN transactions to Maybe |
| **SimpleFIN-to-Maybe** | https://github.com/Toby-Null/SimpleFIN-to-Maybe | 8 | JavaScript | 2025-09-29 | Alternative SimpleFIN connector |

---

### Home Assistant Integration

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **maybe_finance_hass** | https://github.com/M123-dev/maybe_finance_hass | 10 | Shell | 2025-11-05 | Home Assistant custom integration |

---

### Other Integrations & Deployment Tools

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **powens-maybe-finance-connector** | https://github.com/NathanKun/powens-maybe-finance-connector | 2 | Rust | 2025-08-11 | Powens (EU bank aggregator) integration |
| **maybe-helm** | https://github.com/channel-42/maybe-helm | 1 | YAML | 2025-07-31 | Kubernetes Helm chart for Maybe |
| **maybe-finance-docker** | https://github.com/bymcs/maybe-finance-docker | 0 | PowerShell | 2025-07-20 | Docker Compose setup |

---

## 4. Alternative Personal Finance Apps

### 🔴 Firefly III Ecosystem (Largest Alternative)

**Main Project:**

| Name | URL | Stars | Language | Updated | Status |
|------|-----|-------|----------|---------|--------|
| **firefly-iii** | https://github.com/firefly-iii/firefly-iii | 21,379 ⭐ | PHP | 2025-11-07 | **MAIN - HIGHLY ACTIVE** |

**Description:** Feature-rich personal finance manager with double-entry bookkeeping, written in Laravel PHP. Larger ecosystem than Maybe.

---

**Data Import for Firefly III:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **data-importer** | https://github.com/firefly-iii/data-importer | 665 | PHP | 2025-11-07 | Official data import tool |
| **import-configurations** | https://github.com/firefly-iii/import-configurations | 151 | - | 2025-10-28 | Community CSV configs |

---

**Mobile Apps & Companions for Firefly III:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **firefly-pico** | https://github.com/cioraneanu/firefly-pico | 746 | Vue | 2025-11-07 | Web companion for easy entry |
| **abacus** | https://github.com/victorbalssa/abacus | 742 | TypeScript | 2025-11-06 | iOS app for Firefly III |
| **waterfly-iii** | https://github.com/dreautall/waterfly-iii | 576 | Dart | 2025-11-07 | Unofficial Android app |
| **FireflyMobile** | https://github.com/emansih/FireflyMobile | 339 | Kotlin | 2025-10-15 | Kotlin Android client |

---

**Firefly III Enhancements:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **firefly-iii-ai-categorize** | https://github.com/bahuma20/firefly-iii-ai-categorize | 202 | JavaScript | 2025-11-07 | AI auto-categorization (OpenAI) |
| **firefly-plaid-connector-2** | https://github.com/dvankley/firefly-plaid-connector-2 | 137 | Kotlin | 2025-10-28 | Plaid connector for bank sync |

---

### 🔵 Actual Budget Ecosystem

**Mobile Apps for Actual Budget:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **actual-budget-app** | https://github.com/BearTS/actual-budget-app | 170 | Swift | 2025-11-06 | Native iOS app (SwiftUI) |
| **ha-actualbudget** | https://github.com/jlvcm/ha-actualbudget | 97 | Python | 2025-11-01 | Home Assistant integration |

---

**Actual Budget Tools & Extensions:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **actual-ai** | https://github.com/sakowicz/actual-ai | 350 | TypeScript | 2025-11-05 | AI categorization for transactions |
| **actual-http-api** | https://github.com/jhonderson/actual-http-api | 136 | JavaScript | 2025-11-06 | HTTP API wrapper |
| **actualtap-py** | https://github.com/bobokun/actualtap-py | 67 | Python | 2025-11-03 | Auto-create transactions via Tap-to-Pay |
| **actual-auto-sync** | https://github.com/seriouslag/actual-auto-sync | 37 | TypeScript | 2025-11-07 | Automated sync scheduler |

---

**Documentation:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **docs** | https://github.com/actualbudget/docs | 149 | JavaScript | 2025-11-07 | Official docs & website |

---

### 🟢 YNAB Alternatives & Tools

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **financial-freedom** | https://github.com/serversideup/financial-freedom | 2,724 | Vue | 2025-11-07 | Open source Mint/YNAB alternative |
| **budgetzero** | https://github.com/budgetzero/budgetzero | 650 | Vue | 2025-10-25 | Zero-based budgeting app |
| **bank2ynab** | https://github.com/bank2ynab/bank2ynab | 280 | Python | 2025-11-06 | Convert bank statements to YNAB format |

---

**YNAB Browser Extension & Tools:**

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **toolkit-for-ynab** | https://github.com/toolkit-for-ynab/toolkit-for-ynab | 1,497 | TypeScript | 2025-11-05 | Browser extension with enhancements |
| **awesome-ynab** | https://github.com/scottrobertson/awesome-ynab | 289 | - | 2025-11-04 | Curated list of YNAB projects |
| **ynab-sdk-js** | https://github.com/ynab/ynab-sdk-js | 229 | TypeScript | 2025-11-07 | Official JavaScript SDK |

---

### 🟡 Other Notable Self-Hosted Options

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **moneynote** | https://github.com/getmoneynote/moneynote-api | 1,323 | Java | 2025-11-03 | Personal Finance Tracker |
| **WYGIWYH** | https://github.com/eitchtee/WYGIWYH | 507 | Python | 2025-11-06 | Simple but powerful tracker |
| **hisabi** | https://github.com/hisabi-app/hisabi | 402 | PHP | 2025-10-30 | SMS parser + finance |
| **flow** | https://github.com/flow-mn/flow | 322 | Dart | 2025-11-06 | Flutter-based finance app |
| **monetr** | https://github.com/monetr/monetr | 475 | Go | 2025-11-06 | Budgeting with Plaid support |

---

## 5. Finance Data & Import Tools

### Plaid Integration Tools (Bank Sync)

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **build-your-own-mint** | https://github.com/yyx990803/build-your-own-mint | 2,527 | HTML | 2025-11-04 | Finance analytics with Plaid |
| **mintable** | https://github.com/kevinschaich/mintable | 1,598 | TypeScript | 2025-11-05 | Automate finances with Plaid |
| **plaid/quickstart** | https://github.com/plaid/quickstart | 659 | TypeScript | 2025-11-06 | Official Plaid quickstart |
| **plaid/pattern** | https://github.com/plaid/pattern | 499 | TypeScript | 2025-11-02 | Complete Plaid integration example |

---

### SimpleFIN Tools (Alternative to Plaid)

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **simplefin.github.com** | https://github.com/simplefin/simplefin.github.com | 12 | HTML | 2025-10-27 | SimpleFIN spec & docs |
| **actual-simplefin-sync** | https://github.com/lancepick/actual-simplefin-sync | 13 | JavaScript | 2024-12-26 | Actual Budget + SimpleFIN |
| **bursar** | https://github.com/avirut/bursar | 30 | Python | 2025-11-01 | SimpleFIN + Google Sheets |
| **sfin2ledger** | https://github.com/simplefin/sfin2ledger | 12 | Python | 2025-09-03 | Convert to Ledger format |
| **simplefin-cli** | https://github.com/jeeftor/simplefin-cli | 11 | Go | 2025-07-31 | CLI tool for SimpleFIN |

---

### OFX Parsers (Bank File Format)

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **ofx-parser** | https://github.com/aasmith/ofx-parser | 59 | Ruby | 2025-01-28 | Ruby OFX parser |
| **ofex** | https://github.com/jjcarstens/ofex | 9 | Elixir | 2024-01-08 | Elixir OFX parser |

---

### Data Integration Platforms

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **venice** | https://github.com/useVenice/venice | 147 | TypeScript | 2025-09-21 | Drop-in connectors for financial data |

---

## 6. Transaction Categorization & AI Tools

### AI-Powered Categorization

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **bank-statement-analysis** | https://github.com/gbourniq/bank-statement-analysis | 67 | Python | 2025-10-06 | Flask app with OCR & ML |
| **ffiiitc** | https://github.com/akopulko/ffiiitc | 59 | Go | 2025-10-27 | Firefly III Transactions Classifier |
| **flowinance** | https://github.com/manuelalferez/flowinance | 32 | TypeScript | 2025-08-25 | Ultra-fast with auto-categorization |
| **TransactAI** | https://github.com/namanj2003/TransactAI | 1 | Python | 2025-10-25 | AI using DistilBERT |

---

## 7. Additional Notable Projects

### Accounting & ERP

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **erpsaas** | https://github.com/andrewdwallo/erpsaas | 1,310 | PHP | 2025-11-06 | Laravel/Filament accounting platform |

---

### Budget & Expense Apps

| Name | URL | Stars | Language | Updated | Purpose |
|------|-----|-------|----------|---------|---------|
| **envelope-budgeting-test** | https://github.com/vincanger/envelope-budgeting-test | 106 | TypeScript | 2025-10-30 | Full-stack budgeting |
| **guitos** | https://github.com/rare-magma/guitos | 89 | TypeScript | 2025-11-02 | Personal budgeting app |

---

## Quick Reference

### By Stars (Top 15)

1. **maybe-finance/maybe** - 53,354 ⭐ (Ruby)
2. **firefly-iii** - 21,379 ⭐ (PHP)
3. **toolkit-for-ynab** - 1,497 ⭐ (TypeScript)
4. **financial-freedom** - 2,724 ⭐ (Vue)
5. **moneynote** - 1,323 ⭐ (Java)
6. **erpsaas** - 1,310 ⭐ (PHP)
7. **mintable** - 1,598 ⭐ (TypeScript)
8. **build-your-own-mint** - 2,527 ⭐ (HTML)
9. **actual-ai** - 350 ⭐ (TypeScript)
10. **firefly-pico** - 746 ⭐ (Vue)
11. **sure (we-promise)** - 728 ⭐ (Ruby)
12. **budgetzero** - 650 ⭐ (Vue)
13. **waterfly-iii** - 576 ⭐ (Dart)
14. **monetr** - 475 ⭐ (Go)
15. **WYGIWYH** - 507 ⭐ (Python)

---

### By Language

**Ruby** (Maybe & Friends)
- maybe-finance/maybe (53,354)
- we-promise/sure (728)
- ofx-parser (59)

**PHP** (Firefly & ERP)
- firefly-iii (21,379)
- erpsaas (1,310)
- hisabi (402)

**TypeScript** (Web-first)
- toolkit-for-ynab (1,497)
- mintable (1,598)
- firefly-plaid-connector (137)

**Python** (Data Science)
- bank2ynab (280)
- WYGIWYH (507)
- bursar (30)

**Go** (Performance)
- monetr (475)
- ffiiitc (59)
- simplefin-cli (11)

**Dart** (Flutter Mobile)
- waterfly-iii (576)
- flow (322)

---

### By Category (Top 3 in Each)

**Official Projects:**
1. maybe-finance/maybe (53,354)
2. firefly-iii (21,379)
3. financial-freedom (2,724)

**Browser Extensions:**
1. toolkit-for-ynab (1,497)
2. firefly-iii-ai-categorize (202)
3. powens-maybe-finance-connector (2)

**Mobile Apps:**
1. abacus (742) - iOS for Firefly
2. waterfly-iii (576) - Android for Firefly
3. BearTS/actual-budget-app (170) - iOS for Actual

**Integrations:**
1. firefly-pico (746) - Web companion
2. data-importer (665) - Firefly import
3. plaid/quickstart (659) - Plaid example

**Bank Sync:**
1. build-your-own-mint (2,527) - Plaid
2. mintable (1,598) - Plaid
3. monetr (475) - Plaid + budgeting

**AI/ML:**
1. actual-ai (350) - Categorization
2. bank-statement-analysis (67) - OCR + ML
3. TransactAI (1) - DistilBERT

---

### By Activity Level

**Very Active (Updated Last Week):**
- maybe-finance/maybe
- firefly-iii
- sure (we-promise)
- toolkit-for-ynab
- And 50+ others listed above

**Moderately Active (Updated Last Month):**
- Various community projects
- Some specialized tools

**Stable/Mature (Occasional Updates):**
- Some older tools that work well but aren't actively developed

---

## Recommendations

### For Maybe Users
- ✅ **SimpleFIN Integration:** Connect to banking without Plaid
- ✅ **Home Assistant:** Monitor finances from your smart home
- ✅ **Helm Chart:** If deploying on Kubernetes
- ✅ **Firefly III:** Alternative with larger ecosystem if you outgrow Maybe

### For Firefly III Users
- ✅ **firefly-pico:** Quick transaction entry
- ✅ **abacus:** iOS companion app
- ✅ **firefly-iii-ai-categorize:** AI categorization

### For Budget Tracking
- ✅ **actual-ai:** Best categorization AI
- ✅ **toolkit-for-ynab:** Best YNAB enhancements
- ✅ **mintable:** Best Plaid integration example

### For Bank Sync
- ✅ **Plaid:** Most comprehensive (25K+ institutions)
- ✅ **SimpleFIN:** Privacy-focused alternative
- ✅ **OFX:** Export from banks directly

### For Developers
- ✅ **ynab-sdk-js:** Official SDK
- ✅ **plaid/quickstart:** Bank integration starting point
- ✅ **maybe-finance/maybe:** Best open-source finance app

---

## Additional Resources

**Documentation Links:**
- [Maybe Finance GitHub](https://github.com/maybe-finance/maybe)
- [Firefly III Documentation](https://docs.firefly-iii.org/)
- [Actual Budget](https://actualbudget.com/)
- [SimpleFIN Standard](https://github.com/simplefin/simplefin.github.com)
- [Plaid Documentation](https://plaid.com/docs/)

**Community:**
- Maybe Finance Discussions: GitHub Issues & Discussions
- Firefly III Community: Very active
- Self-hosted Finance: r/selfhosted, r/personalfinance

---

## Notes

- ⭐ = Highly recommended / Most active
- 🔴🔵🟢🟡 = Color coding by ecosystem
- Stars reflect GitHub popularity as of Nov 7, 2025
- Links verified as of Nov 7, 2025
- "Community Fork" notation indicates independent projects not officially affiliated

---

**Last Verified:** November 7, 2025
**Maintainer:** This ecosystem guide
**Contributions:** Help keep this list updated by finding new projects!

---

## How to Use This Guide

1. **New to personal finance apps?** Start with "Alternative Personal Finance Apps" section
2. **Want bank sync?** Check "Finance Data & Import Tools" for Plaid/SimpleFIN
3. **Using Maybe?** Look at "Integrations & Tools" and "Community Forks"
4. **Need AI help?** See "Transaction Categorization & AI Tools"
5. **Building your own?** Check "Additional Notable Projects" for inspiration

---

Happy exploring! 🚀

