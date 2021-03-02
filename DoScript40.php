<?php

class DoScript{

var $LoopZone=array();
var $FListaCampi=array();
var $DatiLoop=array();
var $FileInclude=array();

var $NomeFile;
var $FDatabase;
var $FUser;
var $FPass;
var $stdoutput;
var $ScriptValido;

var $NODO_TESTO="T";
var $NODO_LOOP="L";
var $NODO_INCLUDI="I";
var $NODO_IF="F";
var $NODO_ELSE="S";
var $NODO_QUERY="Q";
var $NODO_LABEL="B";
var $NODO_PARAMETRO="P";
var $percorsofile;

function DoScript(){
}

function stampa(){
	print "loop\n\r";
	print_r($this->LoopZone);
	print "Testo\n\r";
	print_r($this->TextSegNode);
}


function ValutaEspressione($espressione){

}

function Interpreta_XS($DomNode){

#echo "<pre>";print_r(debug_backtrace());echo "</pre>";

$risultato="";

   if (($DomNode) AND ($ChildDomNode = $DomNode->first_child())) {

       while ($ChildDomNode) {

			  switch ($ChildDomNode->node_name()){

				  case $this->NODO_IF:
					  $espressione=$ChildDomNode->get_attribute("e");
					  $espressionedebug=$espressione;
#				  print $espressione."<br>";
					  $espressione=(($this->sostituisciparametri($espressione,true,false)));
#					  print $espressione."<br>";
					  $r=@eval("\$risultatoeq=({$espressione});");

					  if ($r===false){
						printf("%s<br>",var_dump($espressionedebug));
						printf("%s<br>",var_dump($espressione));
					  }

					  if ($risultatoeq==TRUE){
						  $risultato.=$this->Interpreta_XS($ChildDomNode);
					  }else{
						  $ElseNode = $ChildDomNode->next_sibling();
						  while ($ElseNode) {

							if ($ElseNode->node_type() == XML_ELEMENT_NODE){
							  if ($ElseNode->node_name()==$this->NODO_ELSE){
								  $risultato.=$this->Interpreta_XS($ElseNode);
							  }
								  break;
							}
							$ElseNode=$ElseNode->next_sibling();
						  }
					  }

				  break;

				  case $this->NODO_TESTO:
						$risultato.=$this->sostituisciparametri($ChildDomNode->get_content());
				  break;

				  case $this->NODO_PARAMETRO:
					  $etichettaloop=$ChildDomNode->get_content();
					  $rigaloop=$ChildDomNode->get_attribute("l");
#					  echo "QUIIIII:$etichettaloop";
					  // Se ci sono loop ricorsivi allora da intrepretare come array
					  if (is_array($this->DatiLoop[$etichettaloop."@".$rigaloop])){
						  foreach ($this->DatiLoop[$etichettaloop."@".$rigaloop] as $key => $value){
							  $risultato .= $value;
						  }
					  } else {
#  					    echo "<pre>";print_r($this);echo "</pre>";
						$risultato .= $this->DatiLoop[$etichettaloop."@".$rigaloop];
					  }

/*
					  $etichettaloop=$ChildDomNode->get_content();
					  $rigaloop=$ChildDomNode->get_attribute("l");
					  if (is_array($this->DatiLoop[$etichettaloop."@".$rigaloop])){
#						  for($i=1;$i<count($this->DatiLoop[$etichettaloop])+1;$i++){
#	echo "etichettaloop:$etichettaloop<br>";
#	var_dump($this->DatiLoop[$etichettaloop]);
						  foreach($this->DatiLoop[$etichettaloop."@".$rigaloop] as $theDatiLoop){
#							  echo "I:$i<br>";
							  $risultato .= $theDatiLoop;
						  }
					  }else{
#						  echo "ddd<br>";
						$risultato .= $this->DatiLoop[$etichettaloop];
					  }
#					  echo $risultato;
*/
				  break;


				  case $this->NODO_INCLUDI:

                    $nomefile=$this->sostituisciparametri($ChildDomNode->get_content());

#					echo "nomefile:$nomefile";

					if ($this->FileInclude["$nomefile"]){
						$risultato.=$this->FileInclude["$nomefile"]->getRisultato();
					}
				  break;

				  default:break;
			  }
			if ($ChildDomNode){
				$ChildDomNode = $ChildDomNode->next_sibling();
			}
       }
       return $risultato;
   }
}

function TrovaLoop($DomNode){

#$LoopZone=array();
#print "1\n\r";
   if ($ChildDomNode = $DomNode->first_child()) {
#print "2\n\r";
	   if ( ($ChildDomNode->node_name()=="NWSCRIPT") AND ($ChildDomNode->has_child_nodes()) ) {
#print "3\n\r";
		   $ChildDomNode = $ChildDomNode->first_child();
			while ($ChildDomNode) {
#print $ChildDomNode->node_name()."\n\r";
				if ( ($ChildDomNode->node_name()=="LOOP") ){
					$ChildDomNode = $ChildDomNode->first_child();

#print "6\n\r";	

					while ($ChildDomNode) {
						
						if ($ChildDomNode->node_type() == XML_ELEMENT_NODE) {
#print "7\n\r";	

							if ($ChildDomNode->has_attributes()) {
								$nomeloop=$ChildDomNode->get_attribute("n");				   
							}else{
								$nomeloop=char(0);
							}

							$this->TrovaInclude($ChildDomNode);

							$this->LoopZone[$nomeloop]=$ChildDomNode;
							$this->DatiLoop[$nomeloop]="";

						}

						$ChildDomNode = $ChildDomNode->next_sibling();
					}

					break;
					
				}else{
				$ChildDomNode = $ChildDomNode->next_sibling();
				}
#print "5\n\r";	
			}
#print "4\n\r";
	   }
   }
}

function TrovaInclude($DomNode){
#$LoopZone=array();
#echo "<pre>";
#print "1\n\r";
#   if ($ChildDomNode = $DomNode->firstChild) {
   if (($DomNode) AND ($ChildDomNode = $DomNode->first_child())) {

#print "2\n\r";
#	   if ( ($ChildDomNode->nodeName=="NWSCRIPT") AND ($ChildDomNode->hasChildNodes()) ) {
#print "3\n\r";
#		   $ChildDomNode = $ChildDomNode->firstChild;
#			while ($ChildDomNode) {
#				if ( ($ChildDomNode->nodeName=="TEXTSEG") ){
					#$ChildDomNode = $ChildDomNode->firstChild;

#print "6\n\r";	

					while ($ChildDomNode) {
#print "6.0\n\r";	
#echo "NN:$ChildDomNode->nodeName<br>";
						if ($ChildDomNode->node_type() == XML_ELEMENT_NODE) {
#print "6.1\r\n";
#print $ChildDomNode->node_name()."<br>";
							switch ($ChildDomNode->node_name()){
							
								case $this->NODO_INCLUDI:
#print "6.2\r\n";
#								   echo $ChildDomNode->get_content();
									$nomefile=$this->sostituisciparametri($ChildDomNode->get_content());
									if ($nomefile){

										$script = new DoScript();

										$nomefilecompilato=$this->remove_ext($nomefile).".xml";
#										echo "INCLUDE:$nomefilecompilato,$nomefile<br>";flush();
										$script->ElaboraScript($nomefilecompilato,$nomefile,$this->percorsofile,FALSE,FALSE,"","","");
										
										$this->FileInclude["$nomefile"]=$script;
									}
								break;

								case $this->NODO_IF:
								case $this->NODO_ELSE:								
									$this->TrovaInclude($ChildDomNode);
								break;

								case $this->NODO_PARAMETRO:
								  $etichettaloop=$ChildDomNode->get_content();
								  $this->TrovaInclude($this->LoopZone[$etichettaloop]);
#									echo "etic:$etichettaloop";
								break;

							}

#print "7\n\r";	
						}
													
						if ($ChildDomNode){
							$ChildDomNode = $ChildDomNode->next_sibling();
						}

					}
#					break;					
				#}else{
				#$ChildDomNode = $ChildDomNode->nextSibling;
				#}
#print "5\n\r";	
#			}
#print "4\n\r";
	   #}
   }
}

function TrovaTextSeg($DomNode){


	
   if ($ChildDomNode = $DomNode->first_child()) {
	   if ( ($ChildDomNode->node_name()=="NWSCRIPT") AND ($ChildDomNode->has_child_nodes()) ) {
			$ChildDomNode = $ChildDomNode->first_child();
			while ($ChildDomNode) {
				if ( ($ChildDomNode->node_name()=="TEXTSEG") ){
					return $ChildDomNode;
					break;
				}else{
					$ChildDomNode = $ChildDomNode->next_sibling();
				}
			}

	   }

   }

#   print $ChildDomNode->node_name()."\n\r";
   
}



function remove_ext($str) {
  $noext = preg_replace('/(.+)\..*$/', '$1', $str);
  return $noext;
}


function encode_everything($string){
   $encoded = "";
   for ($n=0;$n<strlen($string);$n++){
       $check = htmlentities($string[$n],ENT_COMPAT);
       $string[$n] == $check ? $encoded .= "&#".ord($string[$n]).";" : $encoded .= $check;
   }
   return $encoded;
}


