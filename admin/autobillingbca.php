<?php
$apiurl = "https://www.apiservices.web.id";
$namadomain = $_SERVER["SERVER_NAME"];
$namadomain = str_replace("www.", "", $namadomain);

$headers = 'From: no-reply@'.$namadomain."\r\n" .
    'X-Mailer: PHP/' . phpversion();
$pesan = "";    
$userBCA = "";
$passwordBCA = "";
$accbca = "";

function fixCDATA($string) {
	$find[]     = '&lt;![CDATA[';
	$replace[] = '<![CDATA[';

	$find[]     = ']]&gt;';
	$replace[] = ']]>';

	return $string = str_replace($find, $replace, $string);
}

function fixKET($string) {
	$find[]     = '&lt;';
	$replace[] = '<';

	$find[]     = '&gt;';
	$replace[] = '>';

	return $string = str_replace($find, $replace, $string);
}

function fixAngka($string) {
	$find[]     = ',';
	$replace[] = '';

	return $string = str_replace($find, $replace, $string);
}

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	echo "incorrect configuration file ..!";
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();


$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

//ambil saldo terakhir di database
$query = $db->query("SELECT * FROM " . DB_PREFIX . "autobilling_saldo WHERE saldo_id = '1'");	


$saldoakhirbca = $query->row['saldo_bca'];

$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'autobilling'");
foreach ($query->rows as $hasil) {
	if (($hasil['key'])=="autobilling_password_BCA"){
		$passwordBCA = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_user_BCA"){
		$userBCA = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_account_BCA"){
		$accbca = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_mail_bank"){
		$emailtujuan = $hasil['value']; 
	}
}

if (($userBCA == "") || ($passwordBCA == "") || ($accbca == "")){
	echo "Data berikut belum lengkap:";
	echo "\n<br>Username : ".$userBCA;
	echo "\n<br>Password : ".$passwordBCA;
	echo "\n<br>No Rek : ".$accbca;
	die();
} else {
//Ambil Saldo di Ibanking (trxcode=3)

if ($saldoakhirbca != 0){
	$params = 'user='.$userBCA.'&pass='.$passwordBCA.'&nomoracc='.$accbca.'&trxcode=2'.'&bank=BCA';
} else {
	$params = 'user='.$userBCA.'&pass='.$passwordBCA.'&nomoracc='.$accbca.'&trxcode=1'.'&bank=BCA';
}
$ch = curl_init();
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_URL, $apiurl);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
$hasil = curl_exec( $ch );

$hasil = fixCDATA($hasil);
// echo print_r($hasil);

$xml=simplexml_load_string($hasil) or die("Error: Cannot create object");

$status = $xml->title;

