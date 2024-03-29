<?php
/**
 * @version		$Id: submissionslist.php,v 1.24 2013-06-11 18:32:46 juanca Exp $
 * @package		VPL. List student submissions of a VPL instances
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

class vpl_submissionlist_order{
	static $field;   //field to compare
	static $ascending; //value to return when ascending or descending order
	static $corder = null; //usort of old PHP versions don't call static class functions
	static public function cpm_userid($a,$b){ //Compare two submission fields
		if($a->userinfo->id < $b->userinfo->id ){
			return self::$ascending;
		}else{
			return -self::$ascending;
		}
	}
	static public function cpm_userinfo($a,$b){ //Compare two userinfo fields
		$field = self::$field;
		$adata = $a->userinfo->$field;
		$bdata = $b->userinfo->$field;
		if($adata == $bdata) {
			return self::cpm_userid($a,$b);
		}
		if(is_string($adata) && function_exists('collatorlib::compare')){
			return (collatorlib::compare($adata, $bdata))*(self::$ascending);
		}
		if($adata < $bdata){
			return self::$ascending;
		}else{
			return -self::$ascending;
		}
	}
	static public function cpm_submission($a,$b){ //Compare two submission fields
		$field = self::$field;
		$submissiona = $a->submission;
		$submissionb = $b->submission;
		if($submissiona == $submissionb){
			return self::cpm_userid($a,$b);
		}
		if($submissiona == null){
			return self::$ascending;
		}
		if($submissionb == null){
			return -self::$ascending;
		}
		$adata = $submissiona->get_instance()->$field;
		$bdata = $submissionb->get_instance()->$field;
		if($adata === null){
			return self::$ascending;
		}
		if($bdata === null){
			return -self::$ascending;
		}
		if($adata == $bdata) {
			return self::cpm_userid($a,$b);
		}elseif($adata < $bdata){
			return self::$ascending;
		}else{
			return -self::$ascending;
		}
	}

	/**
	 * Check and set data to sort return comparation function
	 * $field field to compare
	 * $descending order
	 * @return function
	 */
	static public function set_order($field,$ascending = true){
		if(self::$corder === null){
			self::$corder = new vpl_submissionlist_order;
		}
		$userinfofields = array('firstname'=>0,'lastname'=>0);
		$submissionfields = array('datesubmitted'=>0,'gradesortable'=>0,'grader'=>0,'dategraded'=>0,'nsubmissions'=>0);
		self::$field = $field;
		if($ascending){
			self::$ascending = -1;
		}else{
			self::$ascending = 1;
		}
		//usort of old PHP versions don't call static class functions
		if(isset($userinfofields[$field])){
			return array(self::$corder,'cpm_userinfo');
		}elseif(isset($submissionfields[$field])){
			return array(self::$corder,'cpm_submission');
		}else{
			self::$field = 'firstname';
			return array(self::$corder,'cpm_userinfo');
		}
	}
}
function vpl_evaluate($vpl,$all_data,$userinfo,$nevaluation,$groups_url){
	global $OUTPUT;
   	$nevaluation++;
	try{
		echo '<h2>'.s(get_string('evaluating',VPL)).'</h2>';
		$text =  $nevaluation.'/'.count($all_data);
		$text .= ' '.$vpl->user_picture($userinfo);
		$text .= ' '.fullname($userinfo);
		$text .= ' <a href="'.$groups_url.'">'.get_string('cancel').'</a>';
		echo $OUTPUT->box($text);
		$id=$vpl->get_course_module()->id;
		$userid=$userinfo->id;
		$ajaxurl="../forms/edit.json.php?id={$id}&userid={$userinfo->id}&action=";
		$url=vpl_url_add_param($groups_url,'evaluate',optional_param('evaluate', 0, PARAM_INT));
		$url=vpl_url_add_param($url,'nevaluation',$nevaluation);
		$nexturl=str_replace('&amp;','&',urldecode($url));
		vpl_editor_util::generateEvaluateScript($ajaxurl,$nexturl);
	}catch(Exception $e){
		echo $OUTPUT->box($e->getMessage());
	}
	$vpl->print_footer();
	die;
}