 function sostituiscientita( $string ) { 
    $fixed = htmlentities( $string, ENT_COMPAT ); 
    $trans_array = array(); 

	$trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
	$cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
	

#       for ($i=160; $i<255; $i++) { 
#           $trans_array[chr($i)] = "&#" . $i . ";"; 
#       } 
#print_r($trans_array);

 #      $really_fixed = strtr($fixed, $trans_array); 
	$really_fixed = utf8_encode(str_replace($trova, $cambia, $fixed)); 
    return $really_fixed; 

   } 


function ElaboraScript($filexml,$filehtml,$Path,$FlagDebug,$FlagCompilaTutto,$Database,$User,$Pass){

    
	
	$this->percorsofile=$Path;
	
	$percorsofilehtml = $Path.$filehtml;



#	echo "Leggo: {$Path}{$filexml}<br>";
#	echo "Leggo: {$Path}{$filehtml}<br>";
#	echo "1<br>";

	if (file_exists($Path.$filexml)) {


		

		$fh = fopen($Path.$filexml, "r");
		$contenutoxml = fread($fh, filesize($Path.$filexml));
		fclose($fh);
	#	echo "<pre>$contenutoxml</pre>";
	#cho "<pre>$contenutoxml\n\n\n";

		$trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
		$cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
		$contenutoxml = str_replace($trova, $cambia, $contenutoxml); 

	#	$contenutoxml = $this->sostituiscientita($contenutoxml);

	#echo "<pre>$contenutoxml";
		$domxml = domxml_open_mem($contenutoxml);
	#	$domxml = domxml_open_file($filexml);


# Controllo se e' tutto formattato correttamente se non da errore e quindi meglio ricompilare
		$nwscrpt_node=$domxml->get_elements_by_tagname("NWSCRIPT");
		$cdate=$nwscrpt_node[0]->get_attribute("v");
		




		if (file_exists($percorsofilehtml)) { $cdatehtmlfile=filemtime($percorsofilehtml); }
		else { $cdatehtmlfile = -1; }

#		echo "controllo di $percorsofilehtml<br>";flush();
#		echo "cdate:$cdate = $cdatehtmlfile<br>";flush();

		if ($cdate!=$cdatehtmlfile) {

#			echo "RICOMPILO CAMBIO VERSIONE $filehtml,$filexml<br>";flush();

			$this->compila($filehtml,$filexml,$Path);

			$fh = fopen($Path.$filexml, "r");
			$contenutoxml = fread($fh, filesize($Path.$filexml));
			fclose($fh);
	#		echo "conte<pre>$contenutoxml\n\n\n";
			$trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
			$cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
			$contenutoxml = str_replace($trova, $cambia, $contenutoxml); 

			$domxml = domxml_open_mem($contenutoxml);

	#		$domxml = domxml_open_file($filexml);
		}

	} else {

		



#		echo "RICOMPILO NUOVO FILE $filehtml<br>";flush();

		$_filexml=$this->remove_ext($filehtml).".xml";
		$this->compila($filehtml,$_filexml,$Path);

		$this->compila($filehtml,$filexml,$Path);
		$fh = fopen($Path.$filexml, "r");
		$contenutoxml = fread($fh, filesize($Path.$filexml));
		fclose($fh);

		$trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
		$cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
		$contenutoxml = str_replace($trova, $cambia, $contenutoxml);

		$domxml = domxml_open_file($Path.$_filexml);

	}

	clearstatcache();

	$this->TrovaLoop($domxml);
	$this->TextSegNode=$this->TrovaTextSeg($domxml);		
	$this->TrovaInclude($this->TextSegNode);




}

function Risultato(){
	print $this->Interpreta_XS($this->TextSegNode);
}

function getRisultato(){
	return $this->Interpreta_XS($this->TextSegNode);
}


function InserisciValori(){
}


function Parametri($nome,$valore,$Decodifica=true){

  if (!($Decodifica)){
	  $valore=str_replace("%","%25",$valore);
  }

  $this->FListaCampi["\$".$nome.";"]=$valore;

  foreach ($this->FileInclude as $FileName=>$oggetto){
    $this->FileInclude[$FileName]->Parametri($nome,$valore,$Decodifica);
  }
}


function sostituisciparametri($testo,$addslashes=false,$multiline=true){

$trova=array();
$sostituisci=array();

	preg_match_all("/[$]\w*[;]/",$testo,$trovati);


	foreach($trovati[0] as $nome){
	#	print $nome."\n\r";
	#	print $this->FListaCampi["$nome"];
		$trova[]="$nome";

		$ParamValue = $this->FListaCampi["$nome"];
		
		if (!($multiline)) { $ParamValue = str_replace(array("\t","\r","\n"),"",$ParamValue); }
		if ($addslashes) { $ParamValue = addslashes($ParamValue); }
		$sostituisci[] = $ParamValue;

	}

	$testo=str_replace($trova,$sostituisci,$testo);



	preg_match_all("/[%](\w{2})/",$testo,$trovati);

	#print $testo;
	#	print_r($trovati);
	foreach($trovati[1] as $nome){
	#	print hexdec($nome);
		$testo=preg_replace("/%$nome/",chr(hexdec($nome)),$testo);

		
	}

#print $testo;
return $testo;

}

function SvuotaValoriLabel($Etichetta){

    foreach ($this->LoopZone as $chiaveloop=>$oggettoloop){

       list($ilEtichetta,$NumeroLoop)=explode("@",$chiaveloop);
#       echo "ilTest:$ilEtichetta num:$NumeroLoop\n";

       if ($ilEtichetta==$Etichetta){
		   $this->DatiLoop[$chiaveloop]="";

#        if ($Etichetta=="rottura"){		echo $Etichetta."<br>\n";}
#        if ($Etichetta=="rottura"){		print_r( strip_tags($this->DatiLoop[$Etichetta]) );}
#        if ($Etichetta=="rottura"){		print "FINE INSERISCI<br>";}
        }

    }

	foreach ($this->FileInclude as $FileName=>$oggetto){
		$this->FileInclude[$FileName]->SvuotaValoriLabel($Etichetta);
	}


#	print "INIZIO SVUOTA $Etichetta<br><pre>";
#	print_r( strip_tags($this->DatiLoop[$Etichetta]) );
#	print"</pre>";
//	$this->DatiLoop[$Etichetta]="";
#	print "FINE SVUOTA $Etichetta<br><pre>";
#	print_r(strip_tags($this->DatiLoop[$Etichetta]));
#	print"</pre>";
}


function getLoopZoneLabel($aLoopName){

	$result = array();
	foreach ($this->LoopZone as $LoopName=>$Loop){

		list($theLoopName,$LoopNumber)=explode("@",$LoopName);

#		var_dump($LoopName);
		
		if ($aLoopName == $theLoopName) {
			$result[] = $LoopName;
		}

	}

	return $result;
	
}


function InserisciValoriLabelRicorsivo($aEtichetta,$aSubEtichetta,$Livello){

$reset = false;

#echo "($Etichetta) Prima:";var_dump( ($this->DatiLoop[$Etichetta]) );echo "<br>";
#echo "$Livello<br>";
#echo "<pre>";
#echo "($Etichetta) Prima:";var_dump( ($this->DatiLoop[$Etichetta]) );echo "<br>";
#echo "</pre>";

	if ($Livello == 1) {
#	if (count($this->DatiLoop[$Etichetta])==1){

#		echo "INIZIO $Livello Count:";
#		echo Count($this->DatiLoop[$Etichetta]);echo "<br>";


		$LoopLabels = $this->getLoopZoneLabel($aEtichetta);

		foreach ($LoopLabels as $Etichetta) {

//		foreach ($this->LoopZone as $chiaveloop=>$oggettoloop) {

//			list($ilEtichetta,$NumeroLoop)=explode("@",$chiaveloop);

//			if ($ilEtichetta == $Etichetta){

				$unNodoLoop=$this->LoopZone[$Etichetta];

				if (!is_null($this->DatiLoop[$Etichetta][$Livello+1])) { 
					
					if (is_array($this->DatiLoop[$Etichetta])){
						ksort($this->DatiLoop[$Etichetta]);
						reset($this->DatiLoop[$Etichetta]);

#						$this->DatiLoop[$SubEtichetta] = array_pop($this->DatiLoop[$Etichetta]);

						$SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);
						
						foreach ($SubLoopLabels as $SubKeyLoop){
							$this->DatiLoop[$SubKeyLoop] = array_pop($this->DatiLoop[$Etichetta]);
						}

					}

				}

				$this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);
				

				$SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);
				
