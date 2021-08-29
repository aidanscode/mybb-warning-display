<?php
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.");
}

/**
 * WARNING DISPLAY - Globals
 */
$warningsKeyedByPostId = [];
$warningReason = null;

/**
 * WARNING DISPLAY - MyBB Base Plugin Methods
 */
function warningdisplay_info() {
  return [
    "name" => "Warning Display",
    "description" =>"Display warning reasons in posts",
    "website" => "https://aidanmurphey.com",
    "author" => "Aidan",
    "authorsite" => "https://aidanmurphey.com",
    "version" => "1.0",
    "guid" => "",
    "codename" => str_replace('.php', '', basename(__FILE__)),
    "compatibility" => "18*"
  ];
}

function warningdisplay_activate() {
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets(
    'postbit',
    '#' . preg_quote("{\$post['signature']}") . '#',
    "{\$post['signature']}{\$post['warning_display']}"
  );
}

function warningdisplay_deactivate() {
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets(
    'postbit',
    '#' . preg_quote("{\$post['warning_display']}") . '#',
    ''
  );
}

function warningdisplay_install() {
  global $db;

  $template = '<div style="background-image: linear-gradient(#9E0000, red); color: white; border-radius: 15px; padding: 5px 10px; margin-top: 4px; margin-bottom: 4px;">' . "\n";
  $template .= '  <img src="images/icons/information.png" alt="Information" title="Information" style="vertical-align: middle;">' . "\n";
  $template .= '  User was warned for this post. Reason: {$warningReason}' . "\n";
  $template .= '</div>' . "\n";

  $insert = [
    'title' => 'warning_display_template',
    'template' => $db->escape_string($template),
    'sid' => -1,
    'version' => '',
    'dateline' => time()
  ];
  $db->insert_query('templates', $insert);
}

function warningdisplay_is_installed() {
  global $db;

  $query = $db->simple_select('templates', '*', 'title="warning_display_template"', ['limit' => 1]);
  $count = $db->num_rows($query);
  $db->free_result($query);

  return $count > 0;
}

function warningdisplay_uninstall() {
  global $db;

  $db->delete_query('templates', 'title="warning_display_template"');
}

/**
 * WARNING DISPLAY - Utility Methods
 */
function warningdisplay_query_results_to_array(&$query) {
  global $db;
  $result = [];

  while ($row = $db->fetch_array($query)) {
    $result[] = $row;
  }

  return $result;
}

function warningdisplay_key_by($keyByName, $toBeKeyed) {
  $result = [];

  foreach($toBeKeyed as $row) {
    $keyByValue = $row[$keyByName];
    if (!array_key_exists($keyByValue, $result)) {
      $result[$keyByValue] = [];
    }
    $result[$keyByValue][] = $row;
  }

  return $result;
}

function warningdisplay_get_table_name_with_prefix($tableName) {
  global $db;

  return $db->table_prefix . $tableName;
}

function warningdisplay_get_warning_html_for_reason($reason) {
  global $templates, $warningReason;

  $warningReason = htmlspecialchars_uni($reason);
  eval('$result = "' . $templates->get('warning_display_template') . '";');
  return $result;
}

/**
 * WARNING DISPLAY - MyBB Hook methods
 */
function warningdisplay_fetch_warnings() {
  global $db, $mybb, $warningsKeyedByPostId;

  $postsTable = warningdisplay_get_table_name_with_prefix('posts');
  $warningsTable = warningdisplay_get_table_name_with_prefix('warnings');

  $threadId = $mybb->get_input('tid');
  $sql = "
    SELECT {$warningsTable}.pid, {$warningsTable}.title
    FROM {$warningsTable}
    JOIN {$postsTable} ON {$warningsTable}.pid = {$postsTable}.pid
    WHERE {$postsTable}.tid = " . $db->escape_string($threadId) . " AND {$warningsTable}.revokedby = 0
  ";
  $query = $db->write_query($sql);

  $rows = warningdisplay_query_results_to_array($query);
  $db->free_result($query);

  $warningsKeyedByPostId = warningdisplay_key_by('pid', $rows);
}

function warningdisplay_postbit(&$post) {
  global $warningsKeyedByPostId;

  $pid = $post['pid'];
  if (!array_key_exists($pid, $warningsKeyedByPostId)) {
    $post['warning_display'] = '';
  } else {
    $warningDisplays = [];
    foreach($warningsKeyedByPostId[$pid] as $warning) {
      $reason = $warning['title'];
      $warningDisplays[] = warningdisplay_get_warning_html_for_reason($reason);
    }
    $post['warning_display'] = implode('', $warningDisplays);
  }
}

$plugins->add_hook('showthread_start', 'warningdisplay_fetch_warnings');
$plugins->add_hook('postbit', 'warningdisplay_postbit');