function vpl_submissionlist_arrow($burl, $sort, $selsort, $seldir){
	global $OUTPUT;
	$newdir = 'down';
	$url = vpl_url_add_param($burl,'sort',$sort);
	if($sort == $selsort){
		$sortdir = $seldir;
		if($sortdir == 'up'){
			$newdir = 'down';
		}elseif($sortdir == 'down'){
			$newdir = 'up';
		}
	}else{
		$sortdir = 'move';
	}
	$url = vpl_url_add_param($url,'sortdir',$newdir);
	return ' <a href="'.$url.'">'.($OUTPUT->pix_icon('t/'.$sortdir,get_string($sortdir))).'</a>';
}

require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->dirroot.'/mod/vpl/locallib.php';
require_once $CFG->dirroot.'/mod/vpl/vpl.class.php';
require_once $CFG->dirroot.'/mod/vpl/vpl_submission_CE.class.php';

require_login();

$id = required_param('id', PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);
$evaluate = optional_param('evaluate', 0, PARAM_INT);
$nevaluation = optional_param('nevaluation', 0, PARAM_INT);
$sort = vpl_get_set_session_var('subsort','lastname', 'sort');
$sortdir = vpl_get_set_session_var('subsortdir','move', 'sortdir');
$subselection = vpl_get_set_session_var('subselection','allsubmissions','selection');
if($evaluate>0){
	require_once $CFG->dirroot.'/mod/vpl/editor/editor_utility.php';
	vpl_editor_util::generate_requires_evaluation();
}
$vpl = new mod_vpl($id);
$vpl->prepare_page('views/submissionslist.php',array('id' => $id));

$course = $vpl->get_course();
$cm = $vpl->get_course_module();
$context_module = $vpl->get_context();
$vpl->require_capability(VPL_GRADE_CAPABILITY);
$vpl->add_to_log('view all submissions'.($evaluate>0?'(evaluate)':''), vpl_rel_url('views/submissionslist.php','id',$id), $vpl->get_printable_name());
//Print header
$vpl->print_header(get_string('submissionslist',VPL));
$vpl->print_view_tabs(basename(__FILE__));
@ob_flush();
flush();
// find out current groups mode
$groupmode = groups_get_activity_groupmode($cm);
if(!$groupmode){
	$groupmode = groups_get_course_groupmode($vpl->get_course());
}
//get graders
$graders = $vpl->get_graders();
$gradeable=$vpl->get_grade() != 0;
//get students
$currentgroup = groups_get_activity_group($cm, true);
if(!$currentgroup){
	$currentgroup='';
}
$list = $vpl->get_students($currentgroup);
$submissions = $vpl->all_last_user_submission();
$submissions_number = $vpl->get_submissions_number();
//Get all information
$all_data = array();
foreach ($list as $userinfo) {
	if($vpl->is_group_activity() && $userinfo->id != $vpl->get_group_leaderid($userinfo->id)){
		continue;
	}
	$submission = null;
	if(!isset($submissions[$userinfo->id])){
		if($subselection != 'all'){
			continue;
		}
		$submission = null;
	}
	else{
		$subinstance = $submissions[$userinfo->id];
		$submission = new mod_vpl_submission_CE($vpl,$subinstance);
		$subid=$subinstance->id;
		$subinstance->gradesortable = null;
		if($subinstance->dategraded>0){
			if($subselection == 'notgraded'){
				continue;
			}
			if($subselection == 'gradedbyuser' && $subinstance->grader != $USER->id){
				continue;
			}
			//TODO REUSE showing 
			$subinstance->gradesortable = $subinstance->grade;
		}else{
			$subinstance->grade = null;
			if($subselection == 'graded' ||$subselection == 'gradedbyuser'){
				continue;
			}
			//TODO REUSE showing 
			$result=$submission->getCE();
			if($result['executed']!==0){
				$prograde=$submission->proposedGrade($result['execution']);
				if($prograde>''){
					$subinstance->gradesortable=$prograde;
				}
			}
		}
		//I know that subinstance isn't the correct place to put nsubmissions but is the easy 
		if(isset($submissions_number[$userinfo->id])){
			$subinstance->nsubmissions = $submissions_number[$userinfo->id]->submissions;
		}else{
			$subinstance->nsubmissions = ' ';
		}
		
	}
	$data = new stdClass();
	$data->userinfo = $userinfo;
	$data->submission = $submission;
	//When group activity => change leader object lastname to groupname for order porpouse 
	if($vpl->is_group_activity()){
		$data->userinfo->firstname = '';
		$data->userinfo->lastname = $vpl->fullname($userinfo);
	}
	$all_data[] = $data;
}
$groups_url =vpl_mod_href('views/submissionslist.php','id',$id,'sort',$sort,'sortdir',$sortdir,'selection',$subselection);
//Unblock user session
session_write_close();

