<?php
/**
 * Daily catalog operations on top of the existing quality report.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Human-readable catalog visibility label. */
function cg_catalog_ops_visibility_label($visibility) {
    $labels = [
        'visible' => 'Каталог и поиск',
        'catalog' => 'Только каталог',
        'search' => 'Только поиск',
        'hidden' => 'Скрыт',
    ];
    return isset($labels[$visibility]) ? $labels[$visibility] : 'По настройкам WooCommerce';
}

/** Whether a product or one of its variations is at or below the low-stock threshold. */
function cg_catalog_ops_low_stock($product) {
    if (!$product instanceof WC_Product) return false;

    $candidates = [$product];
    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation instanceof WC_Product) $candidates[] = $variation;
        }
    }

    foreach ($candidates as $candidate) {
        if (!$candidate->managing_stock() || !$candidate->is_in_stock()) continue;
        $quantity = $candidate->get_stock_quantity();
        if ($quantity === null) continue;

        $threshold = function_exists('wc_get_low_stock_amount')
            ? wc_get_low_stock_amount($candidate)
            : get_option('woocommerce_notify_low_stock_amount', 2);
        $threshold = is_numeric($threshold) ? (float) $threshold : 2.0;

        if ((float) $quantity > 0 && (float) $quantity <= $threshold) return true;
    }

    return false;
}

/** Compact stock text for the manager. */
function cg_catalog_ops_stock_label($product, $is_low) {
    if (!$product instanceof WC_Product) return '—';
    if (!$product->is_in_stock()) return 'Нет в наличии';

    if ($product->managing_stock()) {
        $quantity = $product->get_stock_quantity();
        if ($quantity !== null) {
            return ($is_low ? 'Низкий остаток: ' : 'Остаток: ') . wc_stock_amount($quantity);
        }
    }

    if ($product->is_type('variable') && $is_low) return 'Низкий остаток в варианте';
    return 'В наличии';
}

/** One cached operational pass over published parent products. */
function cg_catalog_ops_report() {
    static $report = null;
    if ($report !== null) return $report;

    $report = [
        'rows' => [],
        'total' => 0,
        'attention' => 0,
        'missing_price' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0,
        'hidden' => 0,
    ];

    if (!class_exists('WooCommerce') || !function_exists('wc_get_products')) return $report;

    $products = wc_get_products([
        'status' => 'publish',
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || $product->get_parent_id() > 0) continue;

        $visibility = (string) $product->get_catalog_visibility();
        $missing_price = $product->get_price() === '';
        $out_of_stock = !$product->is_in_stock();
        $low_stock = !$out_of_stock && cg_catalog_ops_low_stock($product);
        $hidden = in_array($visibility, ['search', 'hidden'], true);

        $issues = [];
        if ($missing_price) $issues[] = 'Нет цены';
        if ($out_of_stock) $issues[] = 'Нет в наличии';
        elseif ($low_stock) $issues[] = 'Низкий остаток';
        if ($hidden) $issues[] = 'Не показывается в обычном каталоге';

        $report['total']++;
        if ($issues) $report['attention']++;
        if ($missing_price) $report['missing_price']++;
        if ($out_of_stock) $report['out_of_stock']++;
        if ($low_stock) $report['low_stock']++;
        if ($hidden) $report['hidden']++;

        $report['rows'][] = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price_html' => $product->get_price_html(),
            'visibility' => $visibility,
            'visibility_label' => cg_catalog_ops_visibility_label($visibility),
            'stock_label' => cg_catalog_ops_stock_label($product, $low_stock),
            'missing_price' => $missing_price,
            'out_of_stock' => $out_of_stock,
            'low_stock' => $low_stock,
            'hidden' => $hidden,
            'issues' => $issues,
        ];
    }

    usort($report['rows'], static function ($a, $b) {
        $left = count($a['issues']);
        $right = count($b['issues']);
        if ($left === $right) return strcasecmp($a['name'], $b['name']);
        return $right <=> $left;
    });

    return $report;
}

/** Filter operational rows by the selected issue. */
function cg_catalog_ops_rows_for_view($rows, $view) {
    if ($view === 'all') return array_values(array_filter($rows, static function ($row) { return !empty($row['issues']); }));

    $key_map = [
        'price' => 'missing_price',
        'stock' => 'out_of_stock',
        'low' => 'low_stock',
        'hidden' => 'hidden',
    ];
    if (!isset($key_map[$view])) return [];
    $key = $key_map[$view];

    return array_values(array_filter($rows, static function ($row) use ($key) {
        return !empty($row[$key]);
    }));
}

