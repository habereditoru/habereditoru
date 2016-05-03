<?php
header('Content-Type: text/html; charset=utf-8');

require_once preg_replace('/wp-content(?!.*wp-content).*/','',dirname(__FILE__)) ."/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/image.php';

require_once('settings.php');

 
$Islem = $_GET['Islem'] ;if (!isset($_GET['Islem'])){ $Islem = "" ;}
$HE_GET_API_KEY = $_GET['SiteKey'];
$HE_API_KEY	= HE_API_KEY;

if ($Islem=="version" ) {
	echo HE_PLUGIN_VERSION ;
	return false;
	exit ;
}

if ($HE_API_KEY!=$HE_GET_API_KEY ) {
	echo __("API_KEY Geçersiz...","HaberEditoru") ;
	return false;
	exit ;
}



$HE_ABONELIK	= get_option('HE_OPT_CRON_MINUTE') ;
$HE_BOT_ID = 0 ;if (isset($_GET['b'])){	$HE_BOT_ID 	= intval($_GET["b"]);}
$HE_NOW = time();

echo "<hr><b>".__("Çalışma Zamanı","HaberEditoru")." : ". date('Y-m-d H:i:s', time() ) ." " . __("Otomatik Çalışma","HaberEditoru") . " : ";

$HE_CRON_AKTIF = get_option('HE_OPT_CRON_AKTIF');
if ( $HE_CRON_AKTIF != false ) {
	$HE_CRON_AKTIF = true ;
	echo sprintf(__("Evet %s","HaberEditoru"),$HE_ABONELIK) ;
} else {
	echo __("Hayır","HaberEditoru") . " " ;
}

echo " </b><br>";

