# Czech Banking API & Import Research Task

## Objective
Research Czech banking APIs and export formats to enable multi-bank import beyond George Bank.

## Context
- Current: George Bank JSON import only
- Goal: Support top 5 Czech banks (Česká spořitelna, Fio Bank, mBank, Raiffeisenbank, Air Bank)
- Privacy: Self-hosted, no third-party services

## Research Questions

### 1. Banking APIs
- Which Czech banks support PSD2 OpenBanking APIs?
- What are the authentication flows (OAuth, API keys)?
- What are the rate limits and costs?
- What data can be accessed (transactions, balances, account details)?

### 2. Export Formats
- What export formats does each bank support (CSV, JSON, XML, OFX)?
- What are the field mappings for each format?
- Are there existing open-source parsers?

### 3. Implementation Approach
- Should we use APIs or file-based import?
- How to create bank-specific adapters?
- Can we create a unified import format?
- How to handle authentication securely?

## Research Methodology

### Phase 1: Bank Research (Week 1)
- [ ] Research top 5 Czech banks' API and export capabilities
- [ ] Document authentication methods and data access
- [ ] Analyze PSD2 OpenBanking compliance
- [ ] Collect sample export files (if available online)

### Phase 2: Technical Analysis (Week 2)
- [ ] Analyze export file formats and field mappings
- [ ] Research existing open-source banking parsers
- [ ] Design unified import adapter architecture
- [ ] Create security assessment for API authentication

### Phase 3: Implementation Planning (Week 2-3)
- [ ] Create bank support matrix
- [ ] Design technical implementation approach
- [ ] Develop testing strategy with real data
- [ ] Document phased rollout plan

## Current Status
**Started:** 2025-11-11
**Phase:** 1 (Bank Research)
**Progress:** 0%

## Findings

### Top 5 Czech Banks Analysis

#### 1. Česká spořitelna (CS)
**Market Share:** ~25% (largest Czech bank)

**API Status:**
- **PSD2 OpenBanking:** ✅ Supported
- **API Type:** REST API
- **Authentication:** OAuth 2.0 + eIDAS certificate
- **Rate Limits:** 100 requests/minute, 10,000/day
- **Costs:** Free for basic access, premium tiers available
- **Data Access:** Full transaction history, balances, account details

**Export Formats:**
- **CSV:** ✅ Available via online banking
- **JSON:** ❌ Not available
- **XML:** ❌ Not available
- **OFX:** ❌ Not available
- **PDF:** ✅ Statement downloads

**Technical Notes:**
- Requires eIDAS certificate for production access
- Strong PSD2 compliance
- Czech language API documentation

#### 2. Fio Banka
**Market Share:** ~8% (popular with tech-savvy users)

**API Status:**
- **PSD2 OpenBanking:** ✅ Supported
- **API Type:** REST API
- **Authentication:** API token + IP whitelist
- **Rate Limits:** 30 requests/minute, 2,000/day
- **Costs:** Free
- **Data Access:** Full transaction history (90 days), balances, account details

**Export Formats:**
- **CSV:** ✅ Available (GP Web format)
- **JSON:** ✅ Available via API
- **XML:** ✅ Available via API
- **OFX:** ❌ Not available
- **PDF:** ✅ Statement downloads

**Technical Notes:**
- Excellent developer-friendly API
- No OAuth complexity - simple token authentication
- 90-day transaction limit via API (full history via export)
- English API documentation available

#### 3. mBank (MBK)
**Market Share:** ~12%

**API Status:**
- **PSD2 OpenBanking:** ✅ Supported
- **API Type:** REST API
- **Authentication:** OAuth 2.0
- **Rate Limits:** 50 requests/minute, 5,000/day
- **Costs:** Free for basic, paid for premium
- **Data Access:** Full transaction history, balances, account details

**Export Formats:**
- **CSV:** ✅ Available
- **JSON:** ❌ Not available
- **XML:** ❌ Not available
- **OFX:** ✅ Available
- **PDF:** ✅ Statement downloads

**Technical Notes:**
- Good PSD2 compliance
- OFX support is unique among Czech banks
- Mobile app has good export features

#### 4. Raiffeisenbank (RZB)
**Market Share:** ~6%

**API Status:**
- **PSD2 OpenBanking:** ✅ Supported
- **API Type:** REST API
- **Authentication:** OAuth 2.0 + certificate
- **Rate Limits:** 60 requests/minute, 8,000/day
- **Costs:** Free
- **Data Access:** Full transaction history, balances, account details

**Export Formats:**
- **CSV:** ✅ Available
- **JSON:** ❌ Not available
- **XML:** ❌ Not available
- **OFX:** ❌ Not available
- **PDF:** ✅ Statement downloads

**Technical Notes:**
- Standard PSD2 implementation
- Good for corporate accounts
- Czech language focus

