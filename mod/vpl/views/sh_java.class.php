<?php
/**
 * @version		$Id: sh_java.class.php,v 1.7 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Syntaxhighlighter for Java language
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * vpl Syntaxhighlighter for Java code
 *
 * @author  Juan Carlos Rodriguez del Pino
 * @version $Id: sh_java.class.php,v 1.7 2012-06-05 23:22:09 juanca Exp $
 * @package vpl
 **/

require_once dirname(__FILE__).'/sh_c.class.php';

class vpl_sh_java extends vpl_sh_c{
	function __construct(){
		parent::__construct();
		$added = array( 'abstract' => true, 'boolean' => true, 'byte' => true, 'try' => true, 'catch' => true,
						'class' => true, 'extends' => true, 'final' => true, 'finally' => true,
						'implements' => true, 'import' => true, 'instanceof' => true, 'interface' => true,
						'native' => true, 'package' => true, 'strctfp' => true, 'super' => true,
						'synchronized' => true, 'new' => true, 'private' => true, 'protected' => true,
						'public' => true, 'this' => true, 'throw' => true, 'transient' => true,
						'true' => true, 'false' => true, 'null' => true);
		$this->reserved= array_merge($this->reserved, $added);
	}
	function print_file($filename, $filedata, $showln=true){
		$this->begin($filename,$showln);
		$state = self::regular;
		$pending='';
		$first_no_space = '';
		$last_no_space = '';
		$l = strlen($filedata);
		if($l){
			$this->show_line_number();
		}
		$current='';
		$previous='';
		for($i=0;$i<$l;$i++){
			$previous=$current;
			$current=$filedata[$i];
			if($i < ($l-1)) {
				$next = $filedata[$i+1];
			}else{
				$next ='';
			}
			if($previous == self::LF){
				$last_no_space='';
				$first_no_space = '';
			}
			if($current == self::CR){
				if($next == self::LF) {
					continue;
				}else{
					$current = self::LF;
				}
			}
			if($current != ' ' && $current != "\t") {//Keep first and last char
				if($current != self::LF){
					$last_no_space=$current;
				}
				if($first_no_space == ''){
					$first_no_space = $current;
				}
			}
			switch($state){
				case self::in_comment:
					// Check end of block comment
					if($current=='*') {
						if($next=='/') {
							$state = self::regular;
							$pending .= '*/';
							$this->show_text($pending);
							$pending='';
							$this->endTag();
							$i++;
							continue 2;
						}
					}
					if($current == self::LF){
						$this->show_text($pending);
						$pending='';
						if($this->showln) { //Check to send endtag
							$this->endTag();
						}
						$this->show_line_number();
						if($this->showln) { //Check to send initTagtag
							$this->initTag(self::c_comment);
						}
					}else{
						$pending .= $current;
					}
					break;
				case self::in_linecomment:
					// Check end of comment
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$state=self::regular;
					}else{
						$pending .= $current;
					}
					break;
				case self::in_macro:
					// Check end of macro
					if(!(($current >= 'a' && $current <= 'z') ||
					($current >= 'A' && $current <= 'Z') ||
					($current >= '0' && $current <= '9') ||
					$current=='_' || ord($current) > 127)){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						if($current == self::LF){
							$this->show_line_number();
						}else{
							$this->show_text($current);
						}
						$state = self::regular;
					}else{
						$pending .= $current;
					}
					break;
				case self::in_string:
					// Check end of string
					if($current=='"' && $previous!='\\') {
						$pending .= '"';
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$state = self::regular;
						break;
					}
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$this->initTag(self::c_string);
					}else{
						$pending .= $current;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::in_char:
					// Check end of char
					if($current=='\'' && $previous!='\\') {
						$pending .= '\'';
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$state = self::regular;
						break;
					}
					if($current==self::LF){
						$this->show_text($pending);
						$pending='';
						$this->endTag();
						$this->show_line_number();
						$this->initTag(self::c_string);
					}else{
						$pending .= $current;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::regular:
					if($current == '/') {
						if($next == '*') { // Begin block comments
							$state = self::in_comment;
							$this->show_pending($pending);
							$this->initTag(self::c_comment);
							$this->show_text('/*');
							$i++;
							continue 2;
						}
						if($next == '/'){ // Begin line comment
							$state = self::in_linecomment;
							$this->show_pending($pending);
							$this->initTag(self::c_comment);
							$this->show_text('//');
							$i++;
							continue 2;
						}
					}elseif($current == '"')	{
						$state = self::in_string;
						$this->show_pending($pending);
						$this->initTag(self::c_string);
						$this->show_text('"');
						break;
					}elseif($current == "'"){
						$state = self::in_char;
						$this->show_pending($pending);
						$this->initTag(self::c_string);
						$this->show_text('\'');
						break;
					} elseif($current == '@' && $first_no_space==$current){
						$state = self::in_macro;
						$this->show_pending($pending);
						$this->initTag(self::c_macro);
						$this->show_text('@');
						break;
					}
					if(($current >= 'a' && $current <= 'z') ||
					($current >= 'A' && $current <= 'Z') ||
					($current >= '0' && $current <= '9') ||
					$current=='_' || ord($current) > 127){
						$pending .= $current;
					} else {
						$this->show_pending($pending);
						if($current == '{' || $current == '(' || $current == '['){
							$this->initHover();
						}
						if($current == self::LF){
							$this->show_line_number();
						}else{
							$aux =$current;
							$this->show_pending($aux);
						}
						if($current == ')' || $current == '}' || $current == ']'){
							$this->endHover();
						}
					}
			}
		}

		$this->show_pending($pending);
		if($state != self::regular){
			$this->endTag();
		}
		$this->end();
	}
}

?>