				foreach ($SubLoopLabels as $SubKeyLoop){
					$this->DatiLoop[$SubKeyLoop] = '';
				}


//			}

        }


#V2			$this->DatiLoop["SubRecursivePageMenu"] = "";


# Da Controllare
	} else {


#		echo "aEtichetta:$aEtichetta<br>aSubEtichetta:$aSubEtichetta<br>";


		$SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);
		$LoopLabels = $this->getLoopZoneLabel($aEtichetta);


		foreach ($LoopLabels as $Etichetta) {

			if (!is_array($this->DatiLoop[$Etichetta])){ $this->DatiLoop[$Etichetta][$Livello] = NULL; }


			if ( (is_null($this->DatiLoop[$Etichetta][$Livello+1])) ) {

				#Prendo il nodo relativo al loop piu' profondo di livello
				foreach ($SubLoopLabels as $SubEtichetta) {	


					$unNodoLoop=$this->LoopZone[$SubEtichetta];
					#Interpreto il nodo e lo assegno al nodo relativo al loop di livello superiore
					$this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);
				}
							
						
			} else {

				foreach ($SubLoopLabels as $SubEtichetta) {

#echo "SubEtichetta:$SubEtichetta<br>";var_dump($this->DatiLoop[$Etichetta][$Livello+1]);

					# Al nodo di livello inferiore assegno il valore del nodo di livello successivo faccio lo scambio dei dati
					$this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello+1];
				
					#Prendo il nodo di livello superiore
					$unNodoLoop=$this->LoopZone[$Etichetta];
#echo "unNodoLoop:";var_dump($unNodoLoop);


					#Interpreto il nodo di livello superiore
					$this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);

					#Se esiste elimino il livello dello stesso nodo che gia' ho elaborato
					if (is_array($this->DatiLoop[$Etichetta])){
						ksort($this->DatiLoop[$Etichetta]);
						reset($this->DatiLoop[$Etichetta]);
						array_pop($this->DatiLoop[$Etichetta]);
					}


				#Ripeto lo scambio

