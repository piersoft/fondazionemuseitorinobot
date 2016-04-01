	<?php
/**
* Telegram Bot Fondazione Torino Musei Lic. CC-BY 3.0 Powered by Francesco "Piersoft" Paolicelli
*/

include("Telegram.php");
include("settings_t.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
if (strpos($text,'/start') === false ){
//	$text =str_replace("/","",$text);
}
if (strpos($text,'@fondazionetorinomusei') !== false) $text =str_replace("@fondazionetorinomusei ","",$text);
	if ($text == "/start" || $text == "Informazioni") {
		$img = curl_file_create('logo.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$reply = "Benvenuto. Questo è un servizio automatico di ricerca delle opere d'arte raccolte dalla ".NAME." http://opendata.fondazionetorinomusei.it/ e rilasciate con licenza Creative Commons Italia 3.0 ( http://creativecommons.org/licenses/by/3.0/it/ ). In questo bot puoi ricercare le opere o gli autori per parola chiave. In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot è stato realizzato da @piersoft e non è collegato in alcun modo con la Fondazione Torino Musei. Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ",new_info,," .$chat_id. "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
	}elseif($text == "/ricerca" || $text == "Ricerca"){
		$reply = "Ci ne sono 4 Archivi per un totale di quasi 40.000 opere, rilasciati in openData dalla Fondazione Torino Musei:\nCOLLEZIONI GAM (G?), COLLEZIONI PALAZZO MADAMA (P?), COLLEZIONI MAO (M?) e FONDO GABINIO (?F).\nPer ricercare un autore o il titolo di un'opera, devi anteporre G? P? M? o F? seguito dalla parola.\nEsempio G?Ignoto è per cercare nelle COLLEZIONI GAM la parola \"IGNOTO\".";
$reply .="\nTi verranno indicati gli autori e le opere trovate. Potrai cliccare direttamente sul codice indicato per avere dettagli e se disponibile l'immagine dell'opera";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
	}
		elseif(strpos($text,'?') !== false || strpos($text,'_') !== false )
		{
			$webpreview=0;
		if (strpos($text,'/') !== false){
			$webpreview=1;
			$text=str_replace("/","",$text);

		}
			$text=str_replace("9999","/",$text);
			$text=str_replace("0000",")",$text);
			$text=str_replace("000",")",$text);
			$text=str_replace("00","(",$text);
			$text=str_replace("__"," ",$text);
			$text=str_replace("_","?",$text);


		$text=strtoupper($text);
		$f=1;
		$urlgd="";
		$collezione="FONDO GABINIO";
		if (strpos($text,'F?') !== false ){
			$text=str_replace("F?","",$text);
			$urlgd  ="GABINIO.csv";
			$location="Sto cercando le opere o gli autori contenenti \"".$text."\" in ".$collezione;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		}elseif (strpos($text,'M?') !== false ){
			$text=str_replace("M?","",$text);
			$f=2;
			$collezione="COLLEZIONI MAO";
			$urlgd  ="MAO.csv";
			$location="Sto cercando le opere o gli autori contenenti \"".$text."\" in ".$collezione;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		}elseif (strpos($text,'P?') !== false ){
			$text=str_replace("P?","",$text);
			$f=3;
			$collezione="COLLEZIONI PALAZZO MADAMA";
			$urlgd  ="PALAZZOMADAMA.csv";
			$location="Sto cercando \"".$text."\" in ".$collezione;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		}elseif (strpos($text,'G?') !== false ){
			$text=str_replace("G?","",$text);
			$f=4;
			$urlgd="GAM.csv";
			$collezione="COLLEZIONI GAM";
			$location="Sto cercando le opere o gli autori contenenti \"".$text."\" in ".$collezione;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		}
		//	$text=str_replace("?","",$text);
		//	$text=str_replace("-","",$text);


			//	$text=str_replace(" ","%20",$text);

				$inizio=1;
				$homepage ="";

				$csv = array_map('str_getcsv',file($urlgd));
				$csv=str_replace(array("\r", "\n"),"",$csv);
			//	$csv=str_replace(" ","%20",$csv);
				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
				if ($count ==0){
						$location="Nessun risultato trovato";
						$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>$webpreview);
						$telegram->sendMessage($content);
					}

					function decode_entities($text)
					{

												$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
												$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
												$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
												$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
												return $text;
					}

					$result=0;
					$ciclo=0;

				for ($i=$inizio;$i<$count;$i++){
					$string="";
					$string1="";
					$string3="";
					$string4="";
					if ($webpreview==1){
							$string3=$csv[$i][2]." - ".$csv[$i][3]."\nImmagine HD se disponibile: ".$csv[$i][5]."\n";
							$string4=$csv[$i][2]." - ".$csv[$i][3]."\n".$csv[$i][5]."\nImmagine HD se disponibile: ".$csv[$i][6]."\n";

					}else $string3="";
		//		$filter=strtoupper($csv[$i][0]);
		//			$filter1=strtoupper($csv[$i][1]);


						if( $collezione=="COLLEZIONI PALAZZO MADAMA" ){
								$filter=strtoupper($csv[$i][1]);
								$filter1=strtoupper($csv[$i][4]);
								$filter2=strtoupper($csv[$i][0]);
								$string=$csv[$i][1]."\n".$csv[$i][4]."\n".$string4;
											$csv[$i][0]=str_replace("/","9999",$csv[$i][0]);
								$csv[$i][4]=str_replace("-","0000",$csv[$i][4]);
								$csv[$i][4]=str_replace("(","00",$csv[$i][4]);
								$csv[$i][4]=str_replace(")","000",$csv[$i][4]);
								$csv[$i][4]=str_replace(" ","__",$csv[$i][4]);

								if ($webpreview==0)$string1="Clicca: /P_".$csv[$i][0];
							}elseif( $collezione=="FONDO GABINIO" ){
									$filter=strtoupper($csv[$i][0]);
									$filter1=strtoupper($csv[$i][1]);
									$string=$csv[$i][0]."\n".$csv[$i][1]."\n".$string3;
										$csv[$i][1]=str_replace("-","0000",$csv[$i][1]);
									$csv[$i][1]=str_replace("(","00",$csv[$i][1]);
									$csv[$i][1]=str_replace(")","000",$csv[$i][1]);
$csv[$i][1]=str_replace(" ","__",$csv[$i][1]);
if ($webpreview==0)$string1="Clicca: /F_".$csv[$i][1];
							}elseif( $collezione=="COLLEZIONI GAM" ){
										$filter=strtoupper($csv[$i][0]);
										$filter1=strtoupper($csv[$i][1]);
											$string=$csv[$i][0]."\n".$csv[$i][1]."\n".$string3;
													$csv[$i][1]=str_replace("-","0000",$csv[$i][1]);
											$csv[$i][1]=str_replace("(","00",$csv[$i][1]);
											$csv[$i][1]=str_replace(")","000",$csv[$i][1]);
$csv[$i][1]=str_replace(" ","__",$csv[$i][1]);
if ($webpreview==0)$string1="Clicca: /G_".$csv[$i][1];
								}elseif( $collezione=="COLLEZIONI MAO" ){
											$filter=strtoupper($csv[$i][0]);
											$filter1=strtoupper($csv[$i][1]);
												$string=$csv[$i][0]."\n".$csv[$i][1]."\n".$string3;
														$csv[$i][1]=str_replace("-","0000",$csv[$i][1]);
												$csv[$i][1]=str_replace("(","00",$csv[$i][1]);
												$csv[$i][1]=str_replace(")","000",$csv[$i][1]);
$csv[$i][1]=str_replace(" ","__",$csv[$i][1]);
if ($webpreview==0)$string1="Clicca: /M_".$csv[$i][1];
										}

	if (strpos(decode_entities($filter),strtoupper($text)) !== false || strpos(decode_entities($filter2),strtoupper($text)) !== false || strpos(decode_entities($filter1),strtoupper($text)) !== false)
				{
					$ciclo++;
					$result=1;
					$homepage .="\n";
					$homepage .=$string.$string1;
				//	$homepage .="\nPer ingredienti e preparazione digita o clicca su: /".$csv[$i][0]."\n";
					$homepage .="\n____________";
				}

				if ($ciclo >100) {
					$location="Troppi risultati per essere visualizzati (più di 100). Restringi la ricerca";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);

					 exit;
				}
			}
				$chunks = str_split($homepage, self::MAX_LENGTH);
				foreach($chunks as $chunk) {
					$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>false);
					$telegram->sendMessage($content);
						}

					$log=$today. ",ricerca,".$text."," .$chat_id. "\n";
					file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);


		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
		}

	}

	function create_keyboard_temp($telegram, $chat_id)
	 {
			 $option = array(["Ricerca","Informazioni"]);
			 $keyb = $telegram->buildKeyBoard($option, $onetime=false);
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Fai la tua ricerca]");
			 $telegram->sendMessage($content);
	 }


}



	 ?>
