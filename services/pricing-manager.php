<?php
/**
 * Pricing Manager
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class PricingManager {
    public static function calculateSellingPrice(float $providerCost): float {
        $markupPercent = (float)get_setting('default_profit_markup_percent', '35');
        $sellingPrice = $providerCost * (1 + ($markupPercent / 100));
        return round($sellingPrice, 2);
    }
}
