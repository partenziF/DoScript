<?

if (version_compare(phpversion(), "5.0", ">=")) { 
  require_once("DoScript50.php"); 
} else { 
  require_once("DoScript40.php");
}

?>