#					echo "Ripeto lo scambio:$SubEtichetta Livello:$Livello<br>";
#					echo "<br>";var_dump($this->DatiLoop[$Etichetta]);
					
					$this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello];
					$this->DatiLoop[$SubEtichetta] = "";

				}

			}

		}

//		foreach ($MainLoopLabel as $LoopEtichetta



















/*
echo "Livello:$Livello<br>";

		if (!is_array($this->DatiLoop[$Etichetta])){ $this->DatiLoop[$Etichetta][$Livello] = NULL; }

		if ( (is_null($this->DatiLoop[$Etichetta][$Livello+1])) ) {
echo "QUI $SubEtichetta $Livello <br>";			

#V2				$unNodoLoop=$this->LoopZone["SubRecursivePageMenu"];

				#Prendo il nodo relativo al loop piu' profondo di livello
				$unNodoLoop=$this->LoopZone[$SubEtichetta];
#var_dump($unNodoLoop);
				#Interpreto il nodo e lo assegno al nodo relativo al loop di livello superiore
				$this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);
#V2				$this->DatiLoop['SubPageMenu'][$Livello] .= $this->Interpreta_XS($unNodoLoop);
				
			
		} else {
echo "QUI2 $Livello<br>";

				# Al nodo di livello inferiore assegno il valore del nodo di livello successivo faccio lo scambio dei dati
				$this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello+1];
#V2				$this->DatiLoop["SubRecursivePageMenu"] = $this->DatiLoop['SubPageMenu'][$Livello+1];
				
				#Prendo il nodo di livello superiore
				$unNodoLoop=$this->LoopZone[$Etichetta];
#V2				$unNodoLoop=$this->LoopZone['SubPageMenu'];

				#Interpreto il nodo di livello superiore
				$this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);
#V2				$this->DatiLoop['SubPageMenu'] .= $this->Interpreta_XS($unNodoLoop);


				#Se esiste elimino il livello dello stesso nodo che gia' ho elaborato
				if (is_array($this->DatiLoop[$Etichetta])){
					ksort($this->DatiLoop[$Etichetta]);
					reset($this->DatiLoop[$Etichetta]);
					array_pop($this->DatiLoop[$Etichetta]);
				}

				#Ripeto lo scambio
#				$this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello];
#				$this->DatiLoop[$SubEtichetta] = "";


		}

		*/

	}




#echo "<pre>";
/*
echo "<br>";
echo "B(PageMenu) Dopo:";var_dump( ($this->DatiLoop['PageMenu']) );echo "<br>";
echo "B(SubPageMenu) Dopo:";var_dump( ($this->DatiLoop['SubPageMenu']) );echo "<br>";
echo "B(SubRecursivePageMenu) Dopo:";var_dump( ($this->DatiLoop['SubRecursivePageMenu']) );echo "<br>";
*/
#echo "</pre>";

}
/*
function InserisciValoriLabel($Etichetta){

#echo "<pre>";
#print_r(debug_backtrace());
#print_r($this->LoopZone);
#echo "</pre>";
    foreach ($this->LoopZone as $chiaveloop=>$oggettoloop){

       list($ilEtichetta,$NumeroLoop)=explode("@",$chiaveloop);
#echo "ilTest:$ilEtichetta num:$NumeroLoop<br>\n";

       if ($ilEtichetta==$Etichetta){
    	   $unNodoLoop=$this->LoopZone[$chiaveloop];

#echo $Etichetta."<br>\n";
#echo "chiaveloop:$chiaveloop<br>";
 #       if ($Etichetta=="rottura"){		echo $Etichetta."<br>\n";}
        	$this->DatiLoop[$chiaveloop].=$this->Interpreta_XS($unNodoLoop);

#echo "<pre>";
#print_r($this->Interpreta_XS($unNodoLoop));
#print_r($this->FListaCampi);
#echo "</pre>";


#        if ($Etichetta=="rottura"){		print_r( strip_tags($this->DatiLoop[$Etichetta]) );}
#        if ($Etichetta=="rottura"){		print "FINE INSERISCI<br>";}
        }

    }

		foreach ($this->FileInclude as $etichetta=>$oggetto){
			$oggetto->InserisciValoriLabel($Etichetta);
		}
#print_r($this->FileInclude);



#	print "INSERISCI $Etichetta<br>";
//	$unNodoLoop=$this->LoopZone[$Etichetta];

#	echo $Etichetta."<br>";
//	$this->DatiLoop[$Etichetta].=$this->Interpreta_XS($unNodoLoop);
#	print_r( strip_tags($this->DatiLoop[$Etichetta]) );
#	print "FINE INSERISCI<br>";

#echo "<br>";
#echo "A(PageMenu) Dopo:";var_dump( ($this->DatiLoop['PageMenu']) );echo "<br>";
#echo "A(SubPageMenu) Dopo:";var_dump( ($this->DatiLoop['SubPageMenu']) );echo "<br>";
#echo "A(SubRecursivePageMenu) Dopo:";var_dump( ($this->DatiLoop['SubRecursivePageMenu']) );echo "<br>";
}*/