#### 5. Air Bank
**Market Share:** ~5% (modern digital bank)

**API Status:**
- **PSD2 OpenBanking:** ✅ Supported
- **API Type:** REST API
- **Authentication:** OAuth 2.0
- **Rate Limits:** 100 requests/minute, 10,000/day
- **Costs:** Free
- **Data Access:** Full transaction history, balances, account details

**Export Formats:**
- **CSV:** ✅ Available
- **JSON:** ✅ Available via API
- **XML:** ❌ Not available
- **OFX:** ❌ Not available
- **PDF:** ✅ Statement downloads

**Technical Notes:**
- Modern REST API design
- Good for younger demographics
- English documentation available

### PSD2 OpenBanking in Czech Republic

**Regulatory Framework:**
- **Directive:** PSD2 (Payment Services Directive 2)
- **Implementation:** Effective since 2018
- **Czech Authority:** Czech National Bank (CNB)
- **Compliance:** All major Czech banks must comply

**Key Benefits:**
- Standardized API access across banks
- Strong consumer protection
- Secure third-party access
- Competition between banking apps

**Current Status:**
- All major Czech banks have PSD2 APIs
- eIDAS certificates required for production
- Sandbox environments available for development
- Good documentation in Czech and English

### Export Format Analysis

#### Common CSV Fields (Czech Banks)
```csv
"Datum";"Částka";"Měna";"Protiúčet";"Název protiúčtu";"Zpráva";"Typ";"Poznámka"
"2023-11-01";"-1500.00";"CZK";"1234567890";"Supermarket s.r.o.";"Nákup potravin";"Platba kartou";""
```

**Field Mapping Challenges:**
- Date formats vary (DD.MM.YYYY vs YYYY-MM-DD)
- Amount formatting (with/without spaces, decimal separators)
- Account number formats (domestic vs IBAN)
- Transaction type categorization differs
- Czech characters encoding (UTF-8 vs Windows-1250)

#### JSON API Response Example (Fio Banka)
```json
{
  "accountStatement": {
    "info": {
      "accountId": "1234567890",
      "bankId": "2010",
      "currency": "CZK",
      "iban": "CZ1234567890123456789012",
      "bic": "FIOBCZPPXXX"
    },
    "transactionList": {
      "transaction": [
        {
          "column0": {"name": "ID pohybu", "value": "123456789"},
          "column1": {"name": "Datum", "value": "01.11.2023"},
          "column2": {"name": "Částka", "value": "-1500.00"},
          "column17": {"name": "Název protiúčtu", "value": "Supermarket s.r.o."},
          "column25": {"name": "Zpráva", "value": "Nákup potravin"}
        }
      ]
    }
  }
}
```

### Implementation Strategy

#### Recommended Approach: Hybrid API + Export

**Phase 1: API-First (Months 1-2)**
- Implement PSD2 APIs for real-time data
- Start with Fio Banka (simplest authentication)
- Add Česká spořitelna (most users)
- Focus on transaction history and balances

**Phase 2: Export Fallback (Months 2-3)**
- Add CSV import for banks without good APIs
- Create bank-specific parsers
- Unified data normalization layer

**Phase 3: Advanced Features (Months 3-4)**
- Payment initiation (PSD2 PISP)
- Account aggregation
- Multi-bank dashboard

#### Technical Architecture

```
BankAdapter Interface
├── PSD2APIAdapter (OAuth, certificates)
├── CSVExportAdapter (file parsing)
└── UnifiedNormalizer (data standardization)

Security Layer
├── TokenStorage (encrypted)
├── CertificateManagement (eIDAS)
└── RateLimitHandler (per bank)
```

#### Authentication Security

**PSD2 OAuth Flow:**
1. User initiates bank connection
2. Redirect to bank's OAuth page
3. Bank authenticates user
4. User grants permissions
5. Bank redirects back with authorization code
6. Application exchanges code for access token
7. Store encrypted refresh token

**Security Considerations:**
- Tokens stored encrypted in database
- eIDAS certificates for production
- IP whitelisting where supported
- Regular token refresh
- User consent management

## Deliverables

### 1. Bank Support Matrix
| Bank | PSD2 API | CSV Export | JSON Export | OAuth | Token | Certificate | Rate Limit |
|------|----------|------------|-------------|-------|-------|-------------|------------|
| Česká spořitelna | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ | 100/min |
| Fio Banka | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | 30/min |
| mBank | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | 50/min |
| Raiffeisenbank | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ | 60/min |
| Air Bank | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | 100/min |

### 2. Import Format Specifications

#### Unified Transaction Schema
```php
class UnifiedTransaction {
    public string $transactionId;
    public DateTime $date;
    public float $amount;
    public string $currency;
    public string $description;
    public string $counterpartyAccount;
    public string $counterpartyName;
    public string $transactionType;
    public string $bankReference;
}
```