if ( $HE_API_KEY !="" && $HE_CRON_AKTIF ){

	/*
	$HE_LAST_CHECK = get_option('HE_LAST_CHECK');
	$HE_CRON_SN = intval($HE_ABONELIK)*60;
	$HE_CRON_UPDATE = intval($HE_LAST_CHECK)+$HE_CRON_SN;
	if (time() < $HE_CRON_UPDATE){
		echo "(!) Otomatik haber çekme " ;
		echo intval(($HE_CRON_UPDATE-time()) /60);
		echo "dk sonra çalışacak... " ;
		return false;
		exit;
	}
	*/
	
	ob_flush(); flush();

	$HE_MAX_ROBOT	= get_option('HE_MAX_ROBOT');	
	$HE_COUNTER=1;
	
	if ($HE_BOT_ID!=0 ){$HE_COUNTER = $HE_BOT_ID ; 	$HE_MAX_ROBOT = $HE_BOT_ID;	}
	for ($HE_COUNTER;$HE_COUNTER<=$HE_MAX_ROBOT;$HE_COUNTER++){
		
			$HE_BOT_ID			= "HE_BOT_".$HE_COUNTER."_";
			$HE_BOT_SETTINGS 	= get_option( $HE_BOT_ID.'SETTINGS') ;
			$HE_BOT_HEID 		= $HE_BOT_SETTINGS['HEID'];
			
			echo "<i>BOT ".$HE_COUNTER." - " .  date('Y-m-d H:i',time() ) ."</i><br>";
			
			if ($HE_BOT_SETTINGS['aktif']=="1"){
					
					// FİLTRE UYGULANIYOR
					$HE_DOMAIN	=  str_replace("https://","",str_replace("http://","",get_option('HE_DOMAIN')));
					$HE_BOT_AJANSLAR_ARR = implode(",",$HE_BOT_SETTINGS['kaynaklar']);
					$HE_BOT_KATEGORILER_ARR = implode(",",$HE_BOT_SETTINGS['kategoriler']);
					$HE_BOT_ICERIK_DILI = $HE_BOT_SETTINGS['icerik_dili'];
					$HE_BOT_ICERIK_TIPI = $HE_BOT_SETTINGS['icerik_tipi'];
					$HE_BOT_ETIKETLER = $HE_BOT_SETTINGS['etiketler'];
					$HE_BOT_ETIKETLER_NEGATIF = $HE_BOT_SETTINGS['negatif_etiketler'];
					$HE_BOT_SPINNER = $HE_BOT_SETTINGS['post_spinner'];
					
					
					$HE_GET_URL = HE_API_URL."/$HE_DOMAIN/$HE_API_KEY/?HEID=$HE_BOT_HEID&a=$HE_BOT_AJANSLAR_ARR&c=$HE_BOT_KATEGORILER_ARR&Lang=$HE_BOT_ICERIK_DILI&ContentType=$HE_BOT_ICERIK_TIPI&Tags=$HE_BOT_ETIKETLER&NegativeTags=$HE_BOT_ETIKETLER_NEGATIF&Spinner=$HE_BOT_SPINNER" ;
					
					//echo '<a target="_blank" href="'.$HE_GET_URL.'">'.$HE_GET_URL.'</a><br>';
					
					ob_flush();flush();
					
					$HE_XML = he_xmlclear(he_curl($HE_GET_URL));
					
					if ( strpos($HE_XML,'\"ERROR\"') === false  ){
						
						$HE_XML = he_curltoxml($HE_XML);
						
						$HE_SITE_ID	= get_option('HE_SITE_ID');
						$HE_CATS_ARR = get_option('HE_OPT_KAT_ESLESTIRME');
						$HE_BOT_ICERIK_ONU = $HE_BOT_SETTINGS['icerigin_onune_ekle'];
						$HE_BOT_ICERIK_SONU = $HE_BOT_SETTINGS['icerigin_sonuna_ekle'];
						$HE_BOT_CONTENT_AFTER_AGENCY_NAME = $HE_BOT_SETTINGS['kaynak_adi'];
						$HE_BOT_POST_AUTHOR = $HE_BOT_SETTINGS['site_editor'];
						$HE_BOT_POST_STATUS = $HE_BOT_SETTINGS['post_durumu'];
						$HE_BOT_POST_TYPE = $HE_BOT_SETTINGS['post_tipi'];
						$HE_BOT_POST_DATE = $HE_BOT_SETTINGS['post_date'];
						$HE_BOT_RESIM_OZEL_ALAN = $HE_BOT_SETTINGS['resim_ozel_alan_adi'];
						$HE_ITEM_IF_NOT_IMAGE = $HE_BOT_SETTINGS['resimsiz_haber'];	
						
						
						foreach($HE_XML->channel->item as $HE_XML_ITEM){

							/*
							<item id="3048559" status="1" lang="tr" editorID="26" type="1" agencyID="13" agencyName="AA" >
								<title url="zonguldak-ta-feto-pdy-nin-11-sirketine-kayyum-atandi"><![CDATA[Zonguldak'ta FETÖ/PDY'nin 11 şirketine kayyum atandı]]></title>
								<description><![CDATA[Zonguldak merkez, Ereğli ve Çaycuma ilçelerinde Fetullahçı Terör Örgütü/Paralel Devlet Yapılanmasına (FETÖ/PDY) yönelik soruşturma kapsamında örgüte finansal destek sağladığı iddiasıyla 11 şirkete kayyum atandı.]]></description>
								<content:encoded><![CDATA[<p><span>Zonguldak Cumhuriyet Başsavcılığınca yürütülen soruşturma kapsamında Sulh Ceza Hakimliğince, Fatih Koleji, Fem-Anafen Dershanesi, Ereğli Fem-Anafen Dershanesi, Ereğli Fatih Koleji, Çaycuma Anafen Dershanesi ile aralarında iki otomotiv firması bayisinin de yer aldığı 6 şirket olmak üzere 11 şirkete</span> kayyum<span> atanmasına karar verildi.</span><br></p><p>Şirketlere bağlı eğitim kurumu ve iş yerlerinde önlem alan polis ekipleri, ilgili yöneticilere kararı tebliğ ederek binalara girmelerine izin vermedi. </p><p>FETÖ/PDY'ye finansman desteği sağlandığı iddiasıyla atanan 6 kayyumun, Emniyet Müdürlüğü Kaçakçılık ve Organize Suçlarla Mücadele Şubesi ekipleriyle şirketlerin bulunduğu binalara gelerek bugün çalışmalara başlayacağı öğrenildi.</p>]]></content:encoded>
								<tags><![CDATA[Zonguldak,FETÖ,Kayyum,Atanma]]></tags>	
								<image>http://aa.com.tr/uploads/Contents/2016/04/09/thumbs_b_c_1da5ba841df708ddd3f4e0054737016e.jpg</image>
								<pubDate update="2016-04-09 10:47:06">2016-04-09 10:40:35</pubDate>
								<guid>http://www.aa.com.tr/tr/turkiye/zonguldakta-feto-pdynin-11-sirketine-kayyum-atandi/551889</guid>
								<category id="36">Gündem</category>
								<files>
									<file type="image" url="htpp://www.file.com/file.jpg">Dosya Adı</file>
									<file type="video" url="htpp://www.file.com/file.jpg">Dosya Adı</file>
									<file type="swf" url="htpp://www.file.com/file.jpg">Dosya Adı</file>
									<file type="other" url="htpp://www.file.com/file.jpg">Dosya Adı</file>
								</files>
								<loadTime>109,375 ms</loadTime>
							</item>
							*/
							
							$HE_ITEM_ADD		= "0";
							$HE_ITEM_ID 		= (int)$HE_XML_ITEM['id'];
							$HE_ITEM_CAT_ID 	= (int)$HE_XML_ITEM->category['id'];
							$HE_ITEM_CAT_NAME 	= (string)$HE_XML_ITEM->category;
							$HE_CAT_ID 			= @$HE_CATS_ARR[$HE_ITEM_CAT_ID];							
							$HE_ITEM_PREVIEW_IMG= (string)$HE_XML_ITEM->image;
							$HE_ITEM_GUID 		= (string)$HE_XML_ITEM->guid;
							$HE_ITEM_TITLE 		= (string)$HE_XML_ITEM->title;
							$HE_ITEM_DESC 		= (string)$HE_XML_ITEM->description;
							$HE_ITEM_CONTENT 	= (string)$HE_XML_ITEM->content;
							$HE_ITEM_TAGS 		= (string)$HE_XML_ITEM->tags;
							$HE_ITEM_PUB_DATE 	= (string)$HE_XML_ITEM->pubDate;							
							$HE_ITEM_AGANCY_NAME= (string)$HE_XML_ITEM['agencyName'];
							$HE_ITEM_FILES 		= $HE_XML_ITEM->files->file;
							
							if ($HE_ITEM_PREVIEW_IMG!=""){
								$HE_IS_IMAGE_URL = he_resim_kontrol($HE_ITEM_PREVIEW_IMG);
							}else{
								$HE_IS_IMAGE_URL=false;
							}
							
							if ($HE_IS_IMAGE_URL==false && $HE_ITEM_IF_NOT_IMAGE!="1"){$HE_ITEM_ADD="1";}
							if ($HE_BOT_POST_DATE!="1"){$HE_ITEM_PUB_DATE = date('Y-m-d H:i',time()) ;}
							if ($HE_BOT_ICERIK_ONU!=""){$HE_ITEM_CONTENT = $HE_BOT_ICERIK_ONU.'<br>'.$HE_ITEM_CONTENT;}
							if ($HE_BOT_ICERIK_SONU!=""){$HE_ITEM_CONTENT = $HE_ITEM_CONTENT.'<br>'.$HE_BOT_ICERIK_SONU;}
							if ($HE_BOT_CONTENT_AFTER_AGENCY_NAME=="1"){$HE_ITEM_CONTENT = $HE_ITEM_CONTENT."<p>Kaynak: ".$HE_ITEM_AGANCY_NAME."</p>";}

							
							$HE_IS_TITLE = get_page_by_title($HE_ITEM_TITLE, OBJECT,$HE_BOT_POST_TYPE);
							if ( @$HE_IS_TITLE->ID > 0 ) {
								$HE_POST_ID = $HE_IS_TITLE->ID ; 
								$HE_ITEM_ADD="1";
							}
							 
							//POST ADD
							if ( $HE_ITEM_ADD=="1" && $HE_POST_ID > 0 ) {
								 echo "<b>(*)</b> <a target='_blank' href='".get_admin_url()."post.php?action=edit&post=".$HE_POST_ID."'>".$HE_ITEM_TITLE."</a> --> ";	
								 $HE_POST_URL = HE_SET_URL . "/$HE_SITE_ID/$HE_BOT_HEID/$HE_ITEM_ID/0/3/$HE_POST_ID/?rURL=" . get_permalink($HE_POST_ID) ;
								 //echo  $HE_POST_URL  ;
								 echo he_curl($HE_POST_URL) . "<br>";
							}
							
							if ($HE_ITEM_ADD!="1"){
								
								$HE_POST = array(
									'post_category' => array($HE_CAT_ID),
									'post_title' => $HE_ITEM_TITLE,
									'post_status' => $HE_BOT_POST_STATUS,
									'post_type' => $HE_BOT_POST_TYPE,
									'post_excerpt' => $HE_ITEM_DESC,
									'post_content' => $HE_ITEM_CONTENT,									
									'post_author' => $HE_BOT_POST_AUTHOR,
									'post_date' => $HE_ITEM_PUB_DATE,
								);
								
								$HE_POST_ID = wp_insert_post($HE_POST);
								
								if ($HE_POST_ID > 0 ) {
									wp_set_post_tags( $HE_POST_ID, $HE_ITEM_TAGS, true );
									$HE_ITEM_THUMB_ID = he_addImages($HE_POST_ID,$HE_ITEM_PREVIEW_IMG,$HE_POST_ID,uniqid());
									add_post_meta($HE_POST_ID, '_thumbnail_id', $HE_ITEM_THUMB_ID, true);									
									if ( $HE_BOT_RESIM_OZEL_ALAN!="" ){	
										add_post_meta($HE_POST_ID, $HE_BOT_RESIM_OZEL_ALAN ,get_the_post_thumbnail( $HE_POST_ID, 'full') ); 
									}
									
									// Diğer Dosyalar Ekleniyor
-									$HE_FILES = False ; 
									foreach( $HE_ITEM_FILES as $HE_ITEM_FILE ){				
										$HE_ITEM_FILE_url = $HE_ITEM_FILE["url"];
										$HE_ITEM_FILE_type = $HE_ITEM_FILE->getAttribute("type");
										$HE_ITEM_FILE_desc = urldecode($HE_ITEM_FILE->nodeValue);
										$HE_attacmentID = he_insert_attachment($HE_ITEM_FILE_url,$HE_POST_ID,$HE_ITEM_FILE_desc,$HE_ITEM_FILE_type);
										if ($HE_attacmentID>0){
											$HE_POST['ID'] = $HE_POST_ID ; 
											$HE_POST['post_content'] = $HE_ITEM_CONTENT . "[gallery]" ;
											wp_update_post( $HE_POST ); 
										}
										
									}								
									
 									add_post_meta( $HE_POST_ID, 'HE_INFO','{"HE_ITEM_ID":'.$HE_ITEM_ID.', "HE_ITEM_CAT_ID":'.$HE_ITEM_CAT_ID.', "HE_ITEM_CAT_NAME":"'.$HE_ITEM_CAT_NAME.'", "HE_ITEM_PUB_DATE":"'.$HE_ITEM_PUB_DATE.'", "HE_ITEM_GUID":"'.$HE_ITEM_GUID.'"}');
 									//add_post_meta( $HE_POST_ID,'HE_INFO',json_encode($HE_XML_ITEM));
									$HE_BOT_SETTINGS['son_icerik'] = date("Y-m-d H:i", time() ) ;
									$HE_BOT_SETTINGS['son_icerik_zamani'] = time() ;
									$HE_BOT_SETTINGS['son_icerik_str'] = "<a target='_blank' href='".get_admin_url()."post.php?action=edit&post=".$HE_POST_ID."'>".$HE_ITEM_TITLE."</a>" ;
									update_option( $HE_BOT_ID.'SETTINGS', $HE_BOT_SETTINGS );

   									echo "<b>(+)</b> <a target='_blank' href='".get_admin_url()."post.php?action=edit&post=".$HE_POST_ID."'>".$HE_ITEM_TITLE."</a> --> ";
									$HE_POST_URL = HE_SET_URL . "/$HE_SITE_ID/$HE_BOT_HEID/$HE_ITEM_ID/0/1/$HE_POST_ID/?rURL=" . get_permalink($HE_POST_ID) ;
									 //echo  $HE_POST_URL  ;
									echo he_curl($HE_POST_URL) . "<br>";
								} else {
									echo "<b>(?)</b> " . $HE_ITEM_TITLE. " -> " . __("HATA OLUŞTU","HaberEditoru") . "<br>";
								}
								ob_flush();flush();

							}
							ob_flush();flush();
						}
					} else {
						
						echo "<b>(!)</b> $HE_XML<br>" ;
						ob_flush();flush();
					}

				$HE_BOT_SETTINGS['son_calisma'] = date("Y-m-d H:i", time() ) ;
				update_option( $HE_BOT_ID.'SETTINGS', $HE_BOT_SETTINGS );
			} else {				
				echo "<b>(!)</b> " . __("BOT Pasif Durumda...","HaberEditoru") ."<br>";
			}
		
	}
	
}
update_option('HE_LAST_CHECK',time() );
?>