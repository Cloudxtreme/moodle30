<?php
	// return null to decline customization
	function  custom_make_shortname($crs_idnumber, $crs_title, $crs_code){
		// feel free to play with following... if you know what you are doing!
		$idnumber_arr=explode("_",$crs_idnumber);
		$ccode = $idnumber_arr[0];
		$term =  $idnumber_arr[1];
		$sec =   $idnumber_arr[2];
		return null;
	}
	// return null to decline customization
	function  custom_make_fullname($crs_idnumber,$crs_title,$crs_code) {
		// feel free to play with following... if you know what you are doing!
		$idnumber_arr=explode("_",$crs_idnumber);
		$ccode = $idnumber_arr[0];
		$term =  $idnumber_arr[1];
		$sec =   $idnumber_arr[2];
		return null;
	}
	// return null to decline customization
	function  custom_make_categoryname_from_term($termname) {
		// feel free to play with termname to generate custom category name
		// replace 'SU' and the like with full word
		//return (str_replace('SU',' Summer',str_replace('SP',' Spring',$termname))) ;
		return null;
	}
	// return null to decline customization
	function  custom_make_categoryname_from_idnumber($idnumber) {
		// feel free to play with idnumber to generate custom category name
		return null;
	}
	
?>