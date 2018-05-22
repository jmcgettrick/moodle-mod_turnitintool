<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for mod_turnitintool.
 *
 * @package    mod_turnitintool
 * @copyright  2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_turnitintool\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {

    // This is the trait to be included to actually benefit from the polyfill.
    use \core_privacy\local\legacy_polyfill;

    /**
     * Return the fields which contain personal data.
     *
     * @param $collection items a reference to the collection to use to store the metadata.
     * @return $collection the updated collection of metadata items.
     */
    public static function _get_metadata(collection $collection) {

        $collection->link_subsystem(
            'core_files',
            'privacy:metadata:core_files'
        );

        $collection->add_database_table(
            'turnitintool_users',
            [
                'userid' => 'privacy:metadata:turnitintool_users:userid',
                'turnitin_uid' => 'privacy:metadata:turnitintool_users:turnitin_uid'
            ],
            'privacy:metadata:turnitintool_users'
        );

        $collection->add_database_table(
            'turnitintool_submissions',
            [
                'userid' => 'privacy:metadata:turnitintool_submissions:userid',
                'submission_title' => 'privacy:metadata:turnitintool_submissions:submission_title',
                'submission_filename' => 'privacy:metadata:turnitintool_submissions:submission_filename',
                'submission_objectid' => 'privacy:metadata:turnitintool_submissions:submission_objectid',
                'submission_score' => 'privacy:metadata:turnitintool_submissions:submission_score',
                'submission_grade' => 'privacy:metadata:turnitintool_submissions:submission_grade',
                'submission_attempts' => 'privacy:metadata:turnitintool_submissions:submission_attempts',
                'submission_modified' => 'privacy:metadata:turnitintool_submissions:submission_modified',
                'submission_unanon' => 'privacy:metadata:turnitintool_submissions:submission_unanon',
                'submission_unanonreason' => 'privacy:metadata:turnitintool_submissions:submission_unanonreason',
                'submission_transmatch' => 'privacy:metadata:turnitintool_submissions:submission_transmatch',
                'submission_hash' => 'privacy:metadata:turnitintool_submissions:submission_hash',
            ],
            'privacy:metadata:turnitintool_submissions'
        );

        $collection->add_database_table(
            'turnitintool_comments',
            [
                'userid' => 'privacy:metadata:turnitintool_comments:userid',
                'commenttext' => 'privacy:metadata:turnitintool_comments:commenttext'
            ],
            'privacy:metadata:turnitintool_comments'
        );

        $collection->link_external_location('turnitintool_client', [
            'email' => 'privacy:metadata:turnitintool_client:email',
            'firstname' => 'privacy:metadata:turnitintool_client:firstname',
            'lastname' => 'privacy:metadata:turnitintool_client:lastname',
            'submission_title' => 'privacy:metadata:turnitintool_client:submission_title',
            'submission_filename' => 'privacy:metadata:turnitintool_client:submission_filename',
        ], 'privacy:metadata:turnitintool_client');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {

        // Fetch all contexts where the user has a submission.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {turnitintool} t ON t.id = cm.instance
                LEFT JOIN {turnitintool_submissions} ts ON ts.turnitintoolid = t.id
        ";

        $params = [
            'contextlevel'  => CONTEXT_MODULE,
            'modname' => 'turnitintool',
            'userid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                ts.submission_title,
                ts.submission_filename,
                ts.submission_objectid,
                ts.submission_score,
                ts.submission_grade,
                ts.submission_attempts,
                ts.submission_modified,
                ts.submission_unanon,
                ts.submission_unanonreason,
                ts.submission_transmatch,
                ts.submission_hash,
                tu.turnitin_uid
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid
                INNER JOIN {turnitintool} t ON t.id = cm.instance
                LEFT JOIN {turnitintool_submissions} ts ON ts.turnitintoolid = t.id
                LEFT JOIN {turnitintool_users} tu ON ts.userid = tu.userid
                WHERE c.id {$contextsql}
                AND ts.userid = :userid
                ORDER BY cm.id";

        $params = ['userid' => $user->id] + $contextparams;

        $submissions = $DB->get_records_sql($sql, $params);
        foreach ($submissions as $submission) {
            $context = \context_module::instance($submission->cmid);
            self::_export_turnitintool_data_for_user((array)$submission, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single activity, along with any generic data or area files.
     *
     * @param array $submissiondata the personal data to export.
     * @param \context_module $context the module context.
     * @param \stdClass $user the user record
     */
    protected static function _export_turnitintool_data_for_user(array $submissiondata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with module data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $submissiondata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function _delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }

        if (!$context instanceof \context_module) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

        // Get submission ids and delete comments relating to them.
        $submissions = $DB->get_records('turnitintool_submissions', ['turnitintoolid' => $instanceid]);
        foreach ($submissions as $submission) {
            $DB->delete_records('turnitintool_comments', ['submissionid' => $submission->id]);
        }

        // Delete all submissions.
        $DB->delete_records('turnitintool_submissions', ['turnitintoolid' => $instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        // Delete records.
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                return;
            }

            // Get all user's submissions.
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $submissions = $DB->get_records(
                'turnitintool_submissions',
                ['turnitintoolid' => $instanceid, 'userid' => $contextlist->get_user()->id]
            );

            // Delete all comments.
            foreach ($submissions as $submission) {
                $DB->delete_records('turnitintool_comments', ['submissionid' => $submission->id]);
            }

            // Delete all submissions.
            $DB->delete_records(
                'turnitintool_submissions',
                ['turnitintoolid' => $instanceid, 'userid' => $contextlist->get_user()->id]
            );
        }
    }
}