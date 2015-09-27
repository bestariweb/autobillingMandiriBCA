<?php
$apiurl = "https://www.apiservices.web.id";
$namadomain = $_SERVER["SERVER_NAME"];
$namadomain = str_replace("www.", "", $namadomain);

$headers = 'From: no-reply@'.$namadomain."\r\n" .
    'X-Mailer: PHP/' . phpversion();

$pesan = "";    
$usermandiri = "";
$passwordmandiri = "";
$accmandiri = "";

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


$saldoakhirmandiri = $query->row['saldo_mandiri'];
$saldoakhirbca = $query->row['saldo_bca'];

$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'autobilling'");
foreach ($query->rows as $hasil) {
	if (($hasil['key'])=="autobilling_password_Mandiri"){
		$passwordmandiri = $hasil['value']; 
		if ($passwordmandiri == ""){ die("Password ibanking Mandiri belum diisi"); }
	}
	if (($hasil['key'])=="autobilling_user_Mandiri"){
		$usermandiri = $hasil['value']; 
		if ($usermandiri == ""){ die("Username ibanking Mandiri belum diisi"); }
	}
	if (($hasil['key'])=="autobilling_account_Mandiri"){
		$accmandiri = $hasil['value']; 
		if ($accmandiri == ""){ die("Nomor Rekening ibanking Mandiri belum diisi"); }
	}
	if (($hasil['key'])=="autobilling_password_BCA"){
		$passwordBCA = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_user_BCA"){
		$userBCA = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_mail_bank"){
		$emailtujuan = $hasil['value']; 
	}
}

if (($usermandiri == "") || ($passwordmandiri == "") || ($accmandiri == "")){
	echo "Data berikut belum lengkap:";
	echo "\n<br>Username : ".$usermandiri;
	echo "\n<br>Password : ".$passwordmandiri;
	echo "\n<br>No Rek : ".$accmandiri;
	die();
} else {
//Ambil Saldo di Ibanking (trxcode=3)
$ch = curl_init();
$params = 'user='.$usermandiri.'&pass='.$passwordmandiri.'&nomoracc='.$accmandiri.'&trxcode=3';
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_URL, $apiurl);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
$hasil = curl_exec( $ch );

$hasil = fixCDATA($hasil);
$xml=simplexml_load_string($hasil) or die("Error: Cannot create object");

$status = $xml->title;