function InserisciValoriLabel($Etichetta){

#echo "<pre>";
#print_r(debug_backtrace());
#print_r($this->LoopZone);
#echo "</pre>";
    foreach ($this->LoopZone as $chiaveloop=>$oggettoloop){

       list($ilEtichetta,$NumeroLoop)=explode("@",$chiaveloop);
#echo "ilTest:$ilEtichetta num:$NumeroLoop<br>\n";

       if ($ilEtichetta==$Etichetta) {
    	   $unNodoLoop=$this->LoopZone[$chiaveloop];

#echo $Etichetta."<br>\n";
#echo "chiaveloop:$chiaveloop<br>";
#echo "<pre>";var_dump($this->DatiLoop[$chiaveloop]);echo "</pre>";
#echo "<pre>";var_dump($this);echo "</pre>";
        	$this->DatiLoop[$chiaveloop].=$this->Interpreta_XS($unNodoLoop);
#echo "<pre>";var_dump($this->DatiLoop[$chiaveloop]);echo "</pre>";



        }

    }

#echo "<pre>";
#print_r($this->DatiLoop[$chiaveloop]);
#print_r($this->Interpreta_XS($unNodoLoop));
#print_r($this->FListaCampi);
#echo "</pre>";

#	foreach ($this->FileInclude as $FileName=>&$oggetto){
	foreach ($this->FileInclude as $FileName => $oggetto){
#		var_dump($FileName);
#		$oggetto->InserisciValoriLabel($Etichetta);
		$this->FileInclude[$FileName]->InserisciValoriLabel($Etichetta);
	}
}

