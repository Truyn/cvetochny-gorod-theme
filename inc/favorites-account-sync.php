<?php
/**
 * Synchronize browser favorites with the signed-in WooCommerce account.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_ACCOUNT_FAVORITES_META = '_cg_favorite_product_ids';
const CG_ACCOUNT_FAVORITES_UPDATED_META = '_cg_favorite_product_ids_updated_at';

/** Normalize and optionally validate product IDs before storing them. */
function cg_account_favorites_normalize($ids, $validate_products = true) {
    if (!is_array($ids)) {
        $ids = [];
    }

    $normalized = [];
    foreach ($ids as $raw_id) {
        $product_id = absint($raw_id);
        if (!$product_id || in_array($product_id, $normalized, true)) continue;

        if ($validate_products && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            if (!$product instanceof WC_Product) continue;
            if ($product->get_status() !== 'publish' || !$product->is_visible()) continue;
        }

        $normalized[] = $product_id;
        if (count($normalized) >= 100) break;
    }

    return $normalized;
}

/** Read a customer's saved favorites from user meta. */
function cg_get_account_favorites($user_id = 0) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    if (!$user_id) return [];

    $stored = get_user_meta($user_id, CG_ACCOUNT_FAVORITES_META, true);
    return cg_account_favorites_normalize(is_array($stored) ? $stored : [], false);
}

/** Timestamp used to resolve changes made on different devices. */
function cg_get_account_favorites_updated_at($user_id = 0) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    if (!$user_id) return 0;

    return absint(get_user_meta($user_id, CG_ACCOUNT_FAVORITES_UPDATED_META, true));
}

/** Persist the exact ordered list for a customer. */
function cg_save_account_favorites($user_id, $ids, $client_updated_at = 0) {
    $user_id = absint($user_id);
    if (!$user_id) return ['ids' => [], 'updatedAt' => 0];

    $ids = cg_account_favorites_normalize($ids, true);
    $server_now = (int) round(microtime(true) * 1000);
    $updated_at = max(absint($client_updated_at), $server_now);

    if ($ids) {
        update_user_meta($user_id, CG_ACCOUNT_FAVORITES_META, $ids);
    } else {
        delete_user_meta($user_id, CG_ACCOUNT_FAVORITES_META);
    }
    update_user_meta($user_id, CG_ACCOUNT_FAVORITES_UPDATED_META, $updated_at);

    return ['ids' => $ids, 'updatedAt' => $updated_at];
}

/** AJAX endpoint used after every signed-in favorites change. */
function cg_ajax_sync_account_favorites() {
    check_ajax_referer('cg_favorites_account_sync', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Войдите в личный кабинет, чтобы синхронизировать избранное.'], 401);
    }

    $user_id = get_current_user_id();
    $raw_ids = isset($_POST['ids']) ? (array) wp_unslash($_POST['ids']) : [];
    $client_updated_at = isset($_POST['updated_at']) ? absint($_POST['updated_at']) : 0;
    $server_updated_at = cg_get_account_favorites_updated_at($user_id);

    // A stale tab or device must not overwrite a newer account state.
    if ($server_updated_at > 0 && $client_updated_at < $server_updated_at) {
        $server_ids = cg_account_favorites_normalize(cg_get_account_favorites($user_id), true);
        wp_send_json_success([
            'ids' => $server_ids,
            'count' => count($server_ids),
            'updatedAt' => $server_updated_at,
            'conflictResolved' => true,
        ]);
    }

    $saved = cg_save_account_favorites($user_id, $raw_ids, $client_updated_at);

    wp_send_json_success([
        'ids' => $saved['ids'],
        'count' => count($saved['ids']),
        'updatedAt' => $saved['updatedAt'],
        'conflictResolved' => false,
    ]);
}
add_action('wp_ajax_cg_sync_account_favorites', 'cg_ajax_sync_account_favorites');

/**
 * Load before the main favorites script so the merged account/browser list is
 * already in localStorage when the regular interface initializes.
 */
function cg_favorites_account_sync_assets() {
    if (!class_exists('WooCommerce')) return;

    $theme_version = wp_get_theme()->get('Version');
    $script_path = get_template_directory() . '/assets/js/favorites-account-sync.js';
    $style_path = get_template_directory() . '/assets/css/favorites-account-sync.css';
    $logged_in = is_user_logged_in();

    wp_enqueue_style(
        'cg-favorites-account-sync',
        get_template_directory_uri() . '/assets/css/favorites-account-sync.css',
        [],
        file_exists($style_path) ? filemtime($style_path) : $theme_version
    );

    wp_enqueue_script(
        'cg-favorites-account-sync',
        get_template_directory_uri() . '/assets/js/favorites-account-sync.js',
        [],
        file_exists($script_path) ? filemtime($script_path) : $theme_version,
        true
    );

    $account_url = function_exists('wc_get_page_permalink')
        ? wc_get_page_permalink('myaccount')
        : wp_login_url(function_exists('cg_favorites_url') ? cg_favorites_url() : home_url('/'));

    wp_localize_script('cg-favorites-account-sync', 'cgFavoritesAccountSync', [
        'loggedIn' => $logged_in,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cg_favorites_account_sync'),
        'serverIds' => $logged_in ? cg_get_account_favorites() : [],
        'serverUpdatedAt' => $logged_in ? cg_get_account_favorites_updated_at() : 0,
        'accountUrl' => $account_url,
        'strings' => [
            'guest' => 'Войдите в личный кабинет — избранное будет доступно на телефоне и компьютере.',
            'login' => 'Войти',
            'ready' => 'Избранное синхронизируется с личным кабинетом.',
            'syncing' => 'Сохраняем избранное в личном кабинете…',
            'saved' => 'Избранное сохранено в личном кабинете.',
            'error' => 'Не удалось синхронизировать избранное. Повторим автоматически.',
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cg_favorites_account_sync_assets', 24);
