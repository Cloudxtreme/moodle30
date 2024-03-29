<?php
/**
 * @version		$Id: sh_base.class.php,v 1.18 2013-04-22 14:12:38 juanca Exp $
 * @package		vpl. vpl Syntaxhighlighters base class
 * @copyright	Copyright (C) 2009 Juan Carlos Rodríguez-del-Pino. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
 * @author		Juan Carlos Rodriguez-del-Pino
 **/

require_once dirname(__FILE__).'/../../../config.php';
class vpl_sh_base{
	const CR ="\r";
	const LF ="\n";
	const TAB ="\t";
	protected $reserved;
	protected $showln;
	protected $line_number;
	protected $file_name;
	protected $action_line;
	protected $hover_level;
	const c_function = 'vpl_f';
	const c_variable = 'vpl_v';
	const c_string = 'vpl_s';
	const c_comment = 'vpl_c';
	const c_macro = 'vpl_m';
	const c_reserved = 'vpl_r';
	const c_general = 'vpl_g';
	const c_hover = 'vpl_h';
	const c_linenumber = 'vpl_ln';
	const endTag= '</span>';
	protected function show_line_number(){
		if($this->showln){
			echo '<span class="'.self::c_linenumber.'">';
			$name = $this->file_name.'.'.$this->line_number;
			echo '<a name="',$name.'"></a>';
			$text = sprintf('%5d',$this->line_number);
			if($this->action_line) {
				echo '<a href="javascript:actionLine(\''.$name.'\')")>'.$text.'</a>';
			}
			else {
				echo $text;
			}
			echo ' </span>';
		}
		$this->line_number++;
	}

	protected function show_text($text){
		p($text);
	}

	protected function show_pending(&$rest){
		$this->show_text($rest);
		$rest='';
	}

	protected function initTag($class){
		echo '<span class="'.$class.'">';
	}
	protected function endTag(){
		echo '</span>';
	}

	protected function begin($filename,$showln=true){
		$this->hover_level=0;
		$this->showln=$showln;
		$this->file_name = $filename;
		$this->line_number=1;
		echo '<pre class="vpl_sh '.self::c_general.'">';
	}
	protected function end(){
		while($this->hover_level>0){
			$this->endHover();
		}
		echo '</pre>';
	}
	protected function initHover(){
		echo '<span class="'.self::c_hover.($this->hover_level<12?$this->hover_level:11).'">';
		$this->hover_level++;
	}
	protected function endHover(){
		$this->hover_level--;
		echo '</span>';
	}
	public function __construct(){

	}
	function print_file($filename, $filedata, $showln=true){
		$this->begin($filename,$showln);
		$pending='';
		$l = strlen($filedata);
		if($l){
			$this->show_line_number();
		}
		for($i=0;$i<$l;$i++){
			$current=$filedata[$i];
			if($i < ($l-1)) {
				$next = $filedata[$i+1];
			}else{
				$next ='';
			}
			if($current == self::CR){
				if($next == self::LF) {
					continue;
				}else{
					$current = self::LF;
				}
			}
			$pending .= $current;
			if($current == self::LF){
				$this->show_pending($pending);
				$this->show_line_number();
			}
		}
		$this->show_pending($pending);
		$this->end();
	}
}

?>