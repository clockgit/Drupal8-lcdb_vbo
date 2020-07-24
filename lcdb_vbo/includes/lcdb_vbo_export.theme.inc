<?php

/**
 * @file
 * Theme implementation for csv output.
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Csv content builder function.
 */
function theme_lcdb_vbo_export_content_csv($variables) {
  $config = $variables['configuration'];
  // Sanitize data.
  foreach ($variables['header'] as $key => $item) {
    $variables['header'][$key] = strtr($item, array($config['separator'] => ' '));
  }

  $content_replacements = array(
    "\r\n" => ' ',
    "\n\r" => ' ',
    "\r" => ' ',
    "\n" => ' ',
    "\t" => ' ',
    $config['separator'] => ' ',
  );
  foreach ($variables['rows'] as $row_key => $row) {
    foreach ($row as $cell_key => $cell) {
      $variables['rows'][$row_key][$cell_key] = strtr($cell, $content_replacements);
    }
  }

  // Generate output.
  $csv_rows = array();
  //$csv_rows[] = implode($config['separator'], $variables['header']);
  $csv_rows[] = implode($config['separator'], $variables['first_row']);
  foreach ($variables['rows'] as $row) {
    $csv_rows[] = implode($config['separator'], $row);
  }
  $csv_rows[] = implode($config['separator'], $variables['last_row']);

  $csv_string = implode(PHP_EOL, $csv_rows);
  if (!empty($config['strip_tags'])) {
    $csv_string = strip_tags($csv_string);
  }
  return $csv_string;
}
