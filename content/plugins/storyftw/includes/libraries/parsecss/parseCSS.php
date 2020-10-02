<?php
/*

 this class parses CSS Files/Strings and returns associative array
 containing all css parameters.
 it can also be called to generate css from associative array
 -- fixed in 0.2
 -- selectors to lowercase (html is case-insensitiv, xml case sensitiv)
 -- attributest from duplicate selectors now are combined
 -- white spaces are no longer trimed (e.g. border: 1px solid black)

@version        0.2

@example

    $oCSS=new CSSparse();
    $oCSS->parseFile("style.css");
    echo $oCSS["body"]["font-family"];
    $oCSS->css["body"]["background-image"]="url(background2.gif)";
    $newcss_string=$oCSS->buildcss();

@author     Michael Ettl(michael@ettl.com)

*/

class CSSparse
{

# associative array containing css-tags $this->CSS["body"]["background-color"]
var $css;

# array containing all css-tags
var $csstags;

# CSS String
var $cssstr;

	function __construct() {
	/* Init */
		$this->css="";
		$this->csstags="";
		$this->cssstr="";
	}

    function parseFile($filename)  {
	/* Open File and Parse it */
    	$fp=fopen($filename,"r") or die("Error opening file $filename");
    	$css_str = fread($fp, filesize ($filename));
    	fclose($fp);
		return($this->parse($css_str));
    }

	function parse($css_str) {
	 	// Parse CSS to Array
		$this->cssstr  = $css_str;
		$this->css     = array();
		$this->csstags = array();
		$css_str       = preg_replace( "/[\r\n]+/","", $css_str );
		$css_class     = explode( "}", $css_str );

    	while (list($key,$val) = each($css_class))
    	{

			$val = preg_replace( "~\/\*.*\*\/~", '', $val );
			$aCSSObj=explode("{",$val);
			$cSel=strtolower(trim($aCSSObj[0]));

			if ($cSel) {
				// echo '<xmp>$cSel: '. print_r( $cSel, true ) .'</xmp>';
    			$this->csstags[]=$cSel;
   			// echo "<pre>".$aCSSObj[0]."</pre>\n\n";
        	    $a=explode(";",$aCSSObj[1]);
        	    while(list($key,$val0) = each ($a))
        	    {
        		  if(trim($val0))
        		  {
   	 		   // echo "<pre>\t$key:$val0</pre>\n";
        	       $aCSSSub=explode(":",$val0);
        	       $cAtt=strtolower(trim($aCSSSub[0]));
        	       if (isset($aCSSSub[1])) {$aCSSItem[$cAtt]=trim($aCSSSub[1]);}
        	      }
        	    }
        	    if ((isset($this->css[$cSel])) && ($this->css[$cSel]))
        	    	$aCSSItem=array_merge($this->css[$cSel],$aCSSItem);
        	    $this->css[$cSel]=$aCSSItem;
        	    unset($aCSSItem);
			}
			if (strstr($cSel,",")) {
				 // there is a comma - duplicate tag name and delete original tag
				$aTags=explode(",",$cSel);
				foreach($aTags as $key0 => $value0) {
					$this->css[$value0]=$this->css[$cSel];
				}
				unset($this->css[$cSel]);
			}
    	}
    	unset($css_str,$css_class,$aCSSSub,$aCSSItem,$aCSSObj);
    	return $this->css;
    }

	function buildcss() {
	 	// Builds CSS on Base of Array
		$this->cssstr="";
		foreach($this->css as $key0 => $value0) {
			$this->cssstr .= "$key0 {\n";
			foreach($this->css[$key0] as $key1 => $value1) {
				$this->cssstr .= "\t$key1:$value1;\n";
			}
			$this->cssstr .="}\n";
		}
		return ($this->cssstr);
	}
}
