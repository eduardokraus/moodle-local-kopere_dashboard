<?php
/**
 * User: Eduardo Kraus
 * Date: 09/11/17
 * Time: 16:36
 */

namespace local_kopere_dashboard\report\custom;

use local_kopere_dashboard\html\button;
use local_kopere_dashboard\util\export;
use local_kopere_dashboard\util\header;

/**
 * Class course_last_access
 *
 * @package local_kopere_dashboard\report\custom
 */
class course_last_access
{
    /**
     * @return string
     */
    public function name ()
    {
        return get_string_kopere ( 'reports_report_courses-3' );
    }

    /**
     * @return boolean
     */
    public function is_enable ()
    {
        return true;
    }

    /**
     * @return void
     */
    public function generate ()
    {
        global $DB, $CFG;

        $cursos_id = optional_param ( 'courseid', 0, PARAM_INT );
        if ( $cursos_id == 0 ) {
            header::notfound ( get_string_kopere ( 'courses_notound' ) );
        }

        $course = $DB->get_record ( 'course', array( 'id' => $cursos_id ) );
        header::notfound_null ( $course, get_string_kopere ( 'courses_notound' ) );

        button::info ( get_string_kopere ( 'reports_export' ), "{$_SERVER['QUERY_STRING']}&export=xls" );

        $export = optional_param ( 'export', '', PARAM_TEXT );
        export::header ( $export, "Lista de alunos - $course->fullname" );

        echo '<table id="list-course-access" class="table table-bordered table-hover" border="1">';

        $user_info_fields = $DB->get_records ( 'user_info_field', null, 'sortorder asc' );

        $filds = '';
        foreach ( $user_info_fields as $user_info_field ) {
            $filds .= '<td align="center" bgcolor="#979797" style="text-align:center;">' . $user_info_field->name . '</td>';
        }

        echo '<thead>
                  <tr bgcolor="#C5C5C5" style="background-color: #c5c5c5;" >
                      <td align="center" bgcolor="#979797" style="text-align:center;">' . get_string_kopere ( 'user_table_fullname' ) . '</td>
                      <td align="center" bgcolor="#979797" style="text-align:center;">' . get_string_kopere ( 'user_table_email' ) . '</td>
                      ' . $filds . '
                      <td align="center" bgcolor="#979797" style="text-align:center;">Primeiro acesso</td>
                      <td align="center" bgcolor="#979797" style="text-align:center;">Último acesso</td>
                  </th>
              </thead>';

        $sql
            = "SELECT DISTINCT u.*
                 FROM {context} c
                 JOIN {role_assignments} ra ON ra.contextid = c.id
                 JOIN {user} u              ON ra.userid    = u.id
                WHERE c.contextlevel = :contextlevel
                  AND c.instanceid   = :instanceid";

        $all_user_course = $DB->get_records_sql ( $sql,
            array(
                'contextlevel' => CONTEXT_COURSE,
                'instanceid'   => $cursos_id
            ) );

        foreach ( $all_user_course as $user ) {
            echo '<tr>';
            $link = $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&course=' . $cursos_id;
            $this->td ( '<a href="' . $link . '" target="moodle">' . fullname ( $user ) . '</a>', 'bg-info text-nowrap', '#D9EDF7' );
            $this->td ( $user->email, 'bg-info text-nowrap', '#D9EDF7' );

            $filds = '';
            foreach ( $user_info_fields as $user_info_field ) {
                $user_info_data = $DB->get_record ( 'user_info_data',
                    array( 'userid' => $user->id, 'fieldid' => $user_info_field->id ) );
                if ( $user_info_data )
                    $this->td ( $user_info_data->data, 'text-nowrap bg-success', 'D9EDF7' );
                else
                    $this->td ( '', 'text-nowrap bg-success', 'D9EDF7' );
            }

            $sql
                = "SELECT timecreated
                      FROM {logstore_standard_log}
                     WHERE courseid = :courseid
                       AND action   = :action
                       AND userid   = :userid
                  ORDER BY timecreated ASC
                     LIMIT 1";

            $log_result = $DB->get_record_sql ( $sql,
                array(
                    'courseid' => $cursos_id,
                    'action'   => 'viewed',
                    'userid'   => $user->id
                ) );
            if ( $log_result && $log_result->timecreated ) {
                $this->td ( userdate ( $log_result->timecreated, get_string ( 'strftimedatetime' ) ), 'text-nowrap bg-success', 'DFF0D8' );
            } else {
                $this->td ( '<span style="color: #282828">' . get_string_kopere ( 'reports_noneaccess' ) . '</span>', 'bg-warning text-nowrap', '#FCF8E3' );
            }

            $sql
                = "SELECT timecreated
                      FROM {logstore_standard_log}
                     WHERE courseid = :courseid
                       AND action   = :action
                       AND userid   = :userid
                  ORDER BY timecreated DESC
                     LIMIT 1";

            $log_result = $DB->get_record_sql ( $sql,
                array(
                    'courseid' => $cursos_id,
                    'action'   => 'viewed',
                    'userid'   => $user->id
                ) );
            if ( $log_result && $log_result->timecreated ) {
                $this->td ( userdate ( $log_result->timecreated, get_string ( 'strftimedatetime' ) ), 'text-nowrap bg-success', 'DFF0D8' );
            } else {
                $this->td ( '<span style="color: #282828">' . get_string_kopere ( 'reports_noneaccess' ) . '</span>', 'bg-warning text-nowrap', '#FCF8E3' );
            }

            echo '</tr>';
        }

        echo '</table>';

        export::close ();
    }

    /**
     * @param        $value
     * @param string $class
     * @param        $bgcolor
     */
    private function td ( $value, $class = '', $bgcolor )
    {
        echo '<td class="' . $class . '" bgcolor="' . $bgcolor . '">' . $value . '</td>';
    }

    /**
     * @param        $value
     * @param string $class
     * @param        $bgcolor
     */
    private function td2 ( $value, $class = '', $bgcolor )
    {
        echo '<td colspan="2" class="' . $class . '" bgcolor="' . $bgcolor . '">' . $value . '</td>';
    }

    /**
     *
     */
    public function list_data ()
    {
    }
}