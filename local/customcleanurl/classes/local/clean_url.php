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

use local_customcleanurl\local\UtilCleanUrlHelper;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * class to clean the default moodle url
 *
 * @package    local_customcleanurl
 * @copyright  2024 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_url
{

    /** @var moodle_url */
    private $originalurl;

    /** @var array [] */
    private $params;

    /** @var string */
    private $path;

    /** @var moodle_url */
    public $cleanedurl;

    // constructor
    public function __construct(moodle_url $url)
    {
        $this->originalurl = $url;
        $this->path = $this->originalurl->get_path(false);
        $this->params = $this->originalurl->params();
        $this->cleanedurl = null;
        $this->execute();
    }


    /**
     * execute
     */
    private function execute()
    {
        // check enable_customcleanurl
        $enable_customcleanurl = get_config('local_customcleanurl', 'enable_customcleanurl');
        if (!$enable_customcleanurl || empty($enable_customcleanurl)) {
            return;
        }
        $this->clean_path();
        $this->create_cleaned_url();
    }

    /**
     * remove index.php, .php or provided path string
     * @param string $remove_last_path
     */
    private function remove_index_php($remove_last_path = '')
    {
        // removed defined path from the end
        if ($remove_last_path) {
            if (substr($this->path, -strlen($remove_last_path)) == $remove_last_path) {
                return substr($this->path, 0, -strlen($remove_last_path));
            }
        }

        // Remove /index.php from end.
        if (substr($this->path, -10) == '/index.php') {
            return substr($this->path, 0, -10);
        }
        // remove .php
        if (substr($this->path, -4) == '.php') {
            return substr($this->path, 0, -4);
        }
    }

    // create_cleaned_url after the path is cleaned
    private function create_cleaned_url()
    {
        // Add back moodle path.
        $this->path = ltrim($this->path, '/');
        if ($this->path) {
            $this->path = "/" . $this->path;
            $originalpath = $this->originalurl->get_path(false);
            if ($this->path == $originalpath) {
                $this->cleanedurl = $this->originalurl;
                return; // URL was not rewritten. return original url
            }
            // 
            $this->cleanedurl = new moodle_url($this->path, $this->params);
            return;
        }
    }


    /** process to claan the default moodle url path */
    private function clean_path()
    {
        global $DB;
        $cleanurl_type = get_config('local_customcleanurl', 'cleanurl_type');
        $cleanurl_type = explode(",", $cleanurl_type);

        /**
         * For cleanurl_type = define_url 
         */
        if (in_array('define_url', $cleanurl_type)) {
            $check_custom_url_path = $DB->get_record('local_customcleanurl', ['default_url' => $this->path]);
            if ($check_custom_url_path) {
                $this->path = $check_custom_url_path->custom_url;
            }
        }

        /**
         * For cleanurl_type = course_url
         */
        if (in_array('course_url', $cleanurl_type)) {
            // url path start with /course
            if (preg_match('#^/course#', $this->path, $matches)) {
                $this->clean_course_url();
                return;
            }
        }

        /**
         * For cleanurl_type = user_url
         */
        if (in_array('user_url', $cleanurl_type)) {
            // url path start with /course
            if (preg_match('#^/user/profile.php#', $this->path, $matches)) {
                $this->clean_users_profile_url();
                return;
            }
        }
        // switch ($this->path) {
        //     case '/user/profile.php':
        //         // user profile clean url
        //         $this->clean_users_profile_url();
        //         return;
        // }
        // // course mod activity and resources
        // if (preg_match('#^/mod/(\w+)/view.php$#', $this->path, $matches)) {
        //     // clean_course_module_view($matches[1]);
        //     return;
        // }
    }

    /**
     * Used to convert following urls
     * 
     * /course/view.php?id={ID} => /course/{course_short_shortname}
     * 
     * /course/edit.php?id={ID} => /course/edit/{course_short_shortname}
     * 
     * /course/index.php = > /course
     * 
     * /course/index.php?categoryid={ID} =>/course/category/{ID}/{category_name}
     * 
     */
    private function clean_course_url()
    {
        $allowed_course_path = [
            '/course/view.php',
            '/course/edit.php',
            '/course/index.php'
        ];
        if (!in_array($this->path, $allowed_course_path)) {
            return;
        }
        global $DB;

        // params
        $course_id = isset($this->params['id']) ? $this->params['id'] : '';
        $category_id = isset($this->params['categoryid']) ? $this->params['categoryid'] : '';
        // filter paths
        $clean_newpath = $this->remove_index_php('/view.php');
        if ($course_id) {
            $course = $DB->get_record('course', ['id' => $course_id]);
            if ($course) {
                $clean_newpath = $clean_newpath . '/' . urlencode($course->shortname);
                if ($this->check_path_allowed($clean_newpath)) {
                    $this->path = $clean_newpath;
                }
            }
        } else if ($category_id) {
            $course_categories = $DB->get_record('course_categories', ['id' => $category_id]);
            if ($course_categories) {
                $clean_newpath = $clean_newpath . '/category/' . $course_categories->id . '/' . urlencode(strtolower($course_categories->name));
                if ($this->check_path_allowed($clean_newpath)) {
                    $this->path = $clean_newpath;
                }
            }
        }

        return false;
    }



    /**
     * clean user profile url 
     * /user/profile.php?id={ID}  => /user/profile/{username}
     */
    private function clean_users_profile_url()
    {
        if (empty($this->params['id'])) {
            return null;
        }

        global $DB;
        $user =  $DB->get_record('user', ['id' => $this->params['id']]);
        if ($user) {
            $clean_newpath = $this->remove_index_php();
            $clean_newpath = $clean_newpath . '/' . urlencode(strtolower($user->username));
            if ($this->check_path_allowed($clean_newpath)) {
                $this->path = $clean_newpath;
            }
        }
        return $user;
    }

    /**
     * check if the final clean process path matches to existing moodle dir or file
     * @param string $path 
     */
    private function check_path_allowed($path)
    {
        global $CFG;

        return (!is_dir($CFG->dirroot . $path) && !is_file($CFG->dirroot . $path . ".php"));
    }
}
