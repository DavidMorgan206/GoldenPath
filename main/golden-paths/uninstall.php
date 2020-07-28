<?php
// If uninstall not called from WordPress exit
  if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
      exit();

  global $GpathsModel;
  require_once('model\gpaths_db_setup.php');
  $GpathsModel->dropAllTables();

