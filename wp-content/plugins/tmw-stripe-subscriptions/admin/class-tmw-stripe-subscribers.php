<?php
/**
 * Subscribers List Table
 *
 * Displays subscription records in WordPress admin.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class TMW_Stripe_Subscribers extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Subscriber', 'tmw-stripe-subscriptions'),
            'plural'   => __('Subscribers', 'tmw-stripe-subscriptions'),
            'ajax'     => false,
        ));
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'user'            => __('User', 'tmw-stripe-subscriptions'),
            'tier'            => __('Tier', 'tmw-stripe-subscriptions'),
            'status'          => __('Status', 'tmw-stripe-subscriptions'),
            'subscription_id' => __('Subscription ID', 'tmw-stripe-subscriptions'),
            'period_end'      => __('Period End', 'tmw-stripe-subscriptions'),
            'trial_used'      => __('Trial Used', 'tmw-stripe-subscriptions'),
            'created_at'      => __('Created', 'tmw-stripe-subscriptions'),
        );
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'tier'       => array('tier_slug', false),
            'status'     => array('status', false),
            'period_end' => array('current_period_end', false),
            'created_at' => array('created_at', true),
        );
    }

    /**
     * Prepare items
     */
    public function prepare_items() {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $per_page = 20;
        $current_page = $this->get_pagenum();

        // Column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(), // Hidden columns
            $this->get_sortable_columns(),
        );

        // Build query
        $where = array('1=1');
        $values = array();

        // Filter by tier
        if (!empty($_GET['tier'])) {
            $where[] = 'tier_slug = %s';
            $values[] = sanitize_key($_GET['tier']);
        }

        // Filter by status
        if (!empty($_GET['status'])) {
            $where[] = 'status = %s';
            $values[] = sanitize_key($_GET['status']);
        }

        // Search
        if (!empty($_GET['s'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%';
            $where[] = '(stripe_customer_id LIKE %s OR stripe_subscription_id LIKE %s)';
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode(' AND ', $where);

        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        if (!empty($values)) {
            $total_query = $wpdb->prepare($total_query, $values);
        }
        $total_items = $wpdb->get_var($total_query);

        // Order
        $orderby = !empty($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
        $order = !empty($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Whitelist orderby
        $allowed_orderby = array('tier_slug', 'status', 'current_period_end', 'created_at');
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'created_at';
        }

        // Get items
        $offset = ($current_page - 1) * $per_page;
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $values[] = $per_page;
        $values[] = $offset;

        $this->items = $wpdb->get_results($wpdb->prepare($query, $values));

        // Pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }

    /**
     * Get views (filter links)
     *
     * @return array
     */
    protected function get_views() {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $current_status = $_GET['status'] ?? '';
        $base_url = admin_url('admin.php?page=tmw-subscribers');

        // Get counts by status
        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table GROUP BY status",
            OBJECT_K
        );

        $total = 0;
        foreach ($counts as $c) {
            $total += $c->count;
        }

        $views = array();

        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url($base_url),
            empty($current_status) ? 'current' : '',
            __('All', 'tmw-stripe-subscriptions'),
            $total
        );

        $statuses = array(
            'active'   => __('Active', 'tmw-stripe-subscriptions'),
            'trialing' => __('Trialing', 'tmw-stripe-subscriptions'),
            'past_due' => __('Past Due', 'tmw-stripe-subscriptions'),
            'canceled' => __('Canceled', 'tmw-stripe-subscriptions'),
            'inactive' => __('Inactive', 'tmw-stripe-subscriptions'),
        );

        foreach ($statuses as $status => $label) {
            $count = isset($counts[$status]) ? $counts[$status]->count : 0;
            if ($count > 0) {
                $views[$status] = sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    esc_url(add_query_arg('status', $status, $base_url)),
                    $current_status === $status ? 'current' : '',
                    $label,
                    $count
                );
            }
        }

        return $views;
    }

    /**
     * Extra table nav (tier filter dropdown)
     *
     * @param string $which
     */
    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }

        $current_tier = $_GET['tier'] ?? '';
        $tiers = function_exists('tmw_get_tiers') ? tmw_get_tiers() : array();
        ?>
        <div class="alignleft actions">
            <select name="tier" id="filter-by-tier">
                <option value=""><?php _e('All Tiers', 'tmw-stripe-subscriptions'); ?></option>
                <?php foreach ($tiers as $slug => $tier) : ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($current_tier, $slug); ?>>
                        <?php echo esc_html($tier['name'] ?? ucfirst($slug)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'tmw-stripe-subscriptions'), '', 'filter_action', false); ?>
        </div>
        <?php
    }

    /**
     * Column: User
     *
     * @param object $item
     * @return string
     */
    protected function column_user($item) {
        $user = get_userdata($item->user_id);

        if (!$user) {
            return sprintf(
                '<em>%s</em> (ID: %d)',
                __('Deleted User', 'tmw-stripe-subscriptions'),
                $item->user_id
            );
        }

        $edit_link = get_edit_user_link($item->user_id);
        $avatar = get_avatar($item->user_id, 32);

        $output = sprintf(
            '<div style="display:flex;align-items:center;gap:8px;">
                %s
                <div>
                    <strong><a href="%s">%s</a></strong><br>
                    <small>%s</small>
                </div>
            </div>',
            $avatar,
            esc_url($edit_link),
            esc_html($user->display_name),
            esc_html($user->user_email)
        );

        return $output;
    }

    /**
     * Column: Tier
     *
     * @param object $item
     * @return string
     */
    protected function column_tier($item) {
        $tier_slug = $item->tier_slug;

        if (function_exists('tmw_get_tier_badge')) {
            return tmw_get_tier_badge($tier_slug);
        }

        if (function_exists('tmw_get_tier')) {
            $tier = tmw_get_tier($tier_slug);
            $name = $tier ? ($tier['name'] ?? ucfirst($tier_slug)) : ucfirst($tier_slug);
            $color = $tier ? ($tier['color'] ?? '#6b7280') : '#6b7280';

            return sprintf(
                '<span class="tmw-badge" style="background:%s;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">%s</span>',
                esc_attr($color),
                esc_html($name)
            );
        }

        return esc_html(ucfirst($tier_slug));
    }

    /**
     * Column: Status
     *
     * @param object $item
     * @return string
     */
    protected function column_status($item) {
        $status = $item->status;

        $colors = array(
            'active'   => '#22c55e',
            'trialing' => '#3b82f6',
            'past_due' => '#f59e0b',
            'canceled' => '#ef4444',
            'inactive' => '#6b7280',
            'none'     => '#9ca3af',
        );

        $labels = array(
            'active'   => __('Active', 'tmw-stripe-subscriptions'),
            'trialing' => __('Trialing', 'tmw-stripe-subscriptions'),
            'past_due' => __('Past Due', 'tmw-stripe-subscriptions'),
            'canceled' => __('Canceled', 'tmw-stripe-subscriptions'),
            'inactive' => __('Inactive', 'tmw-stripe-subscriptions'),
            'none'     => __('None', 'tmw-stripe-subscriptions'),
        );

        $color = $colors[$status] ?? '#6b7280';
        $label = $labels[$status] ?? ucfirst($status);

        return sprintf(
            '<span style="color:%s;font-weight:500;">● %s</span>',
            esc_attr($color),
            esc_html($label)
        );
    }

    /**
     * Column: Subscription ID
     *
     * @param object $item
     * @return string
     */
    protected function column_subscription_id($item) {
        $sub_id = $item->stripe_subscription_id;

        if (empty($sub_id)) {
            return '<span style="color:#9ca3af;">—</span>';
        }

        // Link to Stripe dashboard
        $mode = TMW_Stripe_API::get_mode();
        $base = $mode === 'live' 
            ? 'https://dashboard.stripe.com/subscriptions/' 
            : 'https://dashboard.stripe.com/test/subscriptions/';

        return sprintf(
            '<a href="%s" target="_blank" title="%s" style="font-family:monospace;font-size:11px;">%s</a>',
            esc_url($base . $sub_id),
            __('View in Stripe', 'tmw-stripe-subscriptions'),
            esc_html(substr($sub_id, 0, 20) . '...')
        );
    }

    /**
     * Column: Period End
     *
     * @param object $item
     * @return string
     */
    protected function column_period_end($item) {
        if (empty($item->current_period_end)) {
            return '<span style="color:#9ca3af;">—</span>';
        }

        $date = strtotime($item->current_period_end);
        $formatted = date_i18n(get_option('date_format'), $date);

        // Show warning if expired
        if ($date < time() && $item->status !== 'canceled') {
            return sprintf(
                '<span style="color:#ef4444;">%s</span>',
                esc_html($formatted)
            );
        }

        // Show days remaining
        $days_remaining = ceil(($date - time()) / DAY_IN_SECONDS);
        if ($days_remaining > 0 && $days_remaining <= 7) {
            return sprintf(
                '%s<br><small style="color:#f59e0b;">%s</small>',
                esc_html($formatted),
                sprintf(__('%d days left', 'tmw-stripe-subscriptions'), $days_remaining)
            );
        }

        return esc_html($formatted);
    }

    /**
     * Column: Trial Used
     *
     * @param object $item
     * @return string
     */
    protected function column_trial_used($item) {
        if ($item->trial_used) {
            return '<span style="color:#22c55e;">✓</span>';
        }

        return '<span style="color:#9ca3af;">—</span>';
    }

    /**
     * Column: Created
     *
     * @param object $item
     * @return string
     */
    protected function column_created_at($item) {
        return esc_html(date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($item->created_at)
        ));
    }

    /**
     * Default column handler
     *
     * @param object $item
     * @param string $column_name
     * @return string
     */
    protected function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '';
    }

    /**
     * No items message
     */
    public function no_items() {
        _e('No subscribers found.', 'tmw-stripe-subscriptions');
    }

    /**
     * Process any actions
     */
    public function process_actions() {
        // Could handle bulk actions here if needed
    }
}
