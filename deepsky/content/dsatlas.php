<?php
echo "<script type=\"text/javascript\" src=\"".$baseURL."lib/javascript/phpjs.js\"></script>";
echo "<script type=\"text/javascript\" src=\"".$baseURL."lib/javascript/wz_jsgraphics.js\"></script>";
echo "<script type=\"text/javascript\" src=\"".$baseURL."lib/javascript/atlaspage.js\"></script>";
$loadAtlasPage=1; // ---> to load the atlas js in index.php
$ra=0;
$decl=0;
$object=$objUtil->checkRequestKey('object');
if($object)
{ $ra=$objObject->getDsoProperty($object,'ra',0);
  $decl=$objObject->getDsoProperty($object,'decl',0);
}
echo "<script type=\"text/javascript\">";
echo "atlaspagerahr=".$ra.";atlaspagedecldeg=".$decl.";";
while(list($name,$value)=each($atlasPageText))
  echo $name."Txt='".$value."';";
echo "</script>";



echo "<div id=\"myDiv\" style=\"position:absolute;top:0px;left:0px;height:100%;width:100%;margin:0%;background-color:#000000;border-style:none;border-color:#FF0000;cursor:wait;\" onmousemove=\"canvasOnMouseMove(event);\" onclick=\"canvasOnClick(event);\" onkeydown=\"canvasOnKeyDown(event);\">";
echo "</div>"; 
echo "<div id=\"myDiv1\" style=\"position:absolute;top:0px;left:0px;height:0px;width:0px;margin:0%;background-color:#555555;background:transparant;border-style:none;border-color:#880000;cursor:none;font-size:8pt;\"\">";
echo "</div>"; 



//echo "<div id=\"gridDiv\"  style=\"position:absolute;top:120px;left:170px;height:400px;width:400px;cursor:crosshair;background-color:#ffFF00;\">&nbsp;</div>";
?>