<?php
$apiurl = "http://www.apiservices.web.id";
$headers = 'From: no-reply@toserba123.com' . "\r\n" .
    'Reply-To: support@bestariweb.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

function fixCDATA($string) {
	$find[]     = '&lt;![CDATA[';
	$replace[] = '<![CDATA[';

	$find[]     = ']]&gt;';
	$replace[] = ']]>';

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
	}
	if (($hasil['key'])=="autobilling_user_Mandiri"){
		$usermandiri = $hasil['value']; 
	}
	if (($hasil['key'])=="autobilling_account_Mandiri"){
		$accmandiri = $hasil['value']; 
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

$status = $xml->title;

//Eksekusi jika hasil API tidak bermasalah
if ($status != "Error"){

	$saldoTabunganMandiri = $xml->DataSaldo->Saldo;

//echo "Saldo Database: ".$saldoakhirmandiri."<br>";
//echo "Saldo Tabungan Mandiri: ".$saldoTabunganMandiri."<br>";

// Initial Mutasi Database jika belum ada record
	if ($saldoakhirmandiri == '0'){
//echo "<br><br>update database....<br>";
//echo "<br>Inisial database untuk pertama kali setup..";
		$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_mandiri='".$saldoTabunganMandiri."' WHERE saldo_id = '1'");
		$i=0;
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


				$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasimandiri SET
					tgl = '".date('Y-m-d',strtotime($tgl))."',
					ket = '".$transaksiharian[$i]->Keterangan."',
					debit = '".$transaksiharian[$i]->Debet."',
					kredit = '".$transaksiharian[$i]->Kredit."',
					berita = '".$berita."', invoice = '".$invoice."'"); 
				$pesan .= "\n\nNo: ".($i+1);
				$pesan .= "\nTgl Transaksi: ".$tgl;
				$pesan .= "\nKet Transaksi: ".$transaksiharian[$i]->Keterangan;
				$pesan .= "\nDebit: ".$transaksiharian[$i]->Debet;
				$pesan .= "\nKredit: ".$transaksiharian[$i]->Kredit;
				$pesan .= "\nBerita: ".$berita;

			}
			$i++;
		}
		$pesan = "Inisial data Mutasi dan saldo di OLShop..\nNomor Rek ".$accmandiri."\n".$pesan;
		if ($emailtujuan !="") {
					mail($emailtujuan,"Data install Autobilling",$pesan,$headers);
				} else {
					mail("billing@bestariweb.com","Data install Autobilling",$pesan,$headers);
				}


	}
// End of Initial Mutasi Database jika belum ada record

	else {
//Eksekusi jika saldo tidak nol

//bandingkan saldo jika sama artinya tidak ada transaksi baru
		if (abs($saldoakhirmandiri - $saldoTabunganMandiri) > 10){

//jika ada perbedaan, ambil transaksi hari ini
			$ch2 = curl_init();

			$params2 = 'user='.$usermandiri.'&pass='.$passwordmandiri.'&nomoracc='.$accmandiri.'&trxcode=2';
			curl_setopt( $ch2, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch2, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $ch2, CURLOPT_FOLLOWLOCATION, 0 );
			curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch2, CURLOPT_URL, $apiurl );
			curl_setopt( $ch2, CURLOPT_POSTFIELDS, $params2 );
			$hasil2 = curl_exec( $ch );

			$hasil2 = fixCDATA($hasil2);
			$xml2=simplexml_load_string($hasil2) or die("Error: Cannot create object");

			$status2 = $xml2->title;
			if ($status2 != "Error"){
//Update saldo Akhir
				$query = $db->query("UPDATE " . DB_PREFIX . "autobilling_saldo SET saldo_mandiri='".$saldoTabunganMandiri."' WHERE saldo_id = '1'");
				$i=0;
//Update data mutasi
				$pesan = "Ada Transaksi di bank mandiri sbb:\n";
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


						$query = $db->query("INSERT INTO ". DB_PREFIX . "autobilling_mutasimandiri SET
							tgl = '".date('Y-m-d',strtotime($tgl))."',
							ket = '".$transaksiharian[$i]->Keterangan."',
							debit = '".$transaksiharian[$i]->Debet."',
							kredit = '".$transaksiharian[$i]->Kredit."',
							berita = '".$berita."', invoice = '".$invoice."'"); 
						$pesan .= "\nNo: ".($i);
						$pesan .= "\nTgl Transaksi: ".$tgl;
						$pesan .= "\nKet Transaksi: ".$transaksiharian[$i]->Keterangan;
						$pesan .= "\nDebit: ".$transaksiharian[$i]->Debet;
						$pesan .= "\nKredit: ".$transaksiharian[$i]->Kredit;
						$pesan .= "\nBerita: ".$berita;


					}
					$i++;
				}
				
				if ($emailtujuan !="") {
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
			$pesansalah = "Tidak ada mutasi";

			if ($emailtujuan !="") {
				mail($emailtujuan,"Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
			} else {
				mail("billing@bestariweb.com","Data Transaksi Harian Bank mandiri",$pesansalah,$headers);
			}
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


?>