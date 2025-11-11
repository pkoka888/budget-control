# Export CSV with Running Balance

## Overview

You can now export your CSV with a calculated **running balance** (Zůstatek) column for each transaction. This new feature creates a copy of your original CSV with an additional "Zůstatek" column that shows the cumulative balance after each transaction.

## How to Use

### Quick Command

```bash
docker-compose -f docker-compose.dev.yml -p maybe-dev exec web rake import_csv:export_with_balance
```

### What It Does

1. **Reads** your original CSV file (`Finance - Prijem-Vydej.csv`)
2. **Calculates** cumulative balance for each row:
   - Formula: `Balance = Σ(Příchozí - Odchozí)`
   - Incoming transactions add to balance
   - Outgoing transactions subtract from balance
3. **Exports** a new CSV file named `Finance - Prijem-Vydej-with-balance.csv`
4. **Adds** a new column called "Zůstatek" (Balance) with the running balance

## Example Output

| Datum zaúčtování | Odchozí částka | Příchozí částka | ... | Zůstatek |
|-----------------|---|---|---|---|
| 04.11.2025 | 524.20 | | ... | -524.2 |
| 04.11.2025 | 109.99 | | ... | -634.19 |
| 04.11.2025 | 99.40 | | ... | -733.59 |
| 02.11.2025 | 75.21 | | ... | -808.8 |

The balance starts at 0 and updates cumulatively after each transaction.

## Key Features

- ✅ **Preserves all original columns** - Nothing is deleted or modified
- ✅ **Adds new "Zůstatek" column** - Shows cumulative balance
- ✅ **Handles Czech decimal format** - Properly converts `.` and `,` decimals
- ✅ **Fast processing** - Handles 1600+ rows in seconds
- ✅ **UTF-8 encoding** - Preserves Czech characters
- ✅ **Skips empty rows** - Rows with no amounts are left with empty balance

## Output File

**Location:** `C:\ClaudeProjects\budget-control\Finance - Prijem-Vydej-with-balance.csv`

**Contents:**
- All original columns from your bank CSV
- New column "Zůstatek" at the end
- Running balance calculated from first to last transaction

## Example Use Cases

### 1. Visual Analysis
Import into Excel and create charts showing:
- Account balance over time
- Cumulative spending
- Savings trends

### 2. Backup & Archive
Keep a copy with balance calculations for records

### 3. Data Validation
Compare final balance in CSV with your bank statement

## How Balance is Calculated

### Formula
```
Zůstatek = Σ(Příchozí - Odchozí)
```

### Example Calculation

Starting balance: 0 CZK

| Date | Outgoing | Incoming | Calculation | Balance |
|------|----------|----------|---|---|
| 04.11 | 524.20 | - | 0 - 524.20 | -524.20 |
| 04.11 | 109.99 | - | -524.20 - 109.99 | -634.19 |
| 05.11 | - | 5000.00 | -634.19 + 5000.00 | 4365.81 |

## Latest Run Results

```
✅ Export Complete!
Files processed: 1637 rows
Final balance: -232150.29 CZK

Columns in CSV:
- Datum zaúčtování (Date)
- Název protiúčtu (Counterparty)
- Odchozí částka (Outgoing)
- Příchozí částka (Incoming)
- ... (39 other bank columns)
- Zůstatek (NEW - Running Balance)
```

## Available Rake Tasks

```bash
# Calculate opening balance from all transactions
rake import_csv:calculate_balance

# Export CSV with running balance for each transaction
rake import_csv:export_with_balance

# Import transactions from CSV
rake import_csv:transactions

# Import loans from CSV
rake import_csv:loans

# All imports at once
rake import_csv:all
```

## Troubleshooting

### File not found error
- Make sure `Finance - Prijem-Vydej.csv` is in your `budget-control` root directory
- Check filename spelling and capitalization

### Balance values seem wrong
- Verify the original CSV has correct `Odchozí` and `Příchozí` columns
- Check that amounts aren't in unexpected format
- Review the console output for warnings during processing

### CSV is corrupted
- Make a backup of your original CSV first
- Check that your original CSV can be opened in Excel
- Verify encoding is UTF-8

## Next Steps

1. **Run the export task** to generate your CSV with balance
2. **Open in Excel** to view and analyze the data
3. **Create charts** to visualize your account balance over time
4. **Compare with your bank** to verify the numbers are correct

---

**Created:** November 7, 2025
**Status:** Working ✅
**File:** C:\ClaudeProjects\budget-control\Finance - Prijem-Vydej-with-balance.csv