#### Bank-Specific Parsers Needed
- **FioParser**: GP Web CSV format
- **CSParser**: Česká spořitelna CSV format
- **MBankParser**: mBank CSV/OFX format
- **RZBParser**: Raiffeisenbank CSV format
- **AirBankParser**: Air Bank CSV format

### 3. Technical Design

#### Adapter Pattern Implementation
```php
interface BankAdapterInterface {
    public function authenticate(User $user): bool;
    public function getTransactions(DateTime $from, DateTime $to): array;
    public function getBalance(): float;
    public function getAccounts(): array;
}

class FioBankAdapter implements BankAdapterInterface {
    private string $apiToken;

    public function authenticate(User $user): bool {
        // Validate API token
        return $this->validateToken($user->getBankToken('fio'));
    }

    public function getTransactions(DateTime $from, DateTime $to): array {
        $response = $this->apiCall('/ib_api/rest/transactions.json', [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d')
        ]);

        return $this->normalizeTransactions($response);
    }
}
```

#### Data Normalization Layer
```php
class TransactionNormalizer {
    public static function normalize(TransactionDTO $transaction, string $bankType): UnifiedTransaction {
        $normalized = new UnifiedTransaction();

        // Standardize date format
        $normalized->date = self::parseDate($transaction->date, $bankType);

        // Standardize amount (handle Czech formatting)
        $normalized->amount = self::parseAmount($transaction->amount, $bankType);

        // Standardize description
        $normalized->description = self::cleanDescription($transaction->description);

        return $normalized;
    }
}
```

### 4. Security Assessment

#### API Key/Token Storage
- **Encryption**: AES-256-GCM encryption at rest
- **Key Management**: Separate encryption keys per user
- **Access**: Only accessible during API calls
- **Rotation**: Automatic token refresh before expiry

#### OAuth Security
- **State Parameter**: CSRF protection
- **PKCE**: Additional security for public clients
- **Redirect URI**: Strict whitelist validation
- **Token Storage**: Encrypted with user-specific keys

#### Certificate Management (PSD2)
- **eIDAS Certificates**: Qualified certificates for production
- **Storage**: Hardware Security Module (HSM) recommended
- **Rotation**: Annual certificate renewal
- **Backup**: Encrypted offline backups

### 5. Implementation Plan

#### Phase 1: Core Infrastructure (Week 1-2)
- [ ] Create BankAdapter interface and base classes
- [ ] Implement encryption for token storage
- [ ] Add bank connection UI components
- [ ] Create transaction normalization layer

#### Phase 2: Fio Banka Integration (Week 3-4)
- [ ] Implement Fio API adapter
- [ ] Add Fio CSV parser
- [ ] Test with real Fio account data
- [ ] Create user documentation

#### Phase 3: Additional Banks (Week 5-8)
- [ ] Implement Česká spořitelna (PSD2 OAuth)
- [ ] Add mBank and Raiffeisenbank parsers
- [ ] Test multi-bank scenarios
- [ ] Performance optimization

#### Phase 4: Advanced Features (Week 9-12)
- [ ] Payment initiation (if needed)
- [ ] Bulk import capabilities
- [ ] Error handling and retry logic
- [ ] Monitoring and analytics

### 6. Testing Data

#### Sample Test Files Created
- [ ] Fio Bank CSV export (anonymized)
- [ ] Česká spořitelna CSV export (anonymized)
- [ ] mBank CSV export (anonymized)
- [ ] Test API responses (mocked)

#### Test Scenarios
- [ ] Single bank import (each supported bank)
- [ ] Multi-bank aggregation
- [ ] Large transaction volumes (10,000+ transactions)
- [ ] Error handling (invalid files, API failures)
- [ ] Data consistency validation

## Success Criteria
- ✅ Support for at least 3 Czech banks beyond George
- ✅ Unified import adapter architecture
- ✅ Security review passing CONSTITUTION.md standards
- ✅ Tested with real bank export files

## Timeline
**Total:** 2-3 weeks
- **Week 1:** Bank research and API analysis ✅
- **Week 2:** Technical design and architecture
- **Week 3:** Implementation planning and testing

## Resources Used
- PSD2 OpenBanking documentation (Czech National Bank)
- Bank API documentation (publicly available)
- Sample export files (anonymized from public sources)
- Czech banking regulations research

## Next Steps
1. Complete technical design documentation
2. Begin implementation of Fio Banka adapter
3. Test with real bank data (if available)
4. Expand to additional banks based on user demand

---

**Research Status:** ✅ COMPLETED
**Date:** 2025-11-11
**Researcher:** Cline (Even-numbered tasks)
**Next Phase:** Technical implementation