$base_url = vpl_mod_href('views/submissionslist.php','id',$id,'group',$group);

$firstname = get_string('firstname').vpl_submissionlist_arrow($base_url,'firstname',$sort,$sortdir);
$lastname  = get_string('lastname').vpl_submissionlist_arrow($base_url,'lastname',$sort,$sortdir);
if ($CFG->fullnamedisplay == 'lastname firstname') { // for better view (dlnsk)
	$namesortselect = $lastname.' / '.$firstname;
} else {
	$namesortselect = $firstname.' / '.$lastname;
}
if($vpl->is_group_activity()){
	$namesortselect = get_string('group').vpl_submissionlist_arrow($base_url,'lastname',$sort,$sortdir);
}
$options = array('height' => 550, 'width' => 780, 'directories' =>0, 'location' =>0, 'menubar'=>0,
		'personalbar'=>0,'status'=>0,'toolbar'=>0);
//Load strings
$strsubtime	= get_string('submittedon',VPL).vpl_submissionlist_arrow($base_url,'datesubmitted',$sort,$sortdir);
$strgrade	= get_string('grade').vpl_submissionlist_arrow($base_url,'gradesortable',$sort,$sortdir);
$strgrader	= get_string('grader',VPL).vpl_submissionlist_arrow($base_url,'grader',$sort,$sortdir);
$strgradedon = get_string('gradedon',VPL).vpl_submissionlist_arrow($base_url,'dategraded',$sort,$sortdir);
$hrefnsub = vpl_mod_href('views/activityworkinggraph.php','id',$id);
$action = new popup_action('click',$hrefnsub,'activityworkinggraph'.$id,$options);
$linkworkinggraph = $OUTPUT->action_link($hrefnsub, get_string('submissions',VPL),$action);
$strsubmisions = $linkworkinggraph.vpl_submissionlist_arrow($base_url,'nsubmissions',$sort,$sortdir);
$table = new html_table();