//Eksekusi jika hasil API tidak bermasalah
if ($status != "Error"){

	$saldoTabunganMandiri = $xml->Tabungan->Saldo;

//echo "Saldo Database: ".$saldoakhirmandiri."<br>";
//echo "Saldo Tabungan Mandiri: ".$saldoTabunganMandiri."<br>";

// Initial Mutasi Database jika belum ada record
	if ($saldoakhirmandiri == '0'){
//echo "<br><br>update database....<br>";
//echo "<br>Inisial database untuk pertama kali setup..";
		//Ambil Saldo di Ibanking (trxcode=3)
		$ch = curl_init();

		$params = 'user='.$usermandiri.'&pass='.$passwordmandiri.'&nomoracc='.$accmandiri.'&trxcode=1';
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_URL, $apiurl);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		$hasil = curl_exec( $ch );

		$hasil = fixCDATA($hasil);
		$xml=simplexml_load_string($hasil) or die("Error: Cannot create object");
		$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_mandiri='".$saldoTabunganMandiri."' WHERE saldo_id = '1'");
		$i=0;
		$pesan = "Inisial data Mutasi bank Mandiri\n".
				 "Nomor Rek  : ".$accmandiri."\n".
		         "Saldo Awal : Rp ".number_format($saldoakhirmandiri,2,',','.')."\n".
		         "Saldo Okhir: Rp ".number_format((float)$saldoTabunganMandiri,2,',','.')."\n\n".
		         "Data Transaksi 1 Bulan Yang kami tambahkan ke database:\n".
		         "=======================================================\n";
		foreach ($xml->transaksiMandiri as $key => $transaksiharian) {
			if ($i>0){
				$tgl = substr($transaksiharian[$i]->Tanggal, 0,2);
				$bln = substr($transaksiharian[$i]->Tanggal, 3,2);
				$thn = substr($transaksiharian[$i]->Tanggal, 6,4);
				$tgl = $thn."-".$bln."-".$tgl;
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

				$datalama =  $db->query("SELECT * FROM ". DB_PREFIX . "autobilling_mutasimandiri
					WHERE ket = '".fixKET($transaksiharian[$i]->Keterangan)."' ");
				$statusdata = "Sudah Terdaftar";
				if ($datalama->row['ket'] == ""){
				$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasimandiri SET
					tgl = '".date('Y-m-d',strtotime($tgl))."',
					ket = '".$transaksiharian[$i]->Keterangan."',
					debit = '".$transaksiharian[$i]->Debet."',
					kredit = '".$transaksiharian[$i]->Kredit."',
					berita = '".$berita."', invoice = '".$invoice."'"); 
					$statusdata = "BARU";
				}
				$pesan .= "\n\nNo: ".($i);
				$pesan .= "\nStatus: ".$statusdata;
				$pesan .= "\nTgl Transaksi: ".$tgl;
				$pesan .= "\nKet Transaksi: ".$transaksiharian[$i]->Keterangan;
				$pesan .= "\nDebit:  Rp ".number_format((float)$transaksiharian[$i]->Debet,2,',','.');
				$pesan .= "\nKredit:  Rp ".number_format((float)$transaksiharian[$i]->Kredit,2,',','.');
				$pesan .= "\nBerita: ".$berita;

			}
			$i++;
		}
		
		if ($emailtujuan !="") {
					mail($emailtujuan,"Sincronisasi Autobilling Bank Mandiri",$pesan,$headers);
				} else {
					mail("billing@bestariweb.com","Sincronisasi Autobilling Bank Mandiri",$pesan,$headers);
				}


	}
// End of Initial Mutasi Database jika belum ada record

	else {
//Eksekusi jika saldo tidak nol

//bandingkan saldo jika sama artinya tidak ada transaksi baru
		if (abs($saldoakhirmandiri - $saldoTabunganMandiri) > 1000){

//jika ada perbedaan, ambil transaksi hari ini
			$ch2 = curl_init();

			$params2 = 'user='.$usermandiri.'&pass='.$passwordmandiri.'&nomoracc='.$accmandiri.'&trxcode=2';
			curl_setopt( $ch2, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch2, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $ch2, CURLOPT_FOLLOWLOCATION, 0 );
			curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch2, CURLOPT_URL, $apiurl );
			curl_setopt( $ch2, CURLOPT_POSTFIELDS, $params2 );
			$hasil2 = curl_exec( $ch2 );

			$hasil2 = fixCDATA($hasil2);
			$xml2=simplexml_load_string($hasil2) or die("Error: Cannot create object");

			$status2 = $xml2->title;
			if ($status2 != "Error"){
				//Update saldo Akhir
				if ($saldoTabunganMandiri > 1) {
					$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_mandiri='".$saldoTabunganMandiri."' WHERE saldo_id = '1'");
				}
				$i=0;
				//Update data mutasi
				$pesan = 	"Ada Transaksi di bank mandiri sbb:\n".
				         	"Saldo Awal Database: Rp ".number_format($saldoakhirmandiri,2,',','.')."\n".
		         			"Saldo Akhir:  Rp ".number_format((float)$saldoTabunganMandiri,2,',','.')."\n\n".
		         			"Berikut adalah data Transaksi masuk hari ini :\n";
		        $adatransaksi = false;
				foreach ($xml2->transaksiMandiri as $key => $transaksiharian) {
					if ($i>0){
						$tgl = substr($transaksiharian[$i]->Tanggal, 0,2);
						$bln = substr($transaksiharian[$i]->Tanggal, 3,2);
						$thn = substr($transaksiharian[$i]->Tanggal, 6,4);
						$tgl = $thn."-".$bln."-".$tgl;
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
						$datalama =  $db->query("SELECT * FROM ". DB_PREFIX . "autobilling_mutasimandiri
									WHERE ket = '".fixKET($transaksiharian[$i]->Keterangan)."' ");

						$statusdata = "Sudah Terdaftar";
						if ($datalama->rows['ket'] == ""){
						$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasimandiri SET
							tgl = '".date('Y-m-d',strtotime($tgl))."',
							ket = '".$transaksiharian[$i]->Keterangan."',
							debit = '".$transaksiharian[$i]->Debet."',
							kredit = '".$transaksiharian[$i]->Kredit."',
							berita = '".$berita."', invoice = '".$invoice."'"); 
						$statusdata = "BARU";
						}
						$pesan .= "\nNo: ".($i);
						$pesan .= "\nStatus: ".$statusdata;
						$pesan .= "\nTgl Transaksi: ".$tgl;
						$pesan .= "\nKet Transaksi: ".$transaksiharian[$i]->Keterangan;
						$pesan .= "\nDebit:  Rp ".number_format((float)$transaksiharian[$i]->Debet,2,',','.');
						$pesan .= "\nKredit:  Rp ".number_format((float)$transaksiharian[$i]->Kredit,2,',','.');
						$pesan .= "\nBerita: ".$berita;
						$adatransaksi = true;

					}
					$i++;
				}
				
				if (($emailtujuan !="") && ($adatransaksi)){
					mail($emailtujuan,"Data Transaksi Harian Bank mandiri",$pesan,$headers);
				} else {
					mail("billing@bestariweb.com","Data Transaksi Harian Bank mandiri",$pesan,$headers);
				}


			} else {
				$masalah = $xml2->DetailError->Error;
				$pesansalah  = "Terjadi kesalahan saat eksekusi autobilling\n";
				$pesansalah .= "Jenis Kesalahan: ".$masalah;

				if ($emailtujuan !="") {
					mail($emailtujuan,"Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
				} else {
					mail("billing@bestariweb.com","Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
				}
			}

		} else {
			$pesansalah = "Tidak ada mutasi via bank mandiri\n";
			$pesansalah .= "\nPosisi saldo di Database: Rp ".number_format($saldoakhirmandiri,2,',','.');
			$pesansalah .= "\nPosisi saldo di Tabungan: Rp ".number_format((float)$saldoTabunganMandiri,2,',','.'); 
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
		mail($emailtujuan,"Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
	} else {
		mail("billing@bestariweb.com","Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
	}
}

}
?>