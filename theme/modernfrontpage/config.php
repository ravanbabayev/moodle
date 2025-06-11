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
 * Modern Frontpage Theme Config
 *
 * @package    theme_modernfrontpage
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'modernfrontpage';
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->parents = ['boost'];
$THEME->enable_dock = false;
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_modernfrontpage_process_css';

$THEME->layouts = array(
    'base' => array(
        'file' => 'drawers.php',
        'regions' => array(),
    ),
    'standard' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'course' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    'coursecategory' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'incourse' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'frontpage' => array(
        'file' => 'frontpage.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar' => true),
    ),
    'admin' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'mydashboard' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar' => true, 'langmenu' => true),
    ),
    'mypublic' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'login' => array(
        'file' => 'login.php',
        'regions' => array(),
        'options' => array('langmenu' => true, 'nonavbar' => true),
    ),
    'popup' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    'frametop' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array()
    ),
    'maintenance' => array(
        'file' => 'maintenance.php',
        'regions' => array(),
    ),
    'print' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => false),
    ),
    'redirect' => array(
        'file' => 'embedded.php',
        'regions' => array(),
    ),
    'report' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'secure' => array(
        'file' => 'secure.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre'
    )
);

$THEME->scss = function($theme) {
    return theme_modernfrontpage_get_main_scss_content($theme);
};

$THEME->extrascsscallback = 'theme_modernfrontpage_get_extra_scss';
$THEME->prescsscallback = 'theme_modernfrontpage_get_pre_scss';
$THEME->precompiledcsscallback = 'theme_modernfrontpage_get_precompiled_css';
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->usescommunication = true; 