if($gradeable){
	$table->head  = array ('','',$namesortselect, $strsubtime,$strsubmisions,$strgrade,$strgrader,$strgradedon);
	$table->aling = array ('right','left','left', 'right','right','right','right','left');
}else{
	$table->head  = array ('','',$namesortselect, $strsubtime,$strsubmisions);
	$table->aling = array ('right','left','left', 'right', 'right');
}
$table->size = array ('','','60px','');
//Sort by sort field
usort($all_data,vpl_submissionlist_order::set_order($sort,$sortdir != 'up'));
$show_photo = count($all_data)<100;
$evaluationchoise=0;
$usernumber=0;
$ngrades = array();   //Number of revisions made by teacher
$next_ids = array();  //Information to get next user in list
$last_id=0;           //Last id for next
foreach ($all_data as $data) {
	$userinfo = $data->userinfo;
	if($data->submission == null){
		$text = get_string('nosubmission',VPL);
		$hrefview=vpl_mod_href('forms/submissionview.php','id',$id,
				'userid',$userinfo->id,'inpopup',1);
		//TODO clean comment
		$action = new popup_action('click', $hrefview,'viewsub'.$userinfo->id,$options);
		$subtime = $OUTPUT->action_link($hrefview, $text,$action);
		//$subtime = '<a href="'.$hrefview.'">'.$text.'</a>';
		$prev = '';
		$grade ='';
		$grader ='';
		$gradedon ='';
	}
	else{
		$submission = $data->submission;
		$subinstance = $submission->get_instance();
		$hrefview=vpl_mod_href('forms/submissionview.php','id',$id,
				'userid',$subinstance->userid,'inpopup',1);
		$hrefprev=vpl_mod_href('views/previoussubmissionslist.php','id',$id,
				'userid',$subinstance->userid,'inpopup',1);
		$hrefgrade=vpl_mod_href('forms/gradesubmission.php','id',$id,
				'userid',$subinstance->userid,'inpopup',1);
		//TODO clean comment
		$subtime = $OUTPUT->action_link($hrefview,
			userdate($subinstance->datesubmitted));
		if($subinstance->nsubmissions>0){
			$prev = $OUTPUT->action_link($hrefprev,
			$subinstance->nsubmissions);
		}else{
			$prev='';
		}
		$subid=$subinstance->id;
		if($evaluate == 4 && $nevaluation <= $usernumber){ //Need evaluation
		   	vpl_evaluate($vpl,$all_data,$userinfo,$usernumber,$groups_url);
		}
		if($subinstance->dategraded>0){
			$text = $submission->print_grade_core();
			//Add propossed grade diff
			$result=$submission->getCE();
			if($result['executed']!==0){
				$prograde=$submission->proposedGrade($result['execution']);
				if($prograde>'' && $prograde != $subinstance->grade){
					$text.= ' ('.$prograde.')';
				}
			}
			$text = '<div id="g'.$subid.'">'.$text.'</div>';
			if($subinstance->grader == $USER->id){
				$action = new popup_action('click', $hrefgrade,'gradesub'.$userinfo->id,$options);
				$grade = $OUTPUT->action_link($hrefgrade,$text,$action);
				//Add new next user
				if($last_id){
					$next_ids[$last_id]=$userinfo->id;
				}
				$last_id=$subid; //Save submission id as next index
			}else{
				$grade = $text;
			}
			
			$graderid=$subinstance->grader;
			$graderuser = $submission->get_grader($graderid);
			//Count evaluator marks
			if(isset($ngrades[$graderid])){
				$ngrades[$graderid]++;
			}else{
				$ngrades[$graderid]=1;
			}
			$grader = fullname($graderuser);
			$gradedon = userdate($subinstance->dategraded);
		}else{
			$result=$submission->getCE();
			$text='';
			if(($evaluate == 1 && $result['compilation'] === 0)||
			   ($evaluate == 2 && $result['executed'] === 0 && $nevaluation <= $usernumber) ||
			   ($evaluate == 3 && $nevaluation <= $usernumber)){ //Need evaluation
			   	vpl_evaluate($vpl,$all_data,$userinfo,$usernumber,$groups_url);
			}
			if($result['executed']!==0){
				$prograde=$submission->proposedGrade($result['execution']);
				if($prograde>''){
					$text=get_string('proposedgrade',VPL,$submission->print_grade_core($prograde));
				}
			}
			if($text ==''){
				$text=get_string('nograde');
			}
			$action = new popup_action('click', $hrefgrade,'gradesub'.$userinfo->id,$options);
			$text = '<div id="g'.$subid.'">'.$text.'</div>';
			$grade = $OUTPUT->action_link($hrefgrade,$text,$action);
			$grader = '&nbsp;';
			$gradedon = '&nbsp;';
			//Add new next user
			if($last_id){
				$next_ids[$last_id]=$userinfo->id;
			}
			$last_id=$subid; //Save submission id as next index
		}
		//Add div id to submission info
		$grader ='<div id="m'.$subid.'">'.$grader.'</div>';
		$gradedon ='<div id="o'.$subid.'">'.$gradedon.'</div>';
	}
	
	$usernumber++;
	if($gradeable){
		$table->data[] = array ($usernumber,
				$show_photo?$vpl->user_picture($userinfo):'',
				fullname($userinfo),
				$subtime,
				$prev,
				$grade,
				$grader,
				$gradedon);
	}else{
		$table->data[] = array ($usernumber,
				$show_photo?$vpl->user_picture($userinfo):'',
				fullname($userinfo),
				$subtime,
				$prev);
	}
}
if(count($ngrades)){
	if ($CFG->fullnamedisplay == 'lastname firstname') { // for better view (dlnsk)
		$namehead = get_string('lastname').' / '.get_string('firstname');
	} else {
		$namehead = get_string('firstname').' / '.get_string('lastname');;
	}
	$tablegraders = new html_table();
	$tablegraders->head  = array ('#',$namehead, get_string('grade'));
	$tablegraders->align = array ('right','left', 'center');
	$tablegraders->wrap = array ('nowrap','nowrap','nowrap');
	$tablegraders->data = array();
	$gradernumber=0;
	foreach($ngrades as $graderid => $marks){
		$gradernumber++;
		$grader = mod_vpl_submission::get_grader($graderid);
		$picture='';
		if($graderid>0){ //No automatic grading
			$picture = $OUTPUT->user_picture($grader,array('popup'=> true));
		}
		$tablegraders->data[] = array($gradernumber,$picture.' '.fullname($grader),
		sprintf('%d/%d  (%5.2f%%)',$marks,$usernumber,(float)100.0*$marks/$usernumber));
	}
}
//Menu for groups
if ($groupmode) {
	groups_print_activity_menu($cm, $groups_url);
}
//Print user selection by submission state
$url_base=$CFG->wwwroot."/mod/vpl/views/submissionslist.php?id=$id&sort=$sort&group=$group&selection=";
$urlindex=vpl_select_index($url_base, array('all','allsubmissions','notgraded', 'graded', 'gradedbyuser'));
$urls=array_merge(array($url_base.'all' => get_string('all')),
				vpl_select_array($url_base,array('allsubmissions','notgraded', 'graded', 'gradedbyuser')));
