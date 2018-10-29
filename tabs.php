<?php

if (empty($currenttab) or empty($course)) {
    print_error('cannotcallscript');
}

$row = array();
$tabs = array();

$row[] = new tabobject('uploadfiles', "$CFG->wwwroot/local/repositoryfileupload/upload.php?courseid=$courseid", 'Upload files');
$row[] = new tabobject('removefiles', "$CFG->wwwroot/local/repositoryfileupload/delete.php?courseid=$courseid", 'Remove files');

$tabs[] = $row;
print_tabs($tabs, $currenttab);
