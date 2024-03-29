<?php
/**
 * @version		$Id: tokenizer_scala.class.php,v 1.2 2013-06-11 18:31:10 juanca Exp $
 * @package		VPL. Scala programing language tokenizer class
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Lang Michael <michael.lang.ima10@fh-joanneum.at>
 * @author		Lückl Bernd <bernd.lueckl.ima10@fh-joanneum.at>
 * @author		Lang Johannes <johannes.lang.ima10@fh-joanneum.at>
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/tokenizer_base.class.php';

class vpl_tokenizer_scala extends vpl_tokenizer_base{
	const regular=0;
	const in_string=1;
	const in_char=2;
	const in_comment=3;
	const in_linecomment=4;
	const in_number=5;
	protected static $c_reserved=null;
	protected $line_number;
	protected $tokens;
	protected function is_indentifier($text){
		if(strlen($text)==0){
			return false;
		}
		$first=$text{0};
		return ($first >= 'a' && $first <= 'z') ||
					($first >= 'A' && $first <= 'Z') ||
					$first=='_';
	}
	protected function is_number($text){
		if(strlen($text)==0){
			return false;
		}
		$first=$text{0};
		return $first >= '0' && $first <= '9';
	}
	
	protected function add_pending(&$pending){
		if($pending <= ' '){
			$pending = '';
			return;
		}
		if($this->is_indentifier($pending)){
			if(isset($this->reserved[$pending])){
				$type=vpl_token_type::reserved;
			}else{
				$type=vpl_token_type::identifier;
			}
		}else{
			if($this->is_number($pending)){
				$type=vpl_token_type::literal;
			}else{
				$type=vpl_token_type::operator;
			}
		}
		$this->tokens[] = new vpl_token($type,$pending,$this->line_number);
		$pending='';
	}
	function __construct(){
		if(self::$c_reserved === null){
			self::$c_reserved= array('abstract' => true,
									'case' => true,
									'catch' => true,
									'class' => true,
									'def' => true,
									'do' => true,
									'else' => true,
									'extends' => true,
									'false' => true,
									'final' => true,
									'finally' => true,
									'for' => true,
									'forSome' => true,
									'if' => true,
									'implicit' => true,
									'import' => true,
									'lazy' => true,
									'match' => true,
									'new' => true,
									'null' => true,
									'object' => true,
									'override' => true,
									'package' => true,
									'private' => true,
									'protected' => true,
									'return' => true,
									'sealed' => true,
									'super' => true,
									'this' => true,
									'throw' => true,
									'trait' => true,
									'try' => true,
									'true' => true,
									'type' => true,
									'val' => true,
									'var' => true,
									'while' => true,
									'with' => true,
									'yield' => true,			
									
									'Byte' => true,
									'Short' => true,
									'Char' => true,
									'Int' => true,
									'Long' => true,
									'Float' => true,
									'Double' => true,
									'Boolean' => true,
									'Unit' => true,
									'String' => true);	
		}
		$this->reserved=&self::$c_reserved;
		parent::__construct();
	}
	

	function parse($filedata){
		$this->tokens=array();
		$this->line_number=1;
		$state = self::regular;
		$pending='';
		$first_no_space = '';
		$last_no_space = '';
		$l = strlen($filedata);
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
				$this->line_number++;
			}
			if($current == self::CR){
				if($next == self::LF) {
					continue;
				}else{
					$this->line_number++;
					$current = self::LF;
				}
			}
			if($current != ' ' && $current != "\t") {//Keep first and last no space char
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
							$i++;
							$state=self::regular;
							continue;
						}
					}
					break;
				case self::in_linecomment:
					// Check end of comment
					if($current==self::LF){
						$state=self::regular;
					}
					break;
				case self::in_string:
					// Check end of string
					if($current=='"' && $previous!='\\') {
						$state = self::regular;
						break;
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
						$state = self::regular;
						break;
					}
					//discard two backslash
					if($current=='\\' && $previous=='\\'){
						$current=' ';
					}
					break;
				case self::in_number:
					if(($current >= '0' && $current <= '9') ||
					    $current == '.' || $current == 'E' || $current == 'e'){
						$pending .= $current;
						continue;
					}
					if(($current == '-' || $current == '+') && ($previous == 'E' || $previous == 'e')){
						$pending .= $current;
						continue;
					}
					$this->add_pending($pending);
					$state = self::regular;
					//Process current as regular
				case self::regular:
					if($current == '/') {
						if($next == '*') { // Begin block comments
							$state = self::in_comment;
							$this->add_pending($pending);
							$i++;
							continue;
						}
						if($next == '/'){ // Begin line comment
							$state = self::in_linecomment;
							$this->add_pending($pending);
							$i++;
							continue;
						}
					}elseif($current == '"')	{
						$state = self::in_string;
						$this->add_pending($pending);
						break;
					}elseif($current == "'"){
						$state = self::in_char;
						$this->add_pending($pending);
						break;
					} elseif($current >= '0' && $current <= '9'){
						$state = self::in_number;
						$this->add_pending($pending);
						$pending = $current;
						break;
					}
					if(($current >= 'a' && $current <= 'z') ||
					($current >= 'A' && $current <= 'Z') ||
					$current=='_' || ord($current) > 127){
						$pending .= $current;
					} else {
						$this->add_pending($pending);
						if($current >' '){
							$this->add_pending($current);
						}
					}
			}
		}
		$this->add_pending($pending);
		$this->compact_operators();
	}
	function get_tokens(){
		return $this->tokens;
	}
	function compact_operators(){
		$correct = array();
		$current = false;
		foreach($this->tokens as &$next){
			if($current){
				if($current->type == vpl_token_type::operator
				   && $next->type == vpl_token_type::operator
				   && strpos('()[]{};',$current->value) === false){
				   	$current->value .= $next->value;
				   	$next=false;
				}
				$correct[] = $current;
			}
			$current = $next;
		}
		if($current){
			$correct[] = $current;
		}
		$this->tokens = $correct;
	}
	function show_tokens(){
		foreach($this->tokens as $token){
			$token->show();
		}
	}
}

?>