$url_sel = new url_select($urls,$urlindex[$subselection]);
$url_sel->set_label(get_string('submissionselection',VPL));
echo $OUTPUT->render($url_sel);
if(($gradeable || $vpl->get_instance()->evaluate) && $subselection != 'notgraded' ){
	$url_base=$CFG->wwwroot."/mod/vpl/views/submissionslist.php?id=$id&sort=$sort&sortdir=$sortdir&selection=$subselection&evaluate=";
	$urls=array(0 => null, 2 => $url_base.'2', '3' => $url_base.'3', 4 => $url_base.'4');	
	$url_sel = new url_select(array($urls[2] => get_string('notexecuted',VPL),
						$urls[3] => get_string('notgraded',VPL),
						$urls[4] => get_string('all')),$urls[$evaluate]);
	$url_sel->set_label(get_string('evaluate',VPL));
	echo $OUTPUT->render($url_sel);
}
echo '<br />';
@ob_flush();
flush();
echo html_writer::table($table);
if(count($ngrades)>0){
	echo '<br />';
	echo html_writer::table($tablegraders);
}
//Generate next info as <div id="submissionid">nextuser</div>
if(count($next_ids)){
	//Hide info
	echo '<div style="display:none;">';
	foreach($next_ids as $subid => $next_user){
		echo '<div id="n'.$subid.'">'.$next_user.'</div>';
	}
	echo '</div>';
}
$vpl->print_footer();
?>