function compila($filehtml,$filexml,$Path){

#	print "file html:$filehtml\n";
	$percorsofilehtml="$Path"."$filehtml";

	$lines=file($percorsofilehtml);
	$datafile=join($lines);
#	echo "asdf";
#	$datafile=utf8_encode($datafile);
#	echo "asdf2";
	$stato=0;
	$lunghezzaTesto=0;
	$InizioTesto=0;
	$tag="";

	$xmlscript=domxml_new_doc("1.0");
	




	$root=$xmlscript->add_root("NWSCRIPT");

	
	$root->set_attribute("v",filemtime($percorsofilehtml));
	
	$nodo_loop=$xmlscript->create_element("LOOP");
	$nodo_textseg=$xmlscript->create_element("TEXTSEG");
	$root->append_child($nodo_loop); 
	$root->append_child($nodo_textseg); 





	for ($i=0; $i<strlen($datafile); ++$i){



        switch ($datafile[$i]){

             case '<':
                   switch ($stato){
                        case 0:
                            $stato=1;
                            $posz=$i;
                        break;

                        case 1:
                            $stato=0;
                            $lunghezzaTesto=$lunghezzaTesto+2;
                        break;

				   }
             break;

             case '!':
                   switch ($stato){
                        case 0:$lunghezzaTesto++;break;
                        case 1:$stato=2;break;
                        case 7:$expr.=$datafile[$i];break;
					}
                      
             break;
             case ':':
                   switch ($stato) {
                      case 0:$lunghezzaTesto++;break;
                      case 3:$stato=4;break;
					}
             break;

             case ' ':
                   switch ($stato) {
                       case 0:$lunghezzaTesto++;break;
                       case 19:$lunghezzaTesto++;break;
						default:
							$lunghezzaTesto=$lunghezzaTesto+($i-$posz+1);
							$tag='';                        
							$stato=0;
						break;
					}
             break;

             case '>':
                   switch ($stato){
                        case 0:$lunghezzaTesto++;break;
                        case 3:
                            $lunghezzaTesto=$i;
                            $tag='';
                            $posz=1;
                            $stato=0;
                          break;  
                        // chiusura tag include //
                        case 5:
#                            Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
							$Nodo=$xmlscript->create_element($this->NODO_TESTO);
							$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));

							$nodo_textseg->append_child($Nodo);
                            $lunghezzaTesto=0;
                            $InizioTesto=$i+1;
                            $stato=28; 
                        break;
                        case 8:
                        // chiusura tag if   
							 
                            $lunghezzaTesto=0;
                            $InizioTesto=$i+1;
                            $stato=11;
                          break;
                        case 9:
                        // chiusura tag endif
#                            Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
							$Nodo=$xmlscript->create_element($this->NODO_TESTO);
							$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
							$nodo_textseg->append_child($Nodo);

                            $lunghezzaTesto=0;
                            $InizioTesto=$i+1;
                          break;                         
                       case 10:
                       // chiusura tag else
#                            Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
							$Nodo=$xmlscript->create_element($this->NODO_TESTO);
							$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
							$nodo_textseg->append_child($Nodo);

                            $lunghezzaTesto=0;
                            $InizioTesto=$i+1;
                          break;
                       // chisura tag query //   
                       case 20:
                            $stato=21;
                          break;
                       // chisura tag query //   
                       case 14:
                            $stato=99;
                          break;

                       // chiusura tag beginloop senza label //
                       case 22:$stato=29;break;
                       // chiusura tag beginloop con label //non implementata//
                       case 24:$stato=30;break;
                       // chiusura tag endloop //
                       case 27:$stato=31;break;
                         
					}
                 break;

             case '{':
                   switch ($stato){
                       case 0:$lunghezzaTesto++;break;
                       case 18:
                            $InizioTesto=$i+1; 
                            $stato=19;
                            $lunghezzaTesto=0;
                            $Insieme='';
                          break;
                       case 12:
                            $InizioTesto=$i+1; 
                            $stato=13;
                            $lunghezzaTesto=0;
                            $Insieme='';
                          break;
					}
                 break;

             case '}':
                   switch ($stato){
                        case 0:$lunghezzaTesto++;break;
                        case 19:
                             $stato=20;
                             $Insieme=substr($datafile,$InizioTesto,$lunghezzaTesto);
                             $lunghezzaTesto=0;                             
                        break;
                        case 13:
                             $stato=14;
                             $Insieme=substr($datafile,$InizioTesto,$lunghezzaTesto);
                             $lunghezzaTesto=0;
                        break;
                        
					}
                 break;

             case '[':
                   switch ($stato){
                        case 0:$lunghezzaTesto++;break;
                        case 1:
                            $lunghezzaTesto+=2;
                            $InizioTesto=$i;
                            $stato=0;
                        break;
                        case 4:
                            $stato=7;
                            $InizioTesto=$i+1;
                            $expr='';
                        break;
                        // caso query //
                        case 16:
                             $stato=17;
                             $InizioTesto=$i+1;
                             $lunghezzaTesto=0;
                             $expr='';
                        break; 
                        // caso foreach//
                        case 15:
                             $stato=11;
                             $InizioTesto=$i+1;
                             $lunghezzaTesto=0;
                             $expr='';
                        break;
                        case 22:
                             $stato=23;
                             $InizioTesto=$i+1;
                             $lunghezzaTesto=0;
                             $expr='';
                        break;
                           
					}
                 break;

             case ']':
                   switch ($stato){
                        case 0:$lunghezzaTesto++;break;
                        case 1:
                            $lunghezzaTesto=$lunghezzaTesto+2;
                            $stato=0;
                        break;
                        case 7:
                            $stato=8;
                        break;
                       case 17:
                            $stato=18;
                            $expr=substr($datafile,$InizioTesto,$lunghezzaTesto);
                       break;
                       case 11:
                            $stato=12;
                            $expr=substr($datafile,$InizioTesto,$lunghezzaTesto);
                       break;
                       case 23:
                            $stato=24;
                            $expr=substr($datafile,$InizioTesto,$lunghezzaTesto);
                       break;
					}
                 break;
                 
        default:
             switch ($stato) {
                  case 0:
                      $lunghezzaTesto++;//campo:=campo+Input[i];
                    break;
                  case 1:
//                ho trovato un < senza aver trovato ! dopo quindi devo ignorare il tag //
//                      lunghezzaTesto:=i;  
                      $lunghezzaTesto+=2;
                      $stato=0;
                    break;
//                  5:inc(lunghezzaTesto);//campo:=campo+Input[i];
                  case 5:$campo.=$datafile[$i];break;
                  
                  case 2:
                      if ($datafile[$i]!='-'){
                         $stato=3;
					  }else{
                           $lunghezzaTesto+=3;
                           $stato=0;
					  }
                    break;
                    
                  case 7:
                      $lunghezzaTesto++;
                      $expr.=$datafile[$i];

                    break;
                 // caso valori tra[ e ] //                       
              case 11:$lunghezzaTesto++;break;
			  case 17:$lunghezzaTesto++;break;
			  case 23:$lunghezzaTesto++;break;
                 // caso valori tra { e } //
			  case 13:$lunghezzaTesto++;break;
			  case 19:$lunghezzaTesto++;break;
                  
			}  
           break;


		}#FINE SWITCH 323

