<?php
/**
 * Flower-specific quality, care and claim wording for generated legal pages.
 *
 * Public copy intentionally focuses on inspection at handover and care after
 * receipt. Detailed distance-return terms are maintained separately by the
 * store owner and are not rendered by this module.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * Replace the generated Returns & Claims document with flower-specific wording
 * focused on inspection, photo evidence and care after receipt.
 */
function cg_legal_flower_returns_document($output, $tag, $attr, $m) {
    if ($tag !== 'cg_legal_document') return $output;
    if (!is_array($attr) || sanitize_key($attr['type'] ?? '') !== 'returns') return $output;
    if (!function_exists('cg_legal_get_settings') || !function_exists('cg_legal_document_header')) return $output;

    $s = cg_legal_get_settings();
    $version = !empty($s['offer_version']) ? $s['offer_version'] : wp_date('d.m.Y');

    $html = '<article class="cg-legal-document">';
    $html .= cg_legal_document_header('Качество, уход и претензии', $version);

    $html .= '<section><h2>1. Проверка букета при вручении</h2>';
    $html .= '<p>При получении покупателю или получателю рекомендуется сразу осмотреть букет: проверить свежесть и количество цветов, целостность стеблей и упаковки, отсутствие видимых повреждений и соответствие согласованному заказу.</p>';
    $html .= '<p><strong>Если при вручении всё в порядке, букет считается принятым без замечаний по его видимому состоянию на момент передачи.</strong> Если видимая проблема обнаружена при получении, сообщите об этом магазину сразу или как можно быстрее после обнаружения.</p></section>';

    $html .= '<section><h2>2. Фотофиксация перед отправкой</h2>';
    $html .= '<p>Магазин вправе фотографировать готовый букет перед отправкой. Такая фотография фиксирует внешний вид, состав и видимое состояние композиции перед передачей курьеру и может учитываться при разборе обращения по качеству.</p></section>';

    $html .= '<section><h2>3. Уход после получения</h2>';
    $html .= '<p>Срезанные цветы являются живым скоропортящимся товаром. После вручения их состояние напрямую зависит от температуры, воды, освещения, условий перевозки получателем и дальнейшего ухода.</p>';
    $html .= '<p>Для сохранения свежести поставьте букет в чистую прохладную воду как можно скорее, регулярно меняйте воду, при необходимости обновляйте срезы стеблей и поддерживайте флористическую губку влажной. Не оставляйте цветы без воды, под прямым солнцем, возле батарей и обогревателей, на морозе, в перегретом автомобиле, под сильным потоком кондиционера или на резком сквозняке.</p></section>';

    $html .= '<section><h2>4. Состояние цветов после передачи</h2>';
    $html .= '<p>Увядание, пересыхание, перегрев, переохлаждение, механические повреждения или иное ухудшение состояния цветов, возникшее после вручения из-за хранения, перевозки получателем или ухода, относится к условиям содержания букета после получения.</p>';
    $html .= '<p>При рассмотрении обращения магазин может сопоставлять фотографии готового букета перед отправкой, состояние при вручении, время обращения и условия хранения и ухода после получения.</p></section>';

    $html .= '<section><h2>5. Если проблема заметна при получении</h2>';
    $html .= '<p>Свяжитесь с магазином как можно быстрее. Укажите номер заказа, дату и время получения, опишите проблему и по возможности приложите фотографии букета целиком и проблемного места.</p>';
    $html .= '<p>Телефон: ' . esc_html($s['phone'] ?: get_theme_mod('cg_phone', '+7 (930) 411-98-55')) . '. Режим работы: ' . esc_html(get_theme_mod('cg_worktime', 'Ежедневно с 07:00 до 21:00')) . '.</p></section>';

    $html .= '</article>';
    return $html;
}
add_filter('do_shortcode_tag', 'cg_legal_flower_returns_document', 20, 4);

/**
 * Replace the public-offer section about returns with neutral quality/care copy.
 */
function cg_legal_offer_quality_care_section($output, $tag, $attr, $m) {
    if ($tag !== 'cg_legal_document') return $output;
    if (!is_array($attr) || sanitize_key($attr['type'] ?? '') !== 'offer') return $output;
    if (!is_string($output) || $output === '') return $output;

    $replacement = '<section><h2>7. Получение заказа и уход за цветами</h2>'
        . '<p>При получении покупателю или получателю рекомендуется осмотреть букет и убедиться в его соответствии согласованному заказу и отсутствии видимых повреждений. Если при вручении всё в порядке, заказ принимается без замечаний по видимому состоянию на момент передачи.</p>'
        . '<p>После вручения покупатель или получатель обеспечивает надлежащие условия хранения и ухода за живыми цветами. Состояние букета после передачи зависит от воды, температуры, освещения, перевозки и иных условий содержания.</p>'
        . '</section>';

    $pattern = '~<section><h2>7\. Отказ от товара, возврат и претензии</h2>.*?</section>~su';
    $updated = preg_replace($pattern, $replacement, $output, 1);

    return is_string($updated) && $updated !== '' ? $updated : $output;
}
add_filter('do_shortcode_tag', 'cg_legal_offer_quality_care_section', 30, 4);

/** Add the customer care memo to emails, orders and the print workflow. */
require_once get_template_directory() . '/inc/customer-buyer-memo.php';