/** Operations panel above the existing catalog-quality page. */
function cg_catalog_ops_quality_panel() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'cg-catalog-quality') return;

    $allowed = ['all', 'price', 'stock', 'low', 'hidden'];
    $view = isset($_GET['ops_view']) ? sanitize_key(wp_unslash($_GET['ops_view'])) : 'all';
    if (!in_array($view, $allowed, true)) $view = 'all';

    $report = cg_catalog_ops_report();
    $rows = cg_catalog_ops_rows_for_view($report['rows'], $view);
    $base = admin_url('admin.php?page=cg-catalog-quality');

    $cards = [
        'all' => ['label' => 'Требуют внимания', 'count' => $report['attention']],
        'price' => ['label' => 'Без цены', 'count' => $report['missing_price']],
        'stock' => ['label' => 'Нет в наличии', 'count' => $report['out_of_stock']],
        'low' => ['label' => 'Низкий остаток', 'count' => $report['low_stock']],
        'hidden' => ['label' => 'Не в каталоге', 'count' => $report['hidden']],
    ];

    echo '<section class="cg-catalog-ops">';
    echo '<div class="cg-catalog-ops__head"><div><span>Ежедневная работа</span><strong>Продажи сегодня</strong><p>Здесь только состояние товаров. Ничего автоматически не скрывается и не меняется.</p></div><a class="button" href="' . esc_url(admin_url('edit.php?post_type=product')) . '">Все товары</a></div>';
    echo '<div class="cg-catalog-ops__cards">';
    foreach ($cards as $key => $card) {
        $url = add_query_arg('ops_view', $key, $base);
        $classes = ['cg-catalog-ops__card'];
        if ($view === $key) $classes[] = 'is-active';
        if ($key !== 'all' && (int) $card['count'] > 0) $classes[] = 'has-attention';
        echo '<a class="' . esc_attr(implode(' ', $classes)) . '" href="' . esc_url($url) . '"><span>' . esc_html($card['label']) . '</span><strong>' . esc_html((string) $card['count']) . '</strong></a>';
    }
    echo '</div>';

    if (!$rows) {
        echo '<div class="cg-catalog-ops__empty"><strong>По этой проверке всё спокойно.</strong><span>Товаров, требующих внимания, сейчас нет.</span></div>';
        echo '</section>';
        return;
    }

    echo '<div class="cg-catalog-ops__table-wrap"><table class="widefat striped cg-catalog-ops__table"><thead><tr><th>Товар</th><th>Цена</th><th>Наличие</th><th>Видимость</th><th>Что проверить</th></tr></thead><tbody>';
    foreach (array_slice($rows, 0, 100) as $row) {
        $edit = get_edit_post_link($row['id']);
        echo '<tr>';
        echo '<td><a href="' . esc_url($edit) . '"><strong>' . esc_html($row['name']) . '</strong></a></td>';
        echo '<td>' . ($row['missing_price'] ? '<span class="cg-catalog-ops__badge is-bad">Нет цены</span>' : wp_kses_post($row['price_html'])) . '</td>';
        echo '<td><span class="cg-catalog-ops__badge ' . ($row['out_of_stock'] ? 'is-bad' : ($row['low_stock'] ? 'is-warn' : 'is-good')) . '">' . esc_html($row['stock_label']) . '</span></td>';
        echo '<td><span class="cg-catalog-ops__badge ' . ($row['hidden'] ? 'is-warn' : 'is-neutral') . '">' . esc_html($row['visibility_label']) . '</span></td>';
        echo '<td>' . esc_html(implode(' · ', $row['issues'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    if (count($rows) > 100) echo '<p class="cg-catalog-ops__limit">Показаны первые 100 товаров. Используйте отдельные карточки выше, чтобы сузить список.</p>';
    echo '</section>';
}
add_action('admin_notices', 'cg_catalog_ops_quality_panel', 3);

/** Compact catalog sales-health signal on the existing order-control page. */
function cg_catalog_ops_readiness_notice() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'cg-order-readiness') return;

    $report = cg_catalog_ops_report();
    $has_attention = $report['missing_price'] || $report['out_of_stock'] || $report['low_stock'];
    $class = $has_attention ? 'notice-warning' : 'notice-success';
    $url = admin_url('admin.php?page=cg-catalog-quality');

    echo '<div class="notice ' . esc_attr($class) . ' inline cg-catalog-ops-readiness"><p><strong>Каталог к продаже:</strong> ';
    echo esc_html($report['missing_price'] . ' без цены · ' . $report['out_of_stock'] . ' нет в наличии · ' . $report['low_stock'] . ' с низким остатком');
    echo '. <a href="' . esc_url($url) . '">Открыть качество каталога</a></p></div>';
}
add_action('admin_notices', 'cg_catalog_ops_readiness_notice', 9);

/** Styles only where the operational catalog block can appear. */
function cg_catalog_ops_admin_assets($hook) {
    if (!in_array($hook, ['woocommerce_page_cg-catalog-quality', 'woocommerce_page_cg-order-readiness'], true)) return;
    $path = get_template_directory() . '/assets/css/catalog-operations-admin.css';
    wp_enqueue_style(
        'cg-catalog-operations-admin',
        get_template_directory_uri() . '/assets/css/catalog-operations-admin.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_catalog_ops_admin_assets');
