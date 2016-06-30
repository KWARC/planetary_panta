<?php 

function node_view_count_db_count_view_insert($nid, $uid) {
  $fields = array(
    'nid' => $nid,  
    'uid' => $uid,
    'timestamp' => REQUEST_TIME,
  );
  return drupal_write_record('node_view_count', $fields);
}

function node_view_count_db_count_view_select($node, $user = NULL, $distinct_users = FALSE) {
  $query = db_select('node_view_count', 'nvc');
  $query->condition('nid', $node->nid, '=');
  if ($user) {
    $query->condition('uid', $user->uid, '=');
  }
  if ($distinct_users) {
    $query->addExpression('COUNT(DISTINCT uid)');
  }
  else {
    $query->addExpression('COUNT(*)');
  }
  $result = $query->execute()->fetchAll();
  return $result;
}