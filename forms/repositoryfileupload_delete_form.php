<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class local_repositoryfileupload_delete_form extends moodleform {

    function definition() {
        global $CFG, $local_repositoryfileupload_sessprefix;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $courseid = $this->_customdata['courseid'];
        $repositoryid = $this->_customdata['repositoryid'];
        $repositoryname = $this->_customdata['repositoryname'];

        $mform->addElement('html', '<h2 class="mdl-left">'.get_string('fileslistheader', 'local_repositoryfileupload', $repositoryname).'</h2>');
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'repositoryid', $repositoryid);
        $mform->setType('repositoryid', PARAM_INT);

        $nonceinfo = local_repositoryfileupload_generatenonce();
        $mform->addElement('hidden', 'nonce', $nonceinfo['nonce']);
        $mform->setType('nonce', PARAM_NOTAGS);
        $mform->addElement('hidden', 'nonceid', $nonceinfo['id']);
        $mform->setType('nonceid', PARAM_INT);

        // multi-select box
        $files = local_repositoryfileupload_getrepositoryfiles($CFG->dataroot, $repositoryname);
        if (count($files) > 0) {
            sort($files);
            $filesenc = array();
            foreach ($files as $idx => $file) {
                $fileenc = urlencode($file);
                $filesenc[] = $fileenc;
            }

            $selectsize = min(count($filesenc)+1, 20);
            $multiselect = $mform->addElement('select', 'filestodelete', get_string('fileselectlabel', 'local_repositoryfileupload'), $filesenc, array('size'=>$selectsize, 'class'=>'mdl-left'), null);
            $multiselect->setMultiple(true);
            $multiselect->setHiddenLabel(true);
            $fileshash = local_repositoryfileupload_getrepositoryfileshash($repositoryid, $files);
            $mform->addElement('hidden', 'fileshash', $fileshash);
            $mform->setType('fileshash', PARAM_NOTAGS);

            $this->add_action_buttons(true, get_string('deletefiles', 'local_repositoryfileupload'));
        } else {
            $mform->addElement('html', '<p>' . get_string('emptyrepo', 'local_repositoryfileupload') . '</p>');
        }
    }
}

