<?php
/**
 * @version		$Id: testcasesfile.php,v 1.3 2013-06-10 08:15:42 juanca Exp $
 * @package		VPL. Edit test cases' file
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../editor/editor_utility.php';

vpl_editor_util::generate_requires();

require_login();
$id = required_param('id',PARAM_INT);

$vpl = new mod_vpl($id);
$instance = $vpl->get_instance();
$vpl->prepare_page('forms/testcasesfile.php', array('id' => $id));

$vpl->require_capability(VPL_MANAGE_CAPABILITY);
$fgp = $vpl->get_required_fgm();
$vpl->print_header(get_string('testcases',VPL));
$vpl->print_heading_with_help('testcases');
$vpl->print_configure_tabs(basename(__FILE__));

$options = Array();
$options['restrictededitor']=false;
$options['save']=true;
$options['run']=false;
$options['debug']=false;
$options['evaluate']=false;
$options['ajaxurl']="testcasesfile.json.php?id={$id}&action=";
$options['download']="../views/downloadexecutionfiles.php?id={$id}";
$options['resetfiles']=false;
$options['minfiles']=1;
$options['maxfiles']=1;
//Get files
$fgp = $vpl->get_execution_fgm();
$files = Array();
$filename='vpl_evaluate.cases';
$files[$filename]=$fgp->getFileData($filename);
session_write_close();
vpl_editor_util::print_tag($options,$files);
$vpl->print_footer_simple();