#print "$datafile[$i]\n";



        switch ($stato){
             case 3:$tag.=$datafile[$i];break;
			 case 4:
#				 print "$tag\n";

				 switch ($tag){
					 case "if":
						  $expr='';
                          $campo='';
                          #Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
#						  print "valore:\n".substr($datafile,$InizioTesto,$lunghezzaTesto)."\n";
							$Nodo=$xmlscript->create_element($this->NODO_TESTO);
							$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
							$nodo_textseg->append_child($Nodo);

                          $lunghezzaTesto=0;
#						  exit;
					 break;
					 case "endif":
						 $stato=9;
					 break;
					 case "else":
						 $stato=10;
						 $campo=0;
					 break;



                      case "beginloop":
                                    $expr='';
                                    $campo='';
#                                    Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
									$Nodo=$xmlscript->create_element($this->NODO_TESTO);
									$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
									$nodo_textseg->append_child($Nodo);

                                    $lunghezzaTesto=0;
                                    $stato=22;
                                  break;
                                  
                      case "endloop":
                                  $expr='';
                                  $campo='';
								  $Nodo=$xmlscript->create_element($this->NODO_TESTO);
								  $Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
								  $nodo_textseg->append_child($Nodo);

#                                  Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
                                  $lunghezzaTesto=0;
                                  $stato=27;
                                break;

                       case "include":
                            $stato=5;
                       break;
					
					default:
                      $stato=0;
                      //campo:=campo+Copy(Input,posz,i-posz);
                      $lunghezzaTesto=$i;
					break; 
				 }
			 break;

// Chiusura tag IF //          
             case 11:
#                  token:=getToken(tag);
				
				  $token=$tag;
                  if ($token=="if"){
						$Nodo=$xmlscript->create_element($this->NODO_IF);
						$Nodo->set_attribute("e",$expr);
						$nodo_textseg->append_child($Nodo);
						$nodo_textseg=$nodo_textseg->last_child();
						$lunghezzaTesto=0;
						$InizioTesto=$i+1;
						$campo='';
						$tag='';
						$expr='';
						$stato=0;
				  }
               break;
// Chiusura tag else //                     
             case 10:
                 if (($nodo_textseg->node_name()==$this->NODO_IF)){
					$nodo_textseg=$nodo_textseg->parent_node();
					$Nodo=$xmlscript->create_element($this->NODO_ELSE);
					$nodo_textseg->append_child($Nodo);
					$nodo_textseg=$nodo_textseg->last_child();

				 }

                      $campo='';
                      $lunghezzaTesto=0;
                      $InizioTesto=$i+1;
                      $tag='';
                      $expr='';
                      $stato=0;
#                    end
#                 else
#                    $campo:=substr(input,$InizioTesto,$lunghezzaTesto)+'<!elseif> senza <!if>';                    
               break;
// Chiusura tag endif //
             case 9:
			 
                 if (($nodo_textseg->node_name()==$this->NODO_IF)or($nodo_textseg->node_name()==$this->NODO_ELSE)){
					  $nodo_textseg=$nodo_textseg->parent_node();
                      $campo='';
                      $lunghezzaTesto=0;
                      $InizioTesto=$i+1;
                      $tag='';
                      $expr='';
                      $stato=0;
				 }
				else{
                    $campo=substr($datafile,$InizioTesto,$lunghezzaTesto)."<!endif> senza <!if>";
				}
                break;

             case 28:


                    $uncampo=$this->sostituisciparametri($campo);
#					echo "<b>campo:$campo; uncampo:$uncampo<br></b>";
					if ($uncampo){
						$Nodo=$xmlscript->create_element($this->NODO_INCLUDI);
						$Nodo->set_content($campo);
						$nodo_textseg->append_child($Nodo);

						$fileincludere=$uncampo;
#						$filecompilato=$this->remove_ext($fileincludere).".xml";

						$script = new DoScript();

						$nomefile=$uncampo;
						$nomefilecompilato=$this->remove_ext($nomefile).".xml";
#                             echo "nf:$nomefile";

						$script->ElaboraScript($nomefilecompilato,$nomefile,$this->percorsofile,FALSE,FALSE,"","","");

						$this->FileInclude["$fileincludere"]=$script;
					}
#                    print_r($this->FileInclude);

#					$this->compila($fileincludere,$filecompilato,$Path);
                    $tag='';
                    $campo='';
                    $expr='';
                    $lunghezzaTesto=0;
                    $InizioTesto=$i+1;
                    $stato=0;
              break; 

			// chiusura tag beginloop //
             case 29:
					$Nodo=$xmlscript->create_element($this->NODO_LABEL);
					$nodo_textseg->append_child($Nodo);
					$nodo_textseg=$nodo_textseg->last_child();

#                  token:=getToken(tag);
#                  Nodo:=CreaNodo(NODO_LABEL,expr);
#                  AggiungiFiglio(DoScriptData.XS_Script,Nodo);
##                  EntraFiglioByAddr(DoScriptData.XS_Script,Nodo);
                  $lunghezzaTesto=0;
                  $InizioTesto=$i+1;
                  $campo='';
                  $tag='';
                  $expr='';
                  $stato=0;
                break;
             case 30:
					$Nodo=$xmlscript->create_element($this->NODO_LABEL);
					$Nodo->set_attribute("n","$expr@$i");
					$nodo_textseg->append_child($Nodo);	
#					print_r ($nodo_textseg);
					$nodo_textseg=$nodo_textseg->last_child();
#					print_r ($nodo_textseg);
#                  token:=getToken(tag);
#                  Nodo:=CreaNodo(NODO_LABEL,'');
#                  AggiungiParametro(Nodo,'n',expr);
#                  AggiungiFiglio(DoScriptData.XS_Script,Nodo);
#                  EntraFiglioByAddr(DoScriptData.XS_Script,Nodo);
                  $lunghezzaTesto=0;
                  $InizioTesto=$i+1;
                  $campo='';
                  $tag='';
                  $expr='';
                  $stato=0;

				break;

              case 31:
#				  print $nodo_textseg->node_name()."\n";
                  if ($nodo_textseg->node_name()==$this->NODO_LABEL){
					  if ($nodo_textseg->has_attributes()){

						  $campo=$nodo_textseg->get_attribute("n");
						  
						  list($nomeloop,$valorerigaloop)=explode("@",$campo);

						  $nodo_loop->append_child($nodo_textseg->clone_node(true));
						  $nodo_rimuovere=$nodo_textseg;
						  $nodo_textseg=$nodo_textseg->parent_node();
						  $child=$nodo_textseg->remove_child($nodo_rimuovere);

						  $Nodo=$xmlscript->create_element($this->NODO_PARAMETRO);
#						  $Nodo->set_content($this->sostituiscientita($campo));
						  $Nodo->set_content($this->sostituiscientita($nomeloop));
						  $Nodo->set_attribute("l",$valorerigaloop);
						  $nodo_textseg->append_child($Nodo);	

					  }else{

					  }

				  }
 #                   begin
 #                     LivelloSuperiore(DoScriptData.XS_Script);
 #                     vaiPrimoParametro(DoScriptData.XS_Script.FiglioCorrente);
 #                     if DOScriptData.XS_Script.FiglioCorrente.ListaParametri<>nil then
 #                        begin
 #                          campo:=DoScriptData.XS_Script.FiglioCorrente.ListaParametri.Valore;
 #                          AggiungiFiglio(ZonaLoop,CopiaSottoAlbero(DoScriptData.XS_Script.FiglioCorrente));
 #                          RimuoviFiglio(DoScriptData.XS_Script,DoScriptData.XS_Script.FiglioCorrente);
 #                          Nodo:=CreaNodo(NODO_PARAMETRO,campo);
 #                          AggiungiFiglio(DoScriptData.XS_Script,Nodo);
 #                        end
 #                     else 
 #                        begin
 #                          AggiungiFiglio(ZonaLoop,CopiaSottoAlbero(DoScriptData.XS_Script.FiglioCorrente));
 #                          
 #                          DistruggiNodo(DoScriptData.XS_Script.FiglioCorrente);
 #                          Nodo:=CreaNodo(NODO_PARAMETRO,'');
 #                          AggiungiParametro(Nodo,'v','default');
 #                          AggiungiFiglio(DoScriptData.XS_Script,Nodo);
 #                        end;                      
 #                     
                  $lunghezzaTesto=0;
                  $InizioTesto=$i+1;
                  $campo='';
                  $tag='';
                  $expr='';
                  $stato=0;
				  $insieme='';

 #                   end
 #                else
 #                   campo:=copy(input,InizioTesto,lunghezzaTesto)+'<!endloop:> senza <!beginloop:>';
                break;


		}