//Eksekusi jika hasil API tidak bermasalah
if ($status != "Error"){

	$saldoTabunganbca = fixAngka($xml->Saldo);


// Initial Mutasi Database jika belum ada record
	if ($saldoakhirbca == '0'){

		$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_bca='".$saldoTabunganbca."' WHERE saldo_id = '1'");
		$i=0;
		$pesan = "Inisial data Mutasi bank BCA..\n".
		         "Nomor Rek   : ".$accbca."\n".
		         "Saldo Awal  : Rp ".number_format($saldoakhirbca,2,',','.')."\n".
		         "Saldo Akhir : Rp ".number_format((float)$saldoTabunganbca,2,',','.')."\n\n".
		         "Data Transaksi 1 Bulan Yang kami tambahkan ke database:\n".
		         "=======================================================\n";
		 //$pesan .= print($xml->sXML());
		         

		foreach ($xml->TransaksiBCA as $key => $transaksiharian) {
			/*
				$tgl = substr($transaksiharian[$i]->Tanggal, 0,2);
				$bln = substr($transaksiharian[$i]->Tanggal, 3,2);
				$thn = substr($transaksiharian[$i]->Tanggal, 6,4);
				$tgl = $thn."-".$bln."-".$tgl;
			*/	
				$tgl = $transaksiharian[$i]->Tanggal;
				if ($tgl == "PEND"){
					$tgl = date('Y-m-d');
				} else {
					$tglbln = explode("/", $transaksiharian[$i]->Tanggal);
					/*$tgl = substr($transaksiharian[$i]->Tanggal, 0,2);
					$bln = substr($transaksiharian[$i]->Tanggal, 3,2);*/
					$thn = date('Y');
					$tgl = date('Y-m-d',strtotime($thn."-".$tglbln[1]."-".$tglbln[0]));
				}
				$ket = strtoupper($transaksiharian[$i]->Keterangan);
				$berita = "";
				$invoice = "";

				if (stripos($ket, "INVOICE") > 0){				
					$awalberita = stripos($ket, "INVOICE");
					$endberita = strpos($ket, "<BR", $awalberita);
					$berita = substr($ket, $awalberita, $endberita-$awalberita);
					$invoice = substr($berita, 8);
				} else {
					$beritaarr = explode("<BR>", $ket);
					$akhirberita = count($beritaarr)-2;
					$berita = $beritaarr[$akhirberita];
					//$berita = substr($ket, $awalberita+5, $endberita-$awalberita-5);
				}
				
				$datalama =  $db->query("SELECT * FROM ". DB_PREFIX . "autobilling_mutasibca
					WHERE ket = '".fixKET($transaksiharian[$i]->Keterangan)."' ");
				$statusdata = "Sudah Terdaftar atau invalid";
				$FixDebet = fixAngka($transaksiharian[$i]->Debet);
				$FixKredit = fixAngka($transaksiharian[$i]->Kredit);
				if (($datalama->row['ket'] == "") && (($FixDebet > 0) || ($FixKredit > 0))){
				$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasibca SET
					tgl = '".$tgl."',
					tglstr = '".$transaksiharian[$i]->Tanggal."',
					ket = '".$transaksiharian[$i]->Keterangan."',
					debit = '".$FixDebet."',
					kredit = '".$FixKredit."',
					berita = '".$berita."', invoice = '".$invoice."'"); 
					$statusdata = "BARU dan berhasil di simpan";

				}
				
				$pesan .= "\n\nNo: ".($i);
				$pesan .= "\nStatus: ".$statusdata;
				$pesan .= "\nTgl Transaksi: ".$tgl;
				$pesan .= "\nKet Transaksi: ".$ket;
				$pesan .= "\nDebit:  Rp ".number_format((float)$FixDebet,2,',','.');
				$pesan .= "\nKredit:  Rp ".number_format((float)$FixKredit,2,',','.');
				$pesan .= "\nBerita: ".$berita;

			
			$i++;
		} 
		
		if ($emailtujuan !="") {
					mail($emailtujuan,"Sincronisasi Data Autobilling BCA",$pesan,$headers);
				} else {
					mail("billing@bestariweb.com","Sincronisasi Data Autobilling BCA",$pesan,$headers);
				}


	}
// End of Initial Mutasi Database jika belum ada record

	else {
//Eksekusi jika saldo tidak nol

//bandingkan saldo jika sama artinya tidak ada transaksi baru
		if (abs($saldoakhirbca - $saldoTabunganbca) > 1000){

//jika ada perbedaan, ambil transaksi hari ini

			if ($status != "Error"){
				//Update saldo Akhir
				if ($saldoTabunganbca > 1) {
					$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_bca='".$saldoTabunganbca."' WHERE saldo_id = '1'");
				}
				$i=0;
				//Update data mutasi
				$pesan = 	"Ada Transaksi di bank bca sbb:\n".
				         	"Posisi Saldi di Database: Rp ".number_format($saldoakhirbca,2,',','.')."\n".
		         			"Posisi Saldo di iBanking bca:  Rp ".number_format((float)$saldoTabunganbca,2,',','.')."\n\n".
		         			"Berikut adalah data Transaksi masuk hari ini :\n";

				foreach ($xml->TransaksiBCA as $key => $transaksiharian) {
					
						/*
						$tgl = substr($transaksiharian[$i]->Tanggal, 0,2);
						$bln = substr($transaksiharian[$i]->Tanggal, 3,2);
						$thn = substr($transaksiharian[$i]->Tanggal, 6,4);
						$tgl = $thn."-".$bln."-".$tgl;
						*/
						$tgl = $transaksiharian[$i]->Tanggal."/2015";
						$ket = strtoupper($transaksiharian[$i]->Keterangan);
						$berita = "";
						$invoice = "";

						if (stripos($ket, "INVOICE") > 0){				
							$awalberita = stripos($ket, "INVOICE");
							$endberita = strpos($ket, "<BR", $awalberita);
							$berita = substr($ket, $awalberita, $endberita-$awalberita);
							$invoice = substr($berita, 8);
						} elseif (stripos($ket, "DARI") > 0) {
							$awalberita = stripos($ket, "DARI");
							$endberita = strpos($ket, "<BR", $awalberita);
							$berita = substr($ket, $awalberita+5, $endberita-$awalberita-5);
						}

						// cek apakah sudah di record
						/* kode disini */
						$FixDebet = fixAngka($transaksiharian[$i]->Debet);
						$FixKredit = fixAngka($transaksiharian[$i]->Kredit);


						$datalama =  $db->query("SELECT * FROM ". DB_PREFIX . "autobilling_mutasibca
									WHERE ket = '".fixKET($transaksiharian[$i]->Keterangan)."' ");

						$statusdata = "Sudah Terdaftar";
						if (($datalama->row['ket'] == "") && (($FixDebet > 0) || ($FixKredit > 0))){
						$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasibca SET
							tgl = '".date('Y-m-d')."',
							ket = '".$transaksiharian[$i]->Keterangan."',
							debit = '".$FixDebet."',
							kredit = '".$FixKredit."',
							berita = '".$berita."', invoice = '".$invoice."'"); 
						$statusdata = "BARU";
						}
						$pesan .= "\nNo: ".($i);
						$pesan .= "\nStatus: ".$statusdata;
						$pesan .= "\nTgl Transaksi: ".$tgl;
						$pesan .= "\nKet Transaksi: ".$transaksiharian[$i]->Keterangan;
						$pesan .= "\nDebit:  Rp ".number_format((float)$FixDebet,2,',','.');
						$pesan .= "\nKredit:  Rp ".number_format((float)$FixKredit,2,',','.');
						$pesan .= "\nBerita: ".$berita;


					
					$i++;
				}
				
				if ($emailtujuan !="") {
					mail($emailtujuan,"Data Transaksi Harian Bank bca",$pesan,$headers);
				} else {
					mail("billing@bestariweb.com","Data Transaksi Harian Bank bca",$pesan,$headers);
				}


			} else {
				$masalah = $xml2->DetailError->Error;
				$pesansalah  = "Terjadi kesalahan saat eksekusi autobilling\n";
				$pesansalah .= "Jenis Kesalahan: ".$masalah;

				if ($emailtujuan !="") {
					mail($emailtujuan,"Data Transaksi Harian Bank bca",$pesansalah,$headers);
				} else {
					mail("billing@bestariweb.com","Data Transaksi Harian Bank bca",$pesansalah,$headers);
				}
			}

		} else {
			$pesansalah = "Tidak ada mutasi via bank bca\n";
			$pesansalah .= "\nPosisi saldo di Database: Rp ".number_format($saldoakhirbca,2,',','.');
			$pesansalah .= "\nPosisi saldo di Tabungan: Rp ".number_format((float)$saldoTabunganbca,2,',','.'); 
			/*
			if ($emailtujuan !="") {
				mail($emailtujuan,"Tidak ada Transaksi baru",$pesansalah,$headers);
			} else {
				mail("billing@bestariweb.com","Tidak ada Transaksi baru",$pesansalah,$headers);
			}*/
		}
	}

} else 
//Jika ada masalah, lisensi dll, tanpilkan disini
{
	$masalah = $xml->DetailError->Error;
	$pesansalah  = "Terjadi kesalahan saat eksekusi autobilling\n";
	$pesansalah .= "Jenis Kesalahan: ".$masalah;
	if ($emailtujuan !="") {
		mail($emailtujuan,"Data Transaksi Harian Bank bca",$pesansalah,$headers);
	} else {
		mail("billing@bestariweb.com","Data Transaksi Harian Bank bca",$pesansalah,$headers);
	}
}

}
?>