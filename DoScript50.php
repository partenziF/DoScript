<?php
    DEFINE('_is_utf8_split',5000); 

    class DoScript {

        var $LoopZone=array();
        var $FListaCampi=array();
        var $DatiLoop=array();
        var $FileInclude=array();
        var $IncludeOnlyFilename=array();

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

        var $AutoIncludeFile = false;

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


        function Interpreta_XS($DomNode) {
            $risultato="";

            if (($DomNode) AND ($ChildDomNode = $DomNode->firstChild)) {

                while ($ChildDomNode) {

                    switch ($ChildDomNode->nodeName){

                        case $this->NODO_IF:
                            $espressione=$ChildDomNode->getAttribute("e");
                            $espressionedebug=$espressione;
                            #				  print $espressione."<br>";
                            $espressione=(($this->sostituisciparametri($espressione,true,false)));
                            #					  print $espressione."<br>";
                            if (empty($espressione)) {
                                $risultatoeq = false;
                            } else {
                                $r=@eval("\$risultatoeq=({$espressione});");
                            }

                            if ($r===false){
                                printf("%s<br>",var_dump($espressionedebug));
                                printf("%s<br>",var_dump($espressione));
                                echo "<pre>";
                                #debug_print_backtrace();
                                var_dump($this->FListaCampi);
                                exit;
                            }

                            if ( ($risultatoeq==TRUE) ){
                                $risultato.=$this->Interpreta_XS($ChildDomNode);
                            }else{
                                $ElseNode = $ChildDomNode->nextSibling;
                                while ($ElseNode) {

                                    if ($ElseNode->nodeType == XML_ELEMENT_NODE){
                                        if ($ElseNode->nodeName==$this->NODO_ELSE){
                                            $risultato.=$this->Interpreta_XS($ElseNode);
                                        }
                                        break;
                                    }
                                    $ElseNode=$ElseNode->nextSibling;
                                }
                            }

                            break;

                        case $this->NODO_TESTO:
                            $risultato.=$this->sostituisciparametri($ChildDomNode->textContent);
                            break;

                        case $this->NODO_PARAMETRO:
                            $etichettaloop=$ChildDomNode->textContent;
                            $rigaloop=$ChildDomNode->getAttribute("l");

                            #echo "00<br>";						  
                            // Se ci sono loop ricorsivi allora da intrepretare come array
                            if ( is_array($this->DatiLoop[$etichettaloop."@".$rigaloop]) ) {

                                #echo "10 ".$etichettaloop."@".$rigaloop."<br>";

                                foreach ($this->DatiLoop[$etichettaloop."@".$rigaloop] as $key => $value) {
                                    $risultato .= $value;

                                    #echo "10.1<br>";
                                }

                            } else {

                                $risultato .= $this->DatiLoop[$etichettaloop."@".$rigaloop];

                            }

                            break;

                        case $this->NODO_INCLUDI:



                            $nomefile=$this->sostituisciparametri($ChildDomNode->textContent);
                            #echo "SIII:";var_dump($nomefile);
                            #echo "<pre>";var_dump($nomefile,$this->FileInclude);echo "</pre>";

                            if ($this->FileInclude["$nomefile"]){

                                foreach ($this->FListaCampi as $nome=>$valore){

                                    $this->FileInclude["$nomefile"]->FListaCampi[$nome]=$valore;

                                }

                                $risultato.=$this->FileInclude["$nomefile"]->getRisultato();


                            }

                            break;

                        default: break;
                    }
                    if ($ChildDomNode){
                        $ChildDomNode = $ChildDomNode->nextSibling;
                    }
                }
                return $risultato;
            }
        }


        function TrovaLoop($DomNode){

            #$LoopZone=array();
            #print "1\n\r";
            if ($ChildDomNode = $DomNode->firstChild) {
                #print "2\n\r";
                if ( ($ChildDomNode->nodeName=="NWSCRIPT") AND ($ChildDomNode->hasChildNodes()) ) {
                    #print "3\n\r";
                    $ChildDomNode = $ChildDomNode->firstChild;
                    while ($ChildDomNode) {
                        #print $ChildDomNode->nodeName."\n\r";
                        if ( ($ChildDomNode->nodeName=="LOOP") ){
                            $ChildDomNode = $ChildDomNode->firstChild;

                            #print "6\n\r";
                            while ($ChildDomNode) {
                                if ($ChildDomNode->nodeType == XML_ELEMENT_NODE) {
                                    #print "7\n\r";

                                    if ($ChildDomNode->hasAttributes()) {
                                        $nomeloop=$ChildDomNode->getAttribute("n");				   
                                    }else{
                                        $nomeloop=char(0);
                                    }
                                    #print "$nomeloop\n\r";
                                    $this->LoopZone[$nomeloop]=$ChildDomNode;
                                    $this->DatiLoop[$nomeloop]="";

                                }

                                $ChildDomNode = $ChildDomNode->nextSibling;
                            }

                            break;

                        }else{
                            $ChildDomNode = $ChildDomNode->nextSibling;
                        }
                        #print "5\n\r";	
                    }
                    #print "4\n\r";
                }
            }
        }

        function IncludeFile($aFileName) {
            $this->IncludeOnlyFilename[] = $aFileName;
        }


        function TrovaInclude($DomNode){
            #$LoopZone=array();
            #echo "<pre>";
            #print "1\n\r";

            if (($DomNode) AND ($ChildDomNode = $DomNode->firstChild)) {

                #print "2\n\r";
                #	   if ( ($ChildDomNode->nodeName=="NWSCRIPT") AND ($ChildDomNode->hasChildNodes()) ) {
                #print "3\n\r";
                #		   $ChildDomNode = $ChildDomNode->firstChild;
                #			while ($ChildDomNode) {
                #				if ( ($ChildDomNode->nodeName=="TEXTSEG") ){
                #$ChildDomNode = $ChildDomNode->firstChild;

                #print "6\n\r";	
                while ($ChildDomNode) {
                    #echo "NN:$ChildDomNode->nodeName<br>";
                    if ($ChildDomNode->nodeType == XML_ELEMENT_NODE) {

                        switch ($ChildDomNode->nodeName){

                            case $this->NODO_INCLUDI:

                                $nomefile=$this->sostituisciparametri($ChildDomNode->textContent);

                                if ($nomefile){

                                    if ($this->AutoIncludeFile) {
                                        $IncludeFile = true;
                                    } else {
                                        $IncludeFile = ( (!empty($this->IncludeOnlyFilename)) && (in_array($nomefile,$this->IncludeOnlyFilename)) );
                                    }

                                    if ($IncludeFile) {

                                        $script = new DoScript();


                                        foreach ($this->FListaCampi as $nome=>$valore){

                                            $script->FListaCampi[$nome]=$valore;

                                        }

                                        $nomefilecompilato=$this->remove_ext($nomefile).".xml";

                                        //Codice per includere i file ad albero

                                        for ($FileIndex=0;$FileIndex<count($this->IncludeOnlyFilename);$FileIndex++) {

                                            $script->IncludeFile($this->IncludeOnlyFilename[$FileIndex]);
                                        }


                                        $script->ElaboraScript($nomefilecompilato,$nomefile,$this->percorsofile,FALSE,FALSE,"","","");

                                        $this->FileInclude["$nomefile"]=$script;

                                    }

                                }

                                break;

                            case $this->NODO_IF:
                            case $this->NODO_ELSE:								
                                $this->TrovaInclude($ChildDomNode);
                                break;

                            case $this->NODO_PARAMETRO:
                                $etichettaloop=$ChildDomNode->textContent;
                                $this->TrovaInclude($this->LoopZone[$etichettaloop]);
                                #									echo "etic:$etichettaloop";
                                break;

                        }

                        #print "7\n\r";	
                    }

                    if ($ChildDomNode){
                        $ChildDomNode = $ChildDomNode->nextSibling;
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


        function TrovaTextSeg($DomNode) {



            if ($ChildDomNode = $DomNode->firstChild) {
                if ( ($ChildDomNode->nodeName=="NWSCRIPT") AND ($ChildDomNode->hasChildNodes()) ) {
                    $ChildDomNode = $ChildDomNode->firstChild;
                    while ($ChildDomNode) {
                        if ( ($ChildDomNode->nodeName=="TEXTSEG") ){
                            return $ChildDomNode;
                            break;
                        }else{
                            $ChildDomNode = $ChildDomNode->nextSibling;
                        }
                    }

                }

            }

            #   print $ChildDomNode->nodeName."\n\r";

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



        function ElaboraScript($filexml,$filehtml,$Path,$FlagDebug,$FlagCompilaTutto,$Database,$User,$Pass) {

            #$t=CronStart();

            $this->percorsofile=$Path;

            $percorsofilehtml = $Path.$filehtml;

            $domxml = new DOMDocument();

            #	echo "Leggo: {$Path}{$filexml}<br>";
            #	echo "Leggo: {$Path}{$filehtml}<br>";
            #	echo "1<br>";

            if (file_exists($Path.$filexml)){
                
                //clearstatcache();

                #	echo "2 $filehtml,$filexml<br>";


                $fh = fopen($Path.$filexml, "r");
                $contenutoxml = fread($fh, filesize($Path.$filexml));
                fclose($fh);



                $trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
                $cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
                $contenutoxml = str_replace($trova, $cambia, $contenutoxml);




                $domxml->loadXML($contenutoxml);

                #		if ($filexml == "BMHelpWebmasterAnagraficaLinkAutomatici.xml"){ print ("<textarea>$contenutoxml</textarea>"); }

                # Controllo se e' tutto formattato correttamente se non da errore e quindi meglio ricompilare
                if ($domxml->getElementsByTagName("NWSCRIPT")->item(0)){
                    $cdate=$domxml->getElementsByTagName("NWSCRIPT")->item(0)->getAttribute("v");		
                    #			echo "SI";
                }else{
                    #			echo "NO";
                    $cdate=0;
                }

                //$cdatehtmlfile=filemtime($percorsofilehtml);
                if (file_exists($percorsofilehtml)) { $cdatehtmlfile=filemtime($percorsofilehtml); }
                else { $cdatehtmlfile = -1; }

                #		echo "4.1<br>";

                #		echo "controllo di $percorsofilehtml<br>";flush();
                #		echo "cdate:$cdate = $cdatehtmlfile<br>";flush();

                if ($cdate!=$cdatehtmlfile) {
                    #		if (true) {

                    #			echo "RICOMPILO CAMBIO VERSIONE $filehtml,$filexml<br>";flush();

                    $this->compila($filehtml,$filexml,$Path);

                    $fh = fopen($Path.$filexml, "r");
                    $contenutoxml = fread($fh, filesize($Path.$filexml));
                    fclose($fh);

                    $trova=array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;");
                    $cambia=array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;");
                    $contenutoxml = str_replace($trova, $cambia, $contenutoxml);

                    $domxml->loadXML($contenutoxml);


                }

            } else {

                #		echo "4.2<br>";

                // Compila crea il file xml se non esiste

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

                #		$domxml->loadXML($Path.$_filexml);
                $domxml->loadXML($contenutoxml);

            }

            clearstatcache();

            $this->TrovaLoop($domxml);
            $this->TextSegNode=$this->TrovaTextSeg($domxml);		
            $this->TrovaInclude($this->TextSegNode);

            #	$tp=CronStop($t,"Elabora $filexml");


        }

        function Risultato(){	
            #	echo "FINE";
            echo ($this->Interpreta_XS($this->TextSegNode));
        }

        function getRisultato(){
            #	echo "FINE";
            return $this->Interpreta_XS($this->TextSegNode);
        }


        function InserisciValori(){
        }


        function Parametri($nome,$valore,$Decodifica=true){
            #print "param:$nome,$valore<br>";flush();
            if (!($Decodifica)){
                $valore=str_replace("%","%25",$valore);
            }

            $this->FListaCampi["\$".$nome.";"]=$valore;

            foreach ($this->FileInclude as $etichetta=>$oggetto){
                $this->FileInclude[$etichetta]->Parametri($nome,$valore);
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

            //	foreach ($this->FileInclude as $etichetta=>$oggetto){
            //		$oggetto->SvuotaValoriLabel($Etichetta);
            //	}
        }


        function InserisciValoriLabel($Etichetta){
            #	if ($Etichetta=="rottura"){	print "INSERISCI $Etichetta<br>\n";}

            foreach ($this->LoopZone as $chiaveloop=>$oggettoloop){

                list($ilEtichetta,$NumeroLoop)=explode("@",$chiaveloop);
                #       echo "ilTest:$ilEtichetta num:$NumeroLoop\n";

                if ($ilEtichetta==$Etichetta){
                    $unNodoLoop=$this->LoopZone[$chiaveloop];
                    #echo $Etichetta."<br>\n";
                    #       if ($Etichetta=="rottura"){		echo $Etichetta."<br>\n";}
                    $this->DatiLoop[$chiaveloop].=$this->Interpreta_XS($unNodoLoop);
                    #        if ($Etichetta=="rottura"){		print_r( strip_tags($this->DatiLoop[$Etichetta]) );}
                    #        if ($Etichetta=="rottura"){		print "FINE INSERISCI<br>";}
                }

            }

            foreach ($this->FileInclude as $etichetta=>$oggetto){
                $oggetto->InserisciValoriLabel($Etichetta);
            }

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
        #--------------------

        function InserisciValoriLabelRicorsivo($aEtichetta,$aSubEtichetta,$Livello){

            $reset = false;

            if ($Livello == 1) {

                $LoopLabels = $this->getLoopZoneLabel($aEtichetta);

                foreach ($LoopLabels as $Etichetta) {

                    $unNodoLoop=$this->LoopZone[$Etichetta];

                    if (!is_null($this->DatiLoop[$Etichetta][$Livello+1])) { 

                        if (is_array($this->DatiLoop[$Etichetta])){

                            ksort($this->DatiLoop[$Etichetta]);
                            reset($this->DatiLoop[$Etichetta]);

                            $SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);

                            foreach ($SubLoopLabels as $SubKeyLoop){
                                $this->DatiLoop[$SubKeyLoop] = array_pop($this->DatiLoop[$Etichetta]);
                            }

                        }

                    }


                    $this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);


                    $SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);

                    foreach ($SubLoopLabels as $SubKeyLoop){

                        $unSubKeyLoop=$this->LoopZone[$SubKeyLoop];
                        if ( $unSubKeyLoop->getAttribute("f") == $Etichetta ) {                        
                            $this->DatiLoop[$SubKeyLoop] = '';
                        } else {
                            $this->InserisciValoriLabel($Etichetta);
                        }

                    }

                }
                
                foreach ($this->FileInclude as $File) {
                    $File->InserisciValoriLabelRicorsivo($aEtichetta,$aSubEtichetta,$Livello);
                }

            } else {

                $SubLoopLabels = $this->getLoopZoneLabel($aSubEtichetta);
                $LoopLabels = $this->getLoopZoneLabel($aEtichetta);


                foreach ($LoopLabels as $Etichetta) {

                    if (!is_array($this->DatiLoop[$Etichetta])){ 
                        $this->DatiLoop[$Etichetta][$Livello] = NULL; 
                    }

                    if ( (is_null($this->DatiLoop[$Etichetta][$Livello+1])) ) {

                        #Prendo il nodo relativo al loop piu' profondo di livello
                        foreach ($SubLoopLabels as $SubEtichetta) {	

                            #devo controllare se il $SubEtichetta e' contenuto in $Etichetta
                            #se e' cosi' allora interpreto il nodo altrimento salto				
                            $unNodoLoop=$this->LoopZone[$SubEtichetta];
                            //$unNodoLoopPadre = $this->LoopZone[$Etichetta];

                            // Controllo se la sotto etichetta fa parte dell'eticcheta del padre
                            if ( $Etichetta==$unNodoLoop->getAttribute("f") ) {

                                #Interpreto il nodo e lo assegno al nodo relativo al loop di livello superiore
                                $this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);

                            } else {
                                // $this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);
                                $this->InserisciValoriLabel($SubEtichetta);
                            }

                        }


                    } 

                    else {

                        foreach ($SubLoopLabels as $SubEtichetta) {

                            $unSubEtichetta = $this->LoopZone[$SubEtichetta];

                            if ( $Etichetta==$unSubEtichetta->getAttribute("f") ) {

                                # Al nodo di livello inferiore assegno il valore del nodo di livello successivo faccio lo scambio dei dati
                                $this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello+1];

                                #Prendo il nodo di livello superiore
                                $unNodoLoop=$this->LoopZone[$Etichetta];

                                #Interpreto il nodo di livello superiore
                                $this->DatiLoop[$Etichetta][$Livello] .= $this->Interpreta_XS($unNodoLoop);

                                #Se esiste elimino il livello dello stesso nodo che gia' ho elaborato
                                if (is_array($this->DatiLoop[$Etichetta])){
                                    ksort($this->DatiLoop[$Etichetta]);
                                    reset($this->DatiLoop[$Etichetta]);
                                    array_pop($this->DatiLoop[$Etichetta]);
                                }


                                #Ripeto lo scambio

                                $this->DatiLoop[$SubEtichetta] = $this->DatiLoop[$Etichetta][$Livello];
                                $this->DatiLoop[$SubEtichetta] = "";

                            } else {

                                $this->InserisciValoriLabel($unSubEtichetta);

                            }

                        }

                    }

                }

                
                foreach ($this->FileInclude as $File) {
                    $File->InserisciValoriLabelRicorsivo($aEtichetta,$aSubEtichetta,$Livello);
                }
                


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

        }

        function is_utf8($string) { // v1.01
            if (strlen($string) > _is_utf8_split) {
                // Based on: http://mobile-website.mobi/php-utf8-vs-iso-8859-1-59
                for ($i=0,$s=_is_utf8_split,$j=ceil(strlen($string)/_is_utf8_split);$i < $j;$i++,$s+=_is_utf8_split) {
                    if ($this->is_utf8(substr($string,$s,_is_utf8_split)))
                        return true;
                }
                return false;
            } else {
                // From http://w3.org/International/questions/qa-forms-utf-8.html
                return preg_match('%^(?:
                [\x09\x0A\x0D\x20-\x7E]            # ASCII
                | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
                |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
                )*$%xs', $string);
            }
        }         

        function compila($filehtml,$filexml,$Path){

            #	print "file html:$filehtml\n";        
            $percorsofilehtml=$Path.$filehtml;
            $handle = @fopen($percorsofilehtml, "rb");
            if (is_resource($handle)) {
                $datafile = '';
                while(!feof($handle)) {
                    $datafile .= fread($handle, 4096);
                }
            } else {
                echo "File template not found:{$percorsofilehtml}";
                exit;
            }

            fclose($handle);
            if ($this->is_utf8($datafile)) {
                //Questo codice serve per risolver il problema del BOM del file UTF-8 aggiunto all'inizio da vari programmi soprattutto in ambiente MAC
                $datafile = str_replace("\xEF\xBB\xBF", '', $datafile);
                $datafile=utf8_decode($datafile);
            }

            $stato=0;
            $lunghezzaTesto=0;
            $InizioTesto=0;
            $tag="";

            $RecursiveLoopStack = array();

            #	$xmlscript=domxml_new_doc("1.0");
            $xmlscript=new DOMDocument('1.0', 'utf-8'); 

            $root=$xmlscript->CreateElement("NWSCRIPT");
            $xmlscript->appendChild($root);

            $root->setAttribute("v",filemtime($percorsofilehtml));

            $nodo_loop=$xmlscript->CreateElement("LOOP");
            $nodo_textseg=$xmlscript->CreateElement("TEXTSEG");
            $root->appendChild($nodo_loop); 
            $root->appendChild($nodo_textseg); 

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
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #							$Nodo->set_content();

                            $nodo_textseg->appendChild($Nodo);
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
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #							$Nodo->set_content();
                            $nodo_textseg->appendChild($Nodo);

                            $lunghezzaTesto=0;
                            $InizioTesto=$i+1;
                            break;                         
                        case 10:
                            // chiusura tag else
                            #                            Compila_XS(DOScriptData,tktesto,copy(Input,InizioTesto,lunghezzaTesto));
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #							$Nodo->set_content();
                            $nodo_textseg->appendChild($Nodo);

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
                            // chiusura tag beginloop con label //
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

                #echo " $stato\n";



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
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #							$Nodo->set_content();
                            $nodo_textseg->appendChild($Nodo);

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
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #									$Nodo->set_content();
                            $nodo_textseg->appendChild($Nodo);

                            $lunghezzaTesto=0;
                            $stato=22;
                            break;

                        case "endloop":
                            $expr='';
                            $campo='';
                            $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                            #								  $Nodo->set_content();
                            $nodo_textseg->appendChild($Nodo);

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
                            $Nodo=$xmlscript->CreateElement($this->NODO_IF);
                            $Nodo->setAttribute("e",$expr);
                            $nodo_textseg->appendChild($Nodo);
                            $nodo_textseg=$nodo_textseg->lastChild;
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
                        if (($nodo_textseg->nodeName==$this->NODO_IF)){
                            $nodo_textseg=$nodo_textseg->parentNode;
                            $Nodo=$xmlscript->CreateElement($this->NODO_ELSE);
                            $nodo_textseg->appendChild($Nodo);
                            $nodo_textseg=$nodo_textseg->lastChild;

                        }

                        $campo='';
                        $lunghezzaTesto=0;
                        $InizioTesto=$i+1;
                        $tag='';
                        $expr='';
                        $stato=0;



                        break;
                        // Chiusura tag endif //
                    case 9:

                        if (($nodo_textseg->nodeName==$this->NODO_IF)or($nodo_textseg->nodeName==$this->NODO_ELSE)){
                            $nodo_textseg=$nodo_textseg->parentNode;
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
                        //                 AggiungiFiglio(DOScriptData.XS_Script,CreaNodo(NODO_INCLUDI,campo));

                        $uncampo=$this->sostituisciparametri($campo);
                        #					echo "<b>campo:$campo; uncampo:$uncampo<br></b>";
                        if ($uncampo){
                            $Nodo=$xmlscript->CreateElement($this->NODO_INCLUDI,$campo);

                            $nodo_textseg->appendChild($Nodo);

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
                        $Nodo=$xmlscript->CreateElement($this->NODO_LABEL);
                        $nodo_textseg->appendChild($Nodo);
                        $nodo_textseg=$nodo_textseg->lastChild;

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
                        // chiusura tag beginloop con label//
                    case 30:
                        #					echo "30<br>";
                        $Nodo=$xmlscript->CreateElement($this->NODO_LABEL);
                        $Nodo->setAttribute("n","$expr@$i");

                        array_push($RecursiveLoopStack,"$expr@$i");

                        #				  echo "Entra {$expr}@{$i} <br>";

                        $nodo_textseg->appendChild($Nodo);	
                        #					print_r ($nodo_textseg);
                        $nodo_textseg=$nodo_textseg->lastChild;
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

                        #				  print $nodo_textseg->nodeName."\n";

                        if ( $nodo_textseg->nodeName == $this->NODO_LABEL ) {

                            if ($nodo_textseg->hasAttributes()) {

                                // Tolgo il nodo corrente
                                array_pop($RecursiveLoopStack);

                                if (count($RecursiveLoopStack)>0) {
                                    $NodoPadre = $RecursiveLoopStack[count($RecursiveLoopStack)-1];
                                    #							  var_dump($NodoPadre);
                                    $nodo_textseg->setAttribute("f",$NodoPadre);
                                }

                                $campo=$nodo_textseg->getAttribute("n");

                                list($nomeloop,$valorerigaloop)=explode("@",$campo);

                                $nodo_loop->appendChild($nodo_textseg->cloneNode(true));
                                $nodo_rimuovere=$nodo_textseg;
                                $nodo_textseg=$nodo_textseg->parentNode;
                                $child=$nodo_textseg->removeChild($nodo_rimuovere);

                                $Nodo=$xmlscript->CreateElement($this->NODO_PARAMETRO,$this->sostituiscientita($nomeloop));
                                #						  $Nodo->set_content();

                                $Nodo->setAttribute("l",$valorerigaloop);

                                $nodo_textseg->appendChild($Nodo);	

                                #						  echo "Esci<br>";echo "<pre>";print_r($RecursiveLoopStack);echo "</pre>";

                            } else {

                            }

                        }


                        $lunghezzaTesto=0;
                        $InizioTesto=$i+1;
                        $campo='';
                        $tag='';
                        $expr='';
                        $stato=0;
                        $insieme='';


                        break;


                }


                /*
                case stato of
                // Chisura token include //               
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
                #echo "lt:$InizioTesto\t$lunghezzaTesto\t";


            }// FINE WHILE

            if ($lunghezzaTesto>0){
                $Nodo=$xmlscript->CreateElement($this->NODO_TESTO,$this->sostituiscientita(substr($datafile,$InizioTesto,$lunghezzaTesto)));
                $nodo_textseg->appendChild($Nodo);
            }

            $PercorsoFileCompilato=$Path.$filexml;
            $xmlscript->save($PercorsoFileCompilato);

        }




    }#FINE CLASSE



?>
