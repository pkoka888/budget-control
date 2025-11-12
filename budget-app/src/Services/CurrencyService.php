<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use BudgetApp\Config;

/**
 * Currency Service
 *
 * Handles multi-currency support, exchange rates, and conversions
 */
class CurrencyService {
    private Database $db;
    private Config $config;
    private string $apiProvider;
    private string $apiKey;
    private int $cacheDuration = 3600; // 1 hour

    public function __construct(Database $db, Config $config) {
        $this->db = $db;
        $this->config = $config;
        $this->apiProvider = $_ENV['EXCHANGE_RATE_PROVIDER'] ?? 'exchangerate-api';
        $this->apiKey = $_ENV['EXCHANGE_RATE_API_KEY'] ?? '';
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $from, string $to, ?string $date = null): float {
        // Same currency = 1.0
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? date('Y-m-d');

        // Check cache first
        $cached = $this->getCachedRate($from, $to, $date);
        if ($cached !== null) {
            return $cached;
        }

        // Fetch from API
        $rate = $this->fetchRateFromApi($from, $to);

        // Cache the rate
        $this->cacheRate($from, $to, $rate, $date);

        return $rate;
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to, ?string $date = null): float {
        $rate = $this->getExchangeRate($from, $to, $date);
        return round($amount * $rate, 2);
    }

    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies(): array {
        return $this->db->query(
            "SELECT code, name, symbol, decimal_places
             FROM supported_currencies
             WHERE is_active = 1
             ORDER BY code"
        );
    }

    /**
     * Get user's currency preferences
     */
    public function getUserPreferences(int $userId): array {
        $result = $this->db->query(
            "SELECT * FROM user_currency_preferences WHERE user_id = ?",
            [$userId]
        );

        if (empty($result)) {
            // Create default preferences
            return [
                'base_currency' => 'CZK',
                'display_currencies' => json_encode(['CZK', 'EUR', 'USD']),
                'auto_convert' => 1
            ];
        }

        $prefs = $result[0];
        $prefs['display_currencies'] = json_decode($prefs['display_currencies'] ?? '[]', true);
        return $prefs;
    }

    /**
     * Set user's currency preferences
     */
    public function setUserPreferences(int $userId, string $baseCurrency, array $displayCurrencies, bool $autoConvert = true): bool {
        $existing = $this->db->query(
            "SELECT id FROM user_currency_preferences WHERE user_id = ?",
            [$userId]
        );

        if (empty($existing)) {
            $this->db->query(
                "INSERT INTO user_currency_preferences
                 (user_id, base_currency, display_currencies, auto_convert)
                 VALUES (?, ?, ?, ?)",
                [$userId, $baseCurrency, json_encode($displayCurrencies), $autoConvert ? 1 : 0]
            );
        } else {
            $this->db->query(
                "UPDATE user_currency_preferences
                 SET base_currency = ?, display_currencies = ?, auto_convert = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = ?",
                [$baseCurrency, json_encode($displayCurrencies), $autoConvert ? 1 : 0, $userId]
            );
        }

        return true;
    }

    /**
     * Update all exchange rates from API
     */
    public function updateAllRates(string $baseCurrency = 'EUR'): int {
        $currencies = $this->getSupportedCurrencies();
        $updated = 0;

        foreach ($currencies as $currency) {
            if ($currency['code'] === $baseCurrency) continue;

            try {
                $rate = $this->fetchRateFromApi($baseCurrency, $currency['code']);
                $this->cacheRate($baseCurrency, $currency['code'], $rate);
                $updated++;
            } catch (\Exception $e) {
                error_log("Failed to update rate for {$currency['code']}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Get historical rates for a currency pair
     */
    public function getHistoricalRates(string $from, string $to, string $startDate, string $endDate): array {
        return $this->db->query(
            "SELECT date, rate FROM exchange_rate_history
             WHERE from_currency = ? AND to_currency = ?
             AND date BETWEEN ? AND ?
             ORDER BY date",
            [$from, $to, $startDate, $endDate]
        );
    }

    /**
     * Format amount with currency symbol
     */
    public function formatAmount(float $amount, string $currencyCode): string {
        $currency = $this->db->query(
            "SELECT symbol, decimal_places FROM supported_currencies WHERE code = ?",
            [$currencyCode]
        );

        if (empty($currency)) {
            return number_format($amount, 2) . ' ' . $currencyCode;
        }

        $symbol = $currency[0]['symbol'];
        $decimals = $currency[0]['decimal_places'] ?? 2;

        return $symbol . ' ' . number_format($amount, $decimals);
    }

    /**
     * Convert transaction to base currency
     */
    public function convertTransactionToBase(array $transaction, string $baseCurrency): array {
        if ($transaction['currency'] === $baseCurrency) {
            $transaction['converted_amount'] = $transaction['amount'];
            return $transaction;
        }

        $transaction['converted_amount'] = $this->convert(
            $transaction['amount'],
            $transaction['currency'],
            $baseCurrency,
            $transaction['date'] ?? null
        );

        return $transaction;
    }

    /**
     * Calculate currency gain/loss
     */
    public function calculateExchangeGainLoss(float $originalAmount, string $originalCurrency, float $convertedAmount, string $convertedCurrency, string $purchaseDate): array {
        // Get rate at purchase
        $purchaseRate = $this->getExchangeRate($originalCurrency, $convertedCurrency, $purchaseDate);
        $expectedAmount = $originalAmount * $purchaseRate;

        // Get current rate
        $currentRate = $this->getExchangeRate($originalCurrency, $convertedCurrency);
        $currentValue = $originalAmount * $currentRate;

        $gainLoss = $currentValue - $expectedAmount;
        $gainLossPercentage = ($expectedAmount > 0) ? (($gainLoss / $expectedAmount) * 100) : 0;

        return [
            'original_amount' => $originalAmount,
            'original_currency' => $originalCurrency,
            'expected_amount' => round($expectedAmount, 2),
            'current_value' => round($currentValue, 2),
            'converted_currency' => $convertedCurrency,
            'gain_loss' => round($gainLoss, 2),
            'gain_loss_percentage' => round($gainLossPercentage, 2),
            'purchase_rate' => $purchaseRate,
            'current_rate' => $currentRate
        ];
    }

    // Private methods

    private function getCachedRate(string $from, string $to, string $date): ?float {
        $result = $this->db->query(
            "SELECT rate FROM exchange_rates
             WHERE from_currency = ? AND to_currency = ?
             AND DATE(fetched_at) = DATE(?)
             ORDER BY fetched_at DESC LIMIT 1",
            [$from, $to, $date]
        );

        return !empty($result) ? (float)$result[0]['rate'] : null;
    }

    private function cacheRate(string $from, string $to, float $rate, ?string $date = null): void {
        $date = $date ?? date('Y-m-d');

        $this->db->query(
            "INSERT INTO exchange_rates (from_currency, to_currency, rate, fetched_at, source)
             VALUES (?, ?, ?, ?, 'api')",
            [$from, $to, $rate, $date . ' ' . date('H:i:s')]
        );

        // Also save to history
        $this->db->query(
            "INSERT INTO exchange_rate_history (from_currency, to_currency, rate, date)
             VALUES (?, ?, ?, ?)",
            [$from, $to, $rate, $date]
        );
    }

    private function fetchRateFromApi(string $from, string $to): float {
        switch ($this->apiProvider) {
            case 'exchangerate-api':
                return $this->fetchFromExchangeRateApi($from, $to);
            case 'fixer':
                return $this->fetchFromFixer($from, $to);
            case 'currencyapi':
                return $this->fetchFromCurrencyApi($from, $to);
            default:
                throw new \Exception("Unknown exchange rate provider: {$this->apiProvider}");
        }
    }

    private function fetchFromExchangeRateApi(string $from, string $to): float {
        $url = "https://v6.exchangerate-api.com/v6/{$this->apiKey}/pair/{$from}/{$to}";

        $response = @file_get_contents($url);
        if ($response === false) {
            throw new \Exception("Failed to fetch exchange rate from API");
        }

        $data = json_decode($response, true);
        if (!isset($data['conversion_rate'])) {
            throw new \Exception("Invalid API response");
        }

        return (float)$data['conversion_rate'];
    }

    private function fetchFromFixer(string $from, string $to): float {
        $url = "http://data.fixer.io/api/latest?access_key={$this->apiKey}&base={$from}&symbols={$to}";

        $response = @file_get_contents($url);
        if ($response === false) {
            throw new \Exception("Failed to fetch exchange rate from Fixer");
        }

        $data = json_decode($response, true);
        if (!isset($data['rates'][$to])) {
            throw new \Exception("Invalid Fixer API response");
        }

        return (float)$data['rates'][$to];
    }

    private function fetchFromCurrencyApi(string $from, string $to): float {
        // CurrencyAPI.com implementation
        $url = "https://api.currencyapi.com/v3/latest?apikey={$this->apiKey}&base_currency={$from}&currencies={$to}";

        $response = @file_get_contents($url);
        if ($response === false) {
            throw new \Exception("Failed to fetch exchange rate from CurrencyAPI");
        }

        $data = json_decode($response, true);
        if (!isset($data['data'][$to]['value'])) {
            throw new \Exception("Invalid CurrencyAPI response");
        }

        return (float)$data['data'][$to]['value'];
    }
}
