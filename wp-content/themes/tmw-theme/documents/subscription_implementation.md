# Dynamic Subscription System Implementation

## Status: COMPLETED
## Last Updated: 2026-01-06

## Files Updated:
1. [x] inc/admin-settings.php - Complete rewrite for dynamic tiers/limits
2. [x] inc/subscription.php - Updated to use dynamic data + GarageMinder API functions

## New Data Structure:

### Option: tmw_tiers
```php
[
    'free' => ['name' => 'Free', 'swpm_level_id' => 1, 'is_free' => true, 'order' => 1, 'color' => '#6b7280'],
    'paid' => ['name' => 'Paid', 'swpm_level_id' => 2, 'is_free' => false, 'order' => 2, 'color' => '#3b82f6'],
    'fleet' => ['name' => 'Fleet', 'swpm_level_id' => 3, 'is_free' => false, 'order' => 3, 'color' => '#8b5cf6'],
]
```

### Option: tmw_limit_definitions
```php
[
    'max_vehicles' => ['label' => 'Max Vehicles', 'type' => 'number', 'description' => '...'],
    'max_entries' => ['label' => 'Max Entries', 'type' => 'number', 'description' => '...'],
    'recalls_enabled' => ['label' => 'Recall Alerts', 'type' => 'boolean', 'description' => '...'],
    'export_level' => ['label' => 'Export Level', 'type' => 'select', 'options' => ['none','basic','advanced'], 'description' => '...'],
]
```

### Option: tmw_tier_values
```php
[
    'free' => ['max_vehicles' => 2, 'max_entries' => 50, 'recalls_enabled' => false, ...],
    'paid' => ['max_vehicles' => 10, 'max_entries' => -1, 'recalls_enabled' => true, ...],
    'fleet' => ['max_vehicles' => -1, 'max_entries' => -1, 'recalls_enabled' => true, ...],
]
```

## GarageMinder API Functions (in subscription.php):
- gm_has_subscription($user_id, $tier_id) - Check by SWPM level ID
- gm_is_tier($user_id, $tier_slug) - Check by slug
- gm_has_tier_or_higher($user_id, $min_tier_slug) - Check tier level
- gm_get_user_tier_id($user_id) - Get user's SWPM level ID
- gm_get_user_tier($user_id) - Get user's tier slug  
- gm_get_limit($user_id, $limit_key) - Get limit value
- gm_can_add($user_id, $limit_key, $current_count) - Check if can add more
- gm_has_feature($user_id, $limit_key) - Boolean feature check
- gm_get_user_limits($user_id) - Get all limits for user's tier
- gm_format_limit($value, $type) - Format limit for display

## Admin UI (4 Tabs):
- Tab 1: General - App URL, theme, redirects, membership plugin
- Tab 2: Manage Tiers - Add/edit/delete tiers with SWPM level ID mapping
- Tab 3: Manage Limits - Add/edit/delete limit definitions (number/boolean/select)
- Tab 4: Tier Values - Matrix to set all limit values per tier

## Usage Examples in GarageMinder:

```php
// Check if user has Paid subscription (tier ID 2)
if (gm_has_subscription($userId, 2)) {
    // Show paid features
}

// Check by tier slug
if (gm_is_tier($userId, 'fleet')) {
    // Show fleet features
}

// Check if user can add another vehicle
$vehicleCount = count($userVehicles);
if (gm_can_add($userId, 'max_vehicles', $vehicleCount)) {
    // Allow adding
} else {
    echo "Upgrade to add more vehicles";
}

// Check feature availability
if (gm_has_feature($userId, 'recalls_enabled')) {
    // Show recall button
}

// Get actual limit value
$maxVehicles = gm_get_limit($userId, 'max_vehicles');
// Returns: 2, 10, or -1 (unlimited)

// Works with custom limits added via admin
if (gm_has_feature($userId, 'custom_reports')) {
    // Show custom reports
}
```
