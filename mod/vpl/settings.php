<?php 
/**
 * @version		$Id: settings.php,v 1.26 2012-09-24 15:16:36 juanca Exp $
 * @package		VPL. Module common settings
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot.'/mod/vpl/lib.php';
$kbyte = 1024;
$megabyte=1024*$kbyte;
$gigabyte= 1024*$megabyte;
$minute = 60;
$list_maxfilesize = vpl_get_select_sizes(64*$kbyte,vpl_get_max_post_size());
$list_maxtime = vpl_get_select_time(120*$minute);
$list_maxexefilesize = vpl_get_select_sizes(16*$megabyte); //Start value
$list_maxexememory = vpl_get_select_sizes(16*$megabyte); //Start value

$default_maxfilesize = vpl_get_array_key($list_maxfilesize,$megabyte);
$default_maxtime = vpl_get_array_key($list_maxtime,16*$minute);
$default_maxexefilesize = vpl_get_array_key($list_maxexefilesize,128*$megabyte);
$default_maxexememory = vpl_get_array_key($list_maxexememory,512*$megabyte);
$default_maxexeprocesses = 200;

$default_defaultfilesize = vpl_get_array_key($list_maxfilesize,64*$kbyte);
$default_defaulttime = vpl_get_array_key($list_maxtime,4*$minute);
$default_defaultexefilesize = vpl_get_array_key($list_maxexefilesize,64*$megabyte);
$default_defaultexememory = vpl_get_array_key($list_maxexememory,64*$megabyte);
$default_defaultexeprocesses = 100;

$settings->add(new admin_setting_heading('heading1','',get_string('maxresourcelimits',VPL)));
$settings->add(new admin_setting_configselect('vpl_maxfilesize', get_string('maxfilesize', VPL)
	,get_string('maxfilesize', VPL), $default_maxfilesize, $list_maxfilesize));
$settings->add(new admin_setting_configselect('vpl_maxexetime', get_string('maxexetime', VPL)
	,get_string('maxexetime', VPL), $default_maxtime, $list_maxtime));
$settings->add(new admin_setting_configselect('vpl_maxexefilesize', get_string('maxexefilesize', VPL)
	,get_string('maxexefilesize', VPL), $default_maxexefilesize, $list_maxexefilesize));
$settings->add(new admin_setting_configselect('vpl_maxexememory', get_string('maxexememory', VPL)
	,get_string('maxexememory', VPL), $default_maxexememory, $list_maxexememory));
$settings->add(new admin_setting_configtext('vpl_maxexeprocesses', get_string('maxexeprocesses', VPL)
	,get_string('maxexeprocesses', VPL),$default_maxexeprocesses, PARAM_INT ,4));

$settings->add(new admin_setting_heading('headingd','',get_string('defaultresourcelimits',VPL)));
$name='defaultfilesize';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL)
	,get_string($name, VPL), $default_defaultfilesize, $list_maxfilesize));
$name='defaultexetime';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL)
	,get_string($name, VPL), $default_defaulttime, $list_maxtime));
$name='defaultexefilesize';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL)
	,get_string($name, VPL), $default_defaultexefilesize, $list_maxexefilesize));
$name='defaultexememory';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL)
	,get_string($name, VPL), $default_defaultexememory, $list_maxexememory));
$name='defaultexeprocesses';
$settings->add(new admin_setting_configtext('vpl_'.$name, get_string($name, VPL)
	,get_string($name, VPL),$default_defaultexeprocesses, PARAM_INT ,4));
                   
$settings->add(new admin_setting_heading('heading2','',get_string('jail_servers_config',VPL)));
$default = "#This server is only for test use. "
			."Install your own Jail server and remove the following line as soon as possible\n".
			'http://demojail.dis.ulpgc.es';
$settings->add(new admin_setting_configtextarea('vpl_jail_servers',
               get_string('jail_servers', VPL),get_string('jail_servers_description', VPL),$default));
$settings->add(new admin_setting_configcheckbox('vpl_acceptcertificates', get_string('acceptcertificates', VPL),
                       get_string('acceptcertificates_description', VPL), 1));
$settings->add(new admin_setting_heading('heading3','',get_string('miscellaneous')));
$list = vpl_get_select_time();
$default = vpl_get_array_key($list,60);
$settings->add(new admin_setting_configselect('vpl_discard_submission_period',
               get_string('discard_submission_period', VPL),
               get_string('discard_submission_period_description', VPL),$default,$list));
               
?>
