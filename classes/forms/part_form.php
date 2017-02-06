<?php
/**
 * @package   turnitintool
 * @copyright 2017 Turnitin
 *
 */

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class part_form extends moodleform {
    //Add elements to form
    public function definition() {
        $part = $this->_customdata['part'];

        $mform = $this->_form;

        $mform->addElement('hidden', 'submitted', $part->id); // Add elements to your form
        $mform->setType('submitted', PARAM_RAW);

        // Part Name Field
        $mform->addElement('text', 'partname', get_string('partname', 'turnitintool'), array("class" => "boldlabel"));
        $mform->setType('partname', PARAM_TEXT);
        $mform->setDefault('partname', $part->partname);

        // Date options.
        $dateoptions = array('startyear' => date( 'Y', strtotime( '-6 years' )), 'stopyear' => date( 'Y', strtotime( '+6 years' )),
                        'timezone' => 99, 'applydst' => true, 'step' => 1, 'optional' => false);

        // Date fields.
        $mform->addElement('date_time_selector', 'dtstart', get_string('dtstart', 'turnitintool'), $dateoptions, array("class" => "boldlabel"));
        $mform->setDefault('dtstart', $part->dtstart);

        $mform->addElement('date_time_selector', 'dtdue', get_string('dtdue', 'turnitintool'), $dateoptions, array("class" => "boldlabel"));
        $mform->setDefault('dtdue', $part->dtdue);

        $mform->addElement('date_time_selector', 'dtpost', get_string('dtpost', 'turnitintool'), $dateoptions, array("class" => "boldlabel"));
        $mform->setDefault('dtpost', $part->dtpost);

        // Max marks
        $mform->addElement('text', 'maxmarks', get_string('maxmarks', 'turnitintool'), array("class" => "boldlabel"));
        $mform->setType('maxmarks', PARAM_INT);
        $mform->setDefault('maxmarks', '100');
        $mform->addRule('maxmarks', null, 'numeric', null, 'client');

        // Submit and cancel button.
        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('submit'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

/* ?> */
