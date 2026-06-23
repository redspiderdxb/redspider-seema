<?php

class RedSpider_Navwalker extends Walker_Nav_Menu
{
  public function start_lvl(&$output, $depth = 0, $args = null)
  {
    $output .= '<ul>';
  }

  public function end_lvl(&$output, $depth = 0, $args = null)
  {
    $output .= '</ul>';
  }

  public function start_el(
    &$output,
    $item,
    $depth = 0,
    $args = null,
    $id = 0
  ) {
    $classes = empty($item->classes)
      ? []
      : (array) $item->classes;

    $has_children = in_array(
      'menu-item-has-children',
      $classes,
      true
    );

    $is_active = in_array('current-menu-item', $classes, true) ||
                 in_array('current-menu-parent', $classes, true) ||
                 in_array('current-menu-ancestor', $classes, true) ||
                 in_array('current_page_item', $classes, true);

    $link_class = $is_active ? 'active' : '';

    if ($has_children) {

      $output .= '<li class="dropdown">';

      $output .= '<a href="' . esc_url($item->url) . '" class="' . esc_attr($link_class) . '">';

      $output .= '<span>' .
        esc_html($item->title) .
        '</span>';

      $output .= '<i class="bi bi-chevron-down toggle-dropdown"></i>';

      $output .= '</a>';

    } else {

      $output .= '<li>';

      $output .= '<a href="' .
        esc_url($item->url) .
        '" class="' . esc_attr($link_class) . '">';

      $output .= esc_html($item->title);

      $output .= '</a>';
    }
  }

  public function end_el(
    &$output,
    $item,
    $depth = 0,
    $args = null
  ) {
    $output .= '</li>';
  }
}