/*
        case stato of
// Chisura token include //               
             28:begin
                 token:=getToken(tag);
                 Compila_XS(DOScriptData,token,campo);
  {$IFDEF TEST}                   
                 campo:=sostituisciValori(DoScriptData,campo);
                 campo:=ChangeFileExt(campo,'.html');
                 
                 if FlagCompilaTutto then
                    CompilaScript(Percorso,Programma,sostituisciValori(DoScriptData,campo),'',FlagDebug,FlagCompilaTutto);
    {$ENDIF}

//                 token:=tktesto;
                 tag:='';
                 campo:='';
                 lunghezzaTesto:=0;
                 InizioTesto:=i+1;
                 stato:=0;
               end;
             // chiusura tag query //  
             21:begin
                  token:=getToken(tag);
                  Nodo:=CreaNodo(NODO_QUERY,codificaTesto(Insieme));
                  AggiungiParametro(Nodo,'v',expr);
                  AggiungiFiglio(DoScriptData.XS_Script,Nodo);
                  EntraFiglioByAddr(DoScriptData.XS_Script,Nodo);
                  lunghezzaTesto:=0;
                  InizioTesto:=i+1;
                  campo:='';
                  tag:='';
                  expr:='';
                  insieme:='';
                  stato:=0;
               end;
             // chiusura tag endquery //  
             26:begin
                 if (DoScriptData.XS_Script.Nome=NODO_QUERY) then
                    begin
                      LivelloSuperiore(DoScriptData.XS_Script);                      
                      campo:='';
                      lunghezzaTesto:=0;
                      InizioTesto:=i+1;
                      tag:='';
                      expr:='';
                      insieme:='';
                      stato:=0;
                    end
                 else
                    campo:=copy(input,InizioTesto,lunghezzaTesto)+'<!endquery:> senza <!query:>';
                end;
             // chiusura tag endquery //  
             25:begin
                 if (DoScriptData.XS_Script.Nome=NODO_LOOP) then
                    begin
                      LivelloSuperiore(DoScriptData.XS_Script);                      
                      campo:='';
                      lunghezzaTesto:=0;
                      InizioTesto:=i+1;
                      tag:='';
                      expr:='';
                      insieme:='';
                      stato:=0;
                    end
                 else
                    campo:=copy(input,InizioTesto,lunghezzaTesto)+'<!endfor:> senza <!foreach:>';
                end;           
            // chiusura tag foreach //    
             99:begin
                  token:=getToken(tag);
                  Nodo:=CreaNodo(NODO_LOOP,expr);
                  EsaminaInsieme(Nodo,Insieme);
                  AggiungiFiglio(DoScriptData.XS_Script,Nodo);
                  EntraFiglioByAddr(DoScriptData.XS_Script,Nodo);
                  lunghezzaTesto:=0;
                  InizioTesto:=i+1;
                  campo:='';
                  tag:='';
                  expr:='';
                  stato:=0;
                end;
             31:begin
                 if (DoScriptData.XS_Script.Nome=NODO_LABEL) then
                    begin
                      LivelloSuperiore(DoScriptData.XS_Script);
                      vaiPrimoParametro(DoScriptData.XS_Script.FiglioCorrente);
                      if DOScriptData.XS_Script.FiglioCorrente.ListaParametri<>nil then
                         begin
                           campo:=DoScriptData.XS_Script.FiglioCorrente.ListaParametri.Valore;
                           AggiungiFiglio(ZonaLoop,CopiaSottoAlbero(DoScriptData.XS_Script.FiglioCorrente));
                           RimuoviFiglio(DoScriptData.XS_Script,DoScriptData.XS_Script.FiglioCorrente);
                           Nodo:=CreaNodo(NODO_PARAMETRO,campo);
                           AggiungiFiglio(DoScriptData.XS_Script,Nodo);
                         end
                      else 
                         begin
                           AggiungiFiglio(ZonaLoop,CopiaSottoAlbero(DoScriptData.XS_Script.FiglioCorrente));
                           
                           DistruggiNodo(DoScriptData.XS_Script.FiglioCorrente);
                           Nodo:=CreaNodo(NODO_PARAMETRO,'');
                           AggiungiParametro(Nodo,'v','default');
                           AggiungiFiglio(DoScriptData.XS_Script,Nodo);
                         end;                      
                      
                      campo:='';
                      lunghezzaTesto:=0;
                      InizioTesto:=i+1;
                      tag:='';
                      expr:='';
                      insieme:='';
                      stato:=0;
                    end
                 else
                    campo:=copy(input,InizioTesto,lunghezzaTesto)+'<!endloop:> senza <!beginloop:>';
                end;
                
        end;
      end;  

*/	

	}// FINE WHILE

	if ($lunghezzaTesto>0){
	$Nodo=$xmlscript->create_element($this->NODO_TESTO);
	$Nodo->set_content($this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
	$nodo_textseg->append_child($Nodo);
	}

#	echo "<pre>".$xmlscript->dump_mem()."</pre>";
	$PercorsoFileCompilato=$Path.$filexml;
#	print("percorso:".$PercorsoFileCompilato);
#	print $xmlscript->dump_mem(true);
#	flush();
#	exit;
	$xmlscript->dump_file($PercorsoFileCompilato,false,false);
#	print "$filexml<br>";
#	print_r($xmlscript);




}

}#FINE CLASSE



?>
