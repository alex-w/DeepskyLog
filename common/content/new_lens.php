<?php // new_filter.php - allows the user to add a new filter
if((!isset($inIndex))||(!$inIndex)) include "../../redirect.php";
elseif(!$loggedUser) throw new Exception(LangException002);
else
{
echo "<div id=\"main\">";
$objLens->showLensesObserver();
$lns=$objLens->getSortedLenses('name');
echo "<form action=\"".$baseURL."index.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"indexAction\" value=\"validate_lens\" />";
$content1b= "<select onchange=\"location = this.options[this.selectedIndex].value;\" name=\"catalog\">";
while(list($key, $value) = each($lns))
  $content1b.= "<option value=\"".$baseURL."index.php?indexAction=add_lens&amp;lensid=".urlencode($value)."\" ".(($value==$objUtil->checkGetKey('lensid'))?' selected=\"selected\" ':'').">".$objLens->getLensPropertyFromId($value,'name')."</option>";
$content1b.= "</select>&nbsp;";
$objPresentations->line(array("<h5>".LangAddLensTitle."</h5>"),"L",array(),50);
echo "<hr />";
$objPresentations->line(array(LangAddLensExisting,
                              $content1b,
                              "<input type=\"submit\" name=\"add\" value=\"".LangAddLensButton."\" />&nbsp;"),
                        "RLR",array(25,40,35),'',array("fieldname"));                              
$objPresentations->line(array(LangAddSiteFieldOr." ".LangAddLensFieldManually),"R",array(25),'',array("fieldname"));

$objPresentations->line(array(LangAddLensField1,
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"64\" name=\"lensname\" size=\"30\" value=\"".stripslashes($objUtil->checkGetKey('lensname','')).stripslashes($objLens->getLensPropertyFromId($objUtil->checkGetKey('lensid'),'name'))."\">",
                               LangAddLensField1Expl),
                        "RLL",array(25,40,35),'',array("fieldname","fieldvalue","fieldexplanation"));
$objPresentations->line(array(LangAddLensField2,
                               "<input type=\"text\" class=\"inputfield requiredField centered\" maxlength=\"5\" name=\"factor\" size=\"5\" value=\"".stripslashes($objUtil->checkGetKey('factor','')).stripslashes($objLens->getLensPropertyFromId($objUtil->checkGetKey('lensid'),'factor'))."\" />",
                               LangAddLensField2Expl),
                        "RLL",array(25,40,35),'',array("fieldname","fieldvalue","fieldexplanation"));
echo "<hr />";
echo "</form>";
echo "</div>";
}
?>