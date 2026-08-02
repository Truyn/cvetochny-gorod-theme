<?php
/**
 * Template Name: Контакты — Цветочный город
 * Template Post Type: page
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

$template = locate_template('page-contacts.php');
if ($template) {
    require $template;
}
