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
 * 
 * @package    local_customcleanurl
 * @copyright  2024 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

namespace local_customcleanurl\local;

use moodle_url;

defined('MOODLE_INTERNAL') || die();


/**
 * UtilCleanUrlHelper class for customcleanurl local
 *
 * @package    local_customcleanurl
 * @copyright  2024 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UtilCleanUrlHelper
{

    // check if the moodle default original url is present for the clean url or not. 
    // Then return the url if present
    public static function get_default_moodle_url()
    {
        global $DB;
        // check enable_customcleanurl
        $enable_customcleanurl = get_config('local_customcleanurl', 'enable_customcleanurl');
        if (!$enable_customcleanurl || empty($enable_customcleanurl)) {
            return;
        }
        $request_url =  $_SERVER['REQUEST_URI'];
        $request_moodle_url = new moodle_url($request_url);
        $request_path = $request_moodle_url->get_path(false);

        $parts = explode("/", trim($request_path, '/'));
        $unique_name = urldecode(end($parts));
        $response_path = '';

        $cleanurl_type = get_config('local_customcleanurl', 'cleanurl_type');
        $cleanurl_type = explode(",", $cleanurl_type);
        /**
         * For cleanurl_type = define_url 
         */
        if (in_array('define_url', $cleanurl_type)) {
            $check_custom_url_path = $DB->get_record('local_customcleanurl', ['custom_url' => $request_path]);
            if ($check_custom_url_path) {
                $response_path = $check_custom_url_path->default_url;
            }
        }


        /**
         * For cleanurl_type = course_url
         * /course/view.php?id={ID} => /course/{course_short_shortname}
         * /course/edit.php?id={ID} => /course/edit/{course_short_shortname}
         * /course/index.php = > /course
         * /course/index.php?categoryid={ID} =>/course/category/{ID}/{category_name}
         */
        if (in_array('course_url', $cleanurl_type) && !$response_path && $parts[0] === 'course') {
            $course = $DB->get_record('course', ['shortname' => $unique_name]);
            if ($course && sizeof($parts) == '2') {
                $response_path = "/course/view.php?id=" . $course->id;
            } elseif ($course && sizeof($parts) == '3' && $parts[1] === 'edit') {
                $response_path = "/course/edit.php?id=" . $course->id;
            } else if (sizeof($parts) == '4') {
                $course_categories = $DB->get_record('course_categories', ['id' => $parts[2]]);
                $response_path = "/course/index.php?categoryid=" . $course_categories->id;
            }
        }
        /**
         * For cleanurl_type = user_url
         * /user/profile.php?id={ID}  => /user/profile/{user_name}
         */
        if (in_array('user_url', $cleanurl_type) && !$response_path && $parts[0] === 'user') {
            $user = $DB->get_record('user', ['username' => $unique_name]);
            if ($user && sizeof($parts) == '3') {
                $response_path = "/user/profile.php?id=" . $user->id;
            }
        }

        /**
         * response_path
         */
        if ($response_path) {
            $request_param = $request_moodle_url->params();
            $url = new moodle_url($response_path);
            foreach ($url->params() as $k => $v) {
                if (array_key_exists($k, $request_param)) {
                    if (isset($_GET[$k])) {
                        echo "parameter \"" . $k . "\" is restricted as this parameter is alrady present in original url (" . $response_path . ").";
                        die;
                    }
                }
                $v = str_replace('+', ' ', $v);
                $_GET[$k] = $v;
            }
            return $url;
        }
        return false;
    }



    // urlrewriteclass initialize
    public static function urlrewriteclass_initialize()
    {
        global $CFG;
        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }
        $CFG->urlrewriteclass = '\\local_customcleanurl\\url_rewriter';
    }
}
