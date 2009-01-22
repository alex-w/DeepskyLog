<?php
// new_site.php
// allows the user to add a new site

$sort=$objUtil->checkGetKey('sort','name');
if(!$min) $min=$objUtil->checkGetKey('min',0);
// the code below looks very strange but it works
if((isset($_GET['previous'])))
  $orig_previous = $_GET['previous'];
else
  $orig_previous = "";
$sites=$objLocation->getSortedLocations($sort, $_SESSION['deepskylog_id']);
$locs =$objObserver->getListOfLocations();
if((isset($_GET['sort'])) && $_GET['previous'] == $_GET['sort']) // reverse sort when pushed twice
{ if ($_GET['sort'] == "name")
  { $sites = array_reverse($sites, true);
  }
  else
  { krsort($sites);
    reset($sites);
  }
  $previous = ""; // reset previous field to sort on
}
else
{ $previous = $sort;
}
$step = 25;

$timezone_identifiers = DateTimeZone::listIdentifiers();
$tempTimeZoneList="<select name=\"timezone\" class=\"inputfield requiredField\">";
while(list ($key, $value) = each($timezone_identifiers))
{ if (array_key_exists('locationid',$_GET) && $_GET['locationid'])
	{ if ($value == $objLocation->getLocationPropertyFromId($_GET['locationid'],'timezone'))
	    $tempTimeZoneList.="<option value=\"$value\" selected>$value</option>";
	  else
	    $tempTimeZoneList.="<option value=\"$value\">$value</option>";
	}
	else if ($value == "UTC")
	  $tempTimeZoneList.="<option value=\"".$value."\" selected>".$value."</option>";
	else
	  $tempTimeZoneList.="<option value=\"".$value."\">".$value."</option>";
}
$tempTimeZoneList.="</select>";

$tempCountryList="<select name=\"country\" class=\"inputfield requiredField\">";
$countries = $objLocation->getCountries();
$tempCountryList.="<option value=\"\"></option>";
while(list ($key, $value) = each($countries))
{ if(array_key_exists('country',$_GET) && ($_GET['country'] == $value))
	  $tempCountryList.="<option selected=\"selected\" value=\"".$value."\">".$value."</option>";
	elseif(array_key_exists('locationid',$_GET)&&($objLocation->getLocationPropertyFromId($_GET['locationid'],'country')==$value))
	  $tempCountryList.="<option selected=\"selected\" value=\"".$value."\">".$value."</option>";
	else
    $tempCountryList.="<option value=\"".$value."\">".$value."</option>";
}
$tempCountryList.="</select>";
$latitudedeg='';
$latitudemin='';
$longitudedeg='';
$longitudemin='';
if(array_key_exists('latitude',$_GET) && $_GET['latitude'] || array_key_exists('locationid',$_GET) && $_GET['locationid'])
{ if (array_key_exists('latitude',$_GET))
    $latitudestr = decToString($_GET['latitude'], 1);
  else
	$latitudestr = decToString($objLocation->getLocationPropertyFromId($_GET['locationid'],'latitude'), 1);
  $latarray = explode("&deg;", $latitudestr);
  $latitudedeg = $latarray[0];
  $latitudemin = $latarray[1];
}
if(array_key_exists('longitude',$_GET) && $_GET['longitude'] || array_key_exists('locationid',$_GET) && $_GET['locationid'])
{ if (array_key_exists('longitude',$_GET))
      $longitudestr = decToString($_GET['longitude'], 1);
  else
    $longitudestr = decToString($objLocation->getLocationPropertyFromId($_GET['locationid'],longitude'), 1);
  $longarray = explode("&deg;", $longitudestr);
  $longitudedeg = $longarray[0];
  $longitudemin = $longarray[1];
}


echo "<div id=\"main\">\n<h2>".LangOverviewSiteTitle."</h2>";
$link=$baseURL."index.php?indexAction=add_site&amp;sort=" . $sort . "&amp;previous=" . $orig_previous;
list($min,$max)=$objUtil->printListHeader($sites, $link, $min, $step, "");
echo "<table width=\"100%\">";
echo "<tr class=\"type3\">";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=name&amp;previous=$previous\">".LangViewLocationLocation."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=region&amp;previous=$previous\">".LangViewLocationProvince."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=country&amp;previous=$previous\">".LangViewLocationCountry."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=longitude&amp;previous=$previous\">".LangViewLocationLongitude."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=latitude&amp;previous=$previous\">".LangViewLocationLatitude."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=timezone&amp;previous=$previous\">".LangAddSiteField6."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=limitingMagnitude&amp;previous=$previous\">".LangViewLocationLimMag."</a></td>";
echo "<td><a href=\"".$baseURL."index.php?indexAction=add_site&amp;sort=skyBackground&amp;previous=$previous\">".LangViewLocationSB."</a></td>";
echo "<td>".LangViewLocationStd."</td>";
echo "<td></td>";
echo "</tr>";
echo "<form action=\"".$baseURL."index.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"indexAction\" value=\"validate_site\" />";
$count = 0;
if ($sites != null)
{ while(list ($key, $value) = each($sites))
  { $sitename = stripslashes($objLocation->getLocationPropertyFromId($value,'name'));
    $region = stripslashes($objLocation->getLocationPropertyFromId($value,'region'));
    $country = $objLocation->getLocationPropertyFromId($value,'country');
    if($objLocation->getLocationPropertyFromId($value,'longitude') > 0)
    { $longitude = "&nbsp;" . decToString($objLocation->getLocationPropertyFromId($value,'longitude'));
    }
    else
    { $longitude = decToString($objLocation->getLocationPropertyFromId($value,'longitude'));
    }
    if($objLocation->getLocationPropertyFromId($value,'latitude') > 0)
    { $latitude = "&nbsp;" . decToString($objLocation->getLocationPropertyFromId($value,'latitude'));
    }
    else
    { $latitude = decToString($objLocation->getLocationPropertyFromId($value,'latitude'));
    }
    $timezone = $objLocation->getLocationPropertyFromId($value,'timezone');
    $observer = $objLocation->getLocationPropertyFromId($value,'observer');
    $limmag = $objLocation->getLocationPropertyFromId($value,'limitingMagnitude');
    $sb = $objLocation->getLocationPropertyFromId($value,'skyBackground');
    if ($limmag < -900 && $sb > 0)
    { $limmag = sprintf("%.1f", $objContrast->calculateLimitingMagnitudeFromSkyBackground($sb));
    } else if ($limmag < -900 && $sb < -900) {
      $limmag = "&nbsp;";
      $sb = "&nbsp;";
    } else {
      $sb = sprintf("%.1f", $objContrast->calculateSkyBackgroundFromLimitingMagnitude($limmag));
    }
    if ($value != "1")
    { echo "<tr class=\"type".(2-($count%2))."\">";
      echo "<td><a href=\"".$baseURL."index.php?indexAction=adapt_site&amp;location=".urlencode($value)."\">".$sitename."</a></td>";
      echo "<td>".$region."</td>";
      echo "<td>".$country."</td>";
      echo "<td>".$longitude."</td>";
      echo "<td>".$latitude."</td>";
      echo "<td>".$timezone."</td>";
      echo "<td>".$limmag."</td>";
      echo "<td>".$sb."</td>";
      echo "<td><input type=\"radio\" name=\"stdlocation\" value=\"". $value ."\"".(($value==$objObserver->getStandardLocation($_SESSION['deepskylog_id']))?" checked ":"")." />&nbsp;<br></td>";
      // check if there are no observations made from this location
      $queries = array("location" => $value, "observer" => $_SESSION['deepskylog_id']);
      $obs = $objObservation->getObservationFromQuery($queries, "D", "1");
      $comobs = $objCometObservation->getObservationFromQuery($queries, "", "1", "False");
			echo "<td>";
      if(!sizeof($obs) > 0 && !in_array($value, $locs) && !sizeof($comobs) > 0) // no observations from location yet
      { echo("<a href=\"".$baseURL."index.php?indexAction=validate_delete_location&amp;locationid=" . urlencode($value) . "\">" . LangRemove . "</a>");
      }
      echo "</td>";
			echo "</tr>";
      $count++;
    }
  }
}
echo "</table>";
echo "<input type=\"hidden\" name=\"adaption\" value=\"1\">";
echo "<input type=\"submit\" name=\"adapt\" value=\"" . LangAddSiteStdLocation . "\" />";
echo "</form>";
list($min, $max) = $objUtil->printNewListHeader($sites, $link, $min, $step, "");
echo "</div>";
echo "<hr />";
echo "<h2>".LangAddSiteTitle."</h2>";
echo "<ol>";
echo "<li value=\"1\">".LangAddSiteExisting;
echo "<table width=\"100%\">";
echo "<tr>";
echo "<td width=\"25%\">";
echo "<form name=\"overviewform\">";
echo "<select onchange=\"location = this.options[this.selectedIndex].value;\" name=\"catalog\">";
$sites = $objLocation->getSortedLocations('name');
while(list($key,$value)=each($sites))
  echo "<option value=\"".$baseURL."index.php?indexAction=add_site&amp;locationid=".urlencode($value)."\">" . $objLocation->getLocationPropertyFromId($value,'name') . "</option>";
echo "</select>";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</li>";
echo "</ol>";
echo "<p>";
echo LangAddSiteFieldOr;
echo "</p>";
echo "<ol>";
echo "<li value=\"2\">";
echo "<a href=\"".$baseURL."index.php?indexAction=search_sites\">".LangAddSiteFieldSearchDatabase."</a>";
echo "</li>";
echo "</ol>";
echo "<p>";
echo LangAddSiteFieldOr;
echo "</p>";
echo "<ol>";
echo "<li value=\"3\">".LangAddSiteFieldManually."</li>";
echo "</ol>";
echo "<form action=\"".$baseURL."index.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"indexAction\" value=\"validate_site\" />";
echo "<table>";
tableFieldnameFieldExplanation(LangAddSiteField1,
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"64\" name=\"sitename\" size=\"30\" value=\"".stripslashes($objUtil->checkGetKey('sitename')).stripslashes($objLocation->getLocationPropertyFromId($objUtil->checkGetKey('locationid'),'name'))."\" />",
                               '');
tableFieldnameFieldExplanation(LangAddSiteField2,
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"64\" name=\"region\" size=\"30\" value=\"".stripslashes($objUtil->checkGetKey('region')).stripslashes($objLocation->getLocationPropertyFromId($objUtil->checkGetKey('locationid'),'region'))."\" />",
                               LangAddSiteField2Expl);
tableFieldnameFieldExplanation(LangAddSiteField3,$tempCountryList,'');
tableFieldnameFieldExplanation(LangAddSiteField4,
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"3\" name=\"latitude\" size=\"3\" value=\"".
                                (((array_key_exists('latitude',$_GET) && $_GET['latitude']) || (array_key_exists('locationid',$_GET) && $_GET['locationid']))?$latitudedeg:"").
                                "\" />&deg;&nbsp;".
                                "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"2\" name=\"latitudemin\" size=\"2\"	value=\"".
                                (((array_key_exists('latitude',$_GET) && $_GET['latitude']) || (array_key_exists('locationid',$_GET) && $_GET['locationid']))?$latitudemin:"").
                                "\" />&#39;",
                                LangAddSiteField4Expl);
tableFieldnameFieldExplanation(LangAddSiteField5,
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"4\" name=\"longitude\" size=\"4\" value=\"".
                               (((array_key_exists('longitude',$_GET) && $_GET['longitude']) || (array_key_exists('locationid',$_GET) && $_GET['locationid']))?$longitudedeg:"").
                               "\" />&deg;&nbsp;".
                               "<input type=\"text\" class=\"inputfield requiredField\" maxlength=\"2\"	name=\"longitudemin\" size=\"2\" value=\"".
                               (((array_key_exists('longitude',$_GET) && $_GET['longitude']) || (array_key_exists('locationid',$_GET) && $_GET['locationid']))?$longitudemin:"").
                               "\" />&#39;</td>",
                               LangAddSiteField5Expl);
tableFieldnameFieldExplanation(LangAddSiteField6,$tempTimeZoneList,'');
tableFieldnameFieldExplanation(LangAddSiteField7,
                               "<input type=\"text\" class=\"inputfield\" maxlength=\"5\" name=\"lm\" size=\"5\" value=\"".(($objLocation->getLocationPropertyFromId($objUtil->checkGetKey('locationid'),'limitingMagnitude')>-900)?$objLocation->getLocationPropertyFromId($_GET['locationid'],'limitingMagnitude'):"")."\" />",
                               LangAddSiteField7Expl);
tableFieldnameFieldExplanation(LangAddSiteField8,
                               "<input type=\"text\" class=\"inputfield\" maxlength=\"5\" name=\"sb\" size=\"5\" value=\"".(($objLocation->getLocationPropertyFromId($objUtil->checkGetKey('locationid'),'skyBackground')>-900)?$objLocation->getLocationPropertyFromId($_GET['locationid'],'skyBackground'):"")."\" />",
                               LangAddSiteField8Expl);
echo "</table>";
echo "<hr />";
echo "<input type=\"submit\" name=\"add\" value=\"".LangAddSiteButton."\" /></td>";
echo "</form>";
echo "</div>";
?>
