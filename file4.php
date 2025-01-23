<?php

//connection info https://www.w3schools.com/PHP/php_mysql_connect.asp
$servername = "localhost";
$username = "root";
$password = "";
$database = "test";
//$ptid = REQUEST[
//$THEPT = 841698252; //newfile4 //841058487  842223459  740121486
//$THEPT = 841058487;
//$THEPT = 842223459;  //newfile3
//$THEPT = 740806409; //newfile5 //740121486; //
//$THEPT = 740121486; //newfile6


//$THEPT = 737393665; //newfile7
//740806409; 
//$THEPT = 740299821; //newfile8
$THEPT =737684263; //newfile10
/*842698196
738306681
741306040
841703271
740106235
738447515
841448781
075241762
841698252
842279823
741713839
841450555
841401073
075349370
075221957
738601292
075053130
841698252
841058487
842223459
740121486
738447444
000144045
737738501
841718041
841810434
841338830
*/
// Create connection
$conn = mysqli_connect($servername, $username, $password, $database) or die("Connection Failed");




echo "Connected successfully<br>";

//startthe loop to get all ptn mrns from demographics
$pat_mrn_idqry = mysqli_query($conn,"select PAT_MRN_ID from demographics");
while($row = mysqli_fetch_assoc($pat_mrn_idqry)){
$THEPT = $row["PAT_MRN_ID"];




$myfile = fopen("newfile".$THEPT.".txt", "a") or die("Unable to open file!");  //file is opened for appending; change "a" to "w" if writing
//$query = mysqli_query($conn, "select * from myguests");

$alchxqry = mysqli_query($conn, "select * from alchx where PAT_MRN_ID = " . $THEPT . " order by CONTACT_DATE");

//PAT_MRN_ID 	CONTACT_DATE 	ALCOHOL_DRINKS_WK 	HX_DRINK_TYPE

$PTID = ""; $prevptid = "";
$DRINKSSTR = "";
$DRINKTYPESTR = "";
while($row = mysqli_fetch_assoc($alchxqry)){
		
	$DRINKSSTR = $DRINKSSTR . $row["ALCOHOL_DRINKS_WK"] . "," ;
	$DRINKTYPESTR = $DRINKTYPESTR . $row["HX_DRINK_TYPE"] . ",";
		
	}
	$thestr = "\n\n alchx \n" . "drinkstr:". $DRINKSSTR . "\n"; // . "drinktype:" . $DRINKTYPESTR . "\n\n";
	echo $thestr;
	
	fwrite($myfile, $thestr) ;
	
	//get smok hx
	
	$smokhxqry = mysqli_query($conn, "select * from smokhx where PAT_MRN_ID = " . $THEPT . " order by CONTACT_DATE");
	$smok_tob_use = "";
	$paksday = ""; //$row["TOBACCO_PAK_PER_DAY"];
	$smokyears = ""; //$row["TOBACCO_USED_YEARS"];
	//$smoktypes = $row[""];
	while($row = mysqli_fetch_assoc($smokhxqry)){
		if ($paksday > 0) {
	$paksday .= $row["TOBACCO_PAK_PER_DAY"] . ",";
		}
		if ($smokyears > 0){
	$smokyears .= $row["TOBACCO_USED_YEARS"]. ",";
		}
		$smok_tob_use .=$row["SMOKING_TOB_USE"]. ",";
	}
	$smokstr = "\n\n smokhx \n" . "paksdaystr:". $paksday . "\n" . "$smokyears:" . $smokyears . "\n" . "smokuse:" . $smok_tob_use . "\n\n";
	echo $smokstr;
	fwrite($myfile,$smokstr ) ;
	
	
	//get labs
	$labqry = mysqli_query($conn, "select distinct * from labresult where PAT_MRN_ID = " . $THEPT . " order by RESULT_DATE");  //distinct is only there as our batch of data had duplicates for every row
	
	////$labsql = "select * from labresult where PAT_MRN_ID = " . $THEPT . " order by RESULT_DATE";
	//$result = $conn -> query($labsql);
	//$row = $result -> fetch_assoc();
	//echo $row["COMPONENT_NAME"] . ":" . $row["ORD_NUM_VALUE"] . "<br>";
	
	$labstr=""; $html="";
	
	while($row = mysqli_fetch_assoc($labqry)){
	//$labname = $row["COMPONENT_NAME"];
	//$labresult = $row["ORD_NUM_VALUE"];
	//$labstr = $labstr . $labname . ":" . $labresult .  "\n";
	//$htmlstr .= $labname . ":xx" . $labresult . "<br>";
	//$htmlstr = $htmlstr.$row["COMPONENT_NAME"] . ": " . $row["ORD_NUM_VALUE"] ."  ". $row["ORD_VALUE"]."<br>";
	
	$html .= $row["COMPONENT_NAME"] . ": " . $row["ORD_NUM_VALUE"] ."  ". $row["ORD_VALUE"]."<br>";
	$labstr .= $row["COMPONENT_NAME"] . ": " . $row["ORD_NUM_VALUE"]  ."\n";//. $row["ORD_VALUE"]."\n";
	}
	//*/
	//echo $labstr;
	//echo "htmlstr=".$htmlstr;
	echo $html;
	//fwrite($myfile,$html) ;
	$labstr = "lab section\n" . $labstr . "end of lab section\n\n";
	fwrite($myfile,$labstr) ;
	
	//get meds
	
	
	//$medsql = "SELECT * FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where  PAT_MRN_ID = " . $THEPT . " ORDER BY currmeds2.MEDICATION_NAME, currmeds2.CONTACT_DATE";  ///FAILED AS PAT_MRN_ID AMBIGUOUS
	
	//$medsql = "SELECT * FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME as medname = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where  CURRMEDS2.PAT_MRN_ID = 841698252 ORDER BY currmeds2.MEDICATION_NAME, currmeds2.CONTACT_DATE"; //WORKS IN MYSQL if done without 'as medname'.
	
	$medsql = "SELECT distinct currmeds2.medication_name, dose, contact_date,  FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)"; //works in mysql, just get relevant cols in order to avoid error 
	
	//get only last year, and without drugs with a discontinuation date
	$medsql = "SELECT distinct currmeds2.medication_name, dose, contact_date, end_date FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(currmeds2.CONTACT_DATE)> (now()-1yr) AND end_date is null  (ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)"; 
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(currmeds2.CONTACT_DATE)> 2020/01/01 AND end_date is null ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//above is valid sql in mysql
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y')> date('2020/01/01') AND end_date is null ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//above runs without errors, but no data
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(str_to_date(end_date,'%m/%d/%Y'))>date('2020-01-01') ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//above works and retrieves data! but is incorrect as retrieves dicontinued meds
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') AND (date(str_to_date(end_date,'%m/%d/%Y'))>date('2020-12-31') OR end_date is null)  ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//incorrect, has error in retrieved
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//works, does not exclude those with end date
	
//	SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') AND end_date ='' ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//works, includes only those with end_date=''
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where CURRMEDS2.PAT_MRN_ID = 841698252 and date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') AND (end_date ='' OR end_date > date(12-12-01)) ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//works and retrieves, not sure if retrieves those with end_date > 12/31/20, this pt doesn't have those
	
	//SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') AND date(str_to_date(end_date,'%m/%d/%Y')) > date('20-12-31') ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)
	//test, works (not specific pt id) - just 2 records where end_date > 12/31/20, flags all the recs where end_date=''
	
	$medsql = "SELECT distinct currmeds2.medication_name, dose, contact_date, end_date,str_to_date(currmeds2.CONTACT_DATE,'%m/%d/%Y') FROM currmeds2 INNER JOIN MEDORDERS ON currmeds2.PAT_MRN_ID = medorders.PAT_MRN_ID AND currmeds2.MEDICATION_NAME = medorders.MEDICATION_NAME AND currmeds2.CONTACT_DATE = medorders.ORDERING_DATE where currmeds2.PAT_MRN_ID = ". $THEPT ." AND date(str_to_date(CONTACT_DATE,'%m/%d/%Y'))>date('2020-01-01') AND (date(str_to_date(end_date,'%m/%d/%Y')) > date('20-12-31') OR end_date = '') ORDER BY currmeds2.MEDICATION_NAME, date(currmeds2.CONTACT_DATE)";
	//works, some date warnings, shows for all pts where end_date is '' or > 12/31/20";
	
	$medqry = mysqli_query($conn,$medsql);
	$medstr="";
	while($row = mysqli_fetch_assoc($medqry)){
	//	$medstr .= $row["medname"] ; //."  ". $row["DOSE"] ."  ". $row["INSTRUCTIONS"] ."<br>";  //must use the alias stated in the sql; this is required when there are two cols with same name, the result set can only be specified by the alias see https://www.sitepoint.com/community/t/undefined-index-php-mysql-when-trying-to-query-database/118078
	//	$medstr .= $row{"medication_name"} $row["DOSE"] ."  ". $row["INSTRUCTIONS"] ."<br>";
		$medstr .= $row["medication_name"]."   " . $row["dose"] ."  ". $row["contact_date"] ."\n"; //NB: these names are case sensitive!
	}
	
	echo $medstr;  //this gives meds in date order, may need to do calcs to get dosage for some thing
	$medstr = "start of meds\n" . $medstr . "end of meds\n\n";
	fwrite($myfile,$medstr) ;
	
	//get problist
	$probsql = "select DISTINCT * from problist where pat_mrn_id = ".$THEPT." and problem_status = 'ACTIVE' order by date(onset_date) desc";
	
	$probqry = mysqli_query($conn,$probsql);
	$probstr="";
	while($row = mysqli_fetch_assoc($probqry)){
		$probstr .= $row["DX_NAME"] ." ". $row["DX_CODE"] ." ". $row["ONSET_DATE"] ."<br>";
	}
	echo $probstr;
	fwrite($myfile,$probstr) ;
	
	//GET DIAGNOSES
	//$dxsql = "select DISTINCT * from diagnoses where pat_mrn_id = ".$THEPT."  order by contact_date desc";
	
	$dxsql = "select DISTINCT DX_NAME,DX_CODE,CONTACT_DATE from diagnoses where pat_mrn_id = ".$THEPT."  order by DX_NAME, date(contact_date) desc"; //use only distinct dxname and code to remove repeats  NB: date desc is not working correctly, may have to correct sql to include (date(contact_date)) or whatever, see PCOS entry for example
	
	$dxqry = mysqli_query($conn,$dxsql);  //PAT_MRN_ID 	DX_TYPE 	DX_CODE 	DX_NAME 	PRIMARY_DX_YN 	CONTACT_DATE
	$dxstr="DIAGNOSES: <br><br>";
	while($row = mysqli_fetch_assoc($dxqry)){
		$dxstr .= $row["DX_NAME"] ." ". $row["DX_CODE"] ." ". $row["CONTACT_DATE"] ."<br>";
	}
	echo $dxsql;
	echo $dxstr;
	fwrite($myfile,$dxstr) ;
	
	//get nonlab
	
	$nonlabsql = "select DISTINCT * from nonlab where pat_mrn_id = ".$THEPT."   order by date(result_date) desc";
	
	$nonlabqry = mysqli_query($conn,$nonlabsql);
	$nonlabstr="";
	while($row = mysqli_fetch_assoc($nonlabqry)){
		$nonlabstr .= $row["COMPONENT_NAME"] ." ". $row["RESULT_DATE"] ." ". $row["ORD_NUM_VALUE"] ." ". $row["ORD_VALUE"] ." ". $row["RESULT_FLAG"]. "<br>";
	}
	echo $nonlabstr;
	fwrite($myfile,$nonlabstr) ;
	//*/
	
	//GET ENCNOTES
	//SELECT * FROM `encnotes` WHERE PAT_MRN_ID = 841698252
	$encnotessql = "select DISTINCT * from encnotes where pat_mrn_id = ".$THEPT."  order by date(contact_date) desc";
	
	$encnotesqry = mysqli_query($conn,$encnotessql);
	$encnotesstr="";
	while($row = mysqli_fetch_assoc($encnotesqry)){
		$encnotesstr .= $row["LINE"] ." ". $row["NOTE_TEXT"] ." ". $row["CONTACT_DATE"] ."<br><BR>";
	}
//	echo $encnotesstr;
	
	
	//get vitals
	//PAT_MRN_ID	CONTACT_DATE	PAT_ENC_HEIGHT	HEIGHT_IN	WEIGHT	WEIGHT_LBS	BMI	BP_SYSTOLIC	BP_DIASTOLIC	BP_POSITION	PULSE	TEMPERATURE	RESPIRATIONS
	$vitalssql = "select * from vitals where PAT_MRN_ID = ".$THEPT."  order by date(contact_date) desc";
	$vitalsqry = mysqli_query($conn,$vitalssql);
	$vitalsstr=""; $heightstr=""; $weightstr=""; $bmistr = ""; $systolicstr=""; $diastolicstr=""; $pulse="";
	while($row = mysqli_fetch_assoc($vitalsqry)){
		$vitalsstr .= $row["PAT_ENC_HEIGHT"] ." ". $row["HEIGHT_IN"] ." ". $row["WEIGHT_LBS"]." " .$row["BMI"] ." ".$row["BP_SYSTOLIC"] ." ". $row["BP_DIASTOLIC"] ." ". $row["BP_POSITION"] ." ". $row["PULSE"] ." ". $row["TEMPERATURE"] ." ". $row["RESPIRATIONS"] ." ".$row["CONTACT_DATE"] ."<br><BR>";
	
	//$laststr = ;
	$heightstr .= $row["HEIGHT_IN"].",";
	$weightstr .= $row["WEIGHT_LBS"].",";
	$bmistr .=$row["BMI"] .",";
	$systolicstr .=$row["BP_SYSTOLIC"] .",";
	$diastolicstr .= $row["BP_DIASTOLIC"] .",";
	$pulse.= $row["PULSE"] .",";
	
	}
	ECHO $vitalsstr ;
	echo $weightstr ."weight<br>";
	echo $bmistr ."bmi<br>";
	echo $systolicstr. "syst<br>";
	echo $diastolicstr . "diast<br>";
	echo $pulse ."pulse<br>";
	$vitalsstr = "\n\nstart of vitals\n" . $vitalsstr . "\nend of vitals\n\n";
	fwrite($myfile,"vitalstr:".$vitalsstr."\n") ;
	fwrite($myfile,"weightstr:".$weightstr."\n") ;
	fwrite($myfile,"bmistr:".$bmistr."\n") ;
	fwrite($myfile,"systolicstr:".$systolicstr."\n") ;
	fwrite($myfile,"diastolicstr:".$diastolicstr."\n") ;
	fwrite($myfile,"pulsestr:".$pulse."\n") ;
	
	//get demographics (from demographics, also race, gender, ethnicity (hispanic etc))
	//$demogrsql = "select BIRTH_DATE, GENDER, RACE, FROM DEMOGRAPHICS WHERE PAT_MRN_ID = " . $THEPT ; 
	$demogrsql = "select * FROM demographics WHERE PAT_MRN_ID = " . $THEPT ; 
	//$demogrsql="select * FROM demographics WHERE PAT_MRN_ID = '740121486' ";
	$demogrqry = mysqli_query($conn,$demogrsql);
	$birthdatestr = ""; $gender=""; $race="";
	while($row = mysqli_fetch_assoc($demogrqry)){
	echo $row["BIRTH_DATE"] . $row["GENDER"] . $row["RACE"]; //problem, it is not retrieving this patient at all
	$birthdatestr .= "\n\nbirthdate:".$row["BIRTH_DATE"];
	$gender .= "\ngender:".$row["GENDER"];
	$race .= "\nrace:".$row["RACE"];
	}
	echo $demogrsql;
	ECHO $birthdatestr;
	echo $gender;
	echo $race;
	fwrite($myfile,$birthdatestr);
	fwrite($myfile,$gender);
	fwrite($myfile,$race);
	
	//get diagnoses (esp. to get the diab yes/no, htn tx yes/no, and ifg yes/no for the ascvd)
	$dxsql2 = "select distinct DX_NAME from diagnoses where PAT_MRN_ID= ".$THEPT;
	echo $dxsql2;
	 $dxstr2 = "";
	$dxqry2 = mysqli_query($conn,$dxsql2);
	while($row = mysqli_fetch_assoc($dxqry2)){
	//$dxstr2 .=$row["DX_CODE"]." ".$row["DX_NAME"]." ".$row["PRIMARY_DX_YN"]." ".$row["CONTACT_DATE"] ."\n";	
		$dxstr2 .=$row["DX_NAME"]."\n";
	}	
	echo $dxstr2;
	fwrite($myfile, "\n\nDiagnoses2\n".$dxstr2."end of diagnoses\n\n");
	
	/*
echo $row["id"].",";
echo $row["firstname"].",";
echo $row["lastname"].",";
echo $row["email"].",";
echo $row["regdate"].",";
echo "<hr>";

$txt = $row["id"].",";
fwrite($myfile, $txt);
$txt = $row["firstname"].",";
fwrite($myfile, $txt);
$txt = $row["lastname"].",";
fwrite($myfile, $txt);
$txt = $row["email"].",";
fwrite($myfile, $txt);
$txt = $row["regdate"].",\n";
fwrite($myfile, $txt);
*/
//}


//list sqls



for ($x = 0; $x <= 10; $x++) {
  echo "The number is: $x <br>";
} 

fclose($myfile);

} //END OF MAKING ONE FILE FOR THE PATIENT

$conn->close();



?>