<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
      <a class="btn btn-success" onclick="$('#save').val('stay');$('#form-autobilling').submit();"><i class="fa fa-check"></i> <?php echo $button_save_stay; ?></a>
  <button type="submit" form="form-autobilling" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
  <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
    </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  
  <div class="container-fluid">
  <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check"></i> <?php echo $success; ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    
  <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> Auto Billing Settings</h3>
      </div>
    
   <div class="panel-body">
   <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-autobilling">
   <input type="hidden" name="save" id="save" value="0">
      <ul class="nav nav-tabs main">
      <li class="active"><a href="#tab-user" data-toggle="tab"><i class="fa fa-list"></i>&nbsp;Setting</a></li>
      <li><a href="#tab-bca" data-toggle="tab" style="padding:1px"><img src="<?php echo HTTP_SERVER.'/view/image/bca.png'; ?>" width="80"></a></li>
      <li><a href="#tab-mandiri" data-toggle="tab" style="padding:1px"><img src="<?php echo HTTP_SERVER.'/view/image/mandiri.png'; ?>" width="80"></a></li>
      <li><a href="#tab-lisence" data-toggle="tab" ><i class="fa fa-key"></i>&nbsp;Lisence</a></li>
      </ul>

     
    <div class="tab-content"> 

    <!-- GENERAL /-->      


    <div id="tab-user" class="tab-pane active">
    
    <div class="row">
      
      <div class="col-sm-6 form-group">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Bank BCA</h3>
          </div>
        <div class="panel-body">
        
        <label class="control-label"><?php echo $text_acc_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_account_BCA?>" name="autobilling_account_BCA" id="autobilling_account_BCA" />
        <?php if ($autobilling_account_BCA =="") { echo "<span class='help-block' style='color:red'>Nomor Rekening Harus Di Isi (hanya Angka, tanpa - atau spasi)";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_name_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_name_BCA?>" name="autobilling_name_BCA" />
        <?php if ($autobilling_name_BCA =="") { echo "<span class='help-block' style='color:red'>Username klikBCA Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_user_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_user_BCA?>" name="autobilling_user_BCA" id="autobilling_user_BCA" />
        <?php if ($autobilling_user_BCA =="") { echo "<span class='help-block' style='color:red'>Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_password_bank;?></label>
        <input type="password" class="form-control" value="<?php echo $autobilling_password_BCA?>" name="autobilling_password_BCA" id="autobilling_account_BCA" />
        <?php if ($autobilling_password_BCA =="") { echo "<span class='help-block' style='color:red'>Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

      </div></div></div>


      <div class="col-sm-6 form-group">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Bank Mandiri</h3>
          </div>
        <div class="panel-body">
        <label class="control-label"><?php echo $text_acc_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_account_Mandiri?>" name="autobilling_account_Mandiri"  />
        <?php if ($autobilling_account_Mandiri =="") { echo "<span class='help-block' style='color:red'>Nomor Rekening Harus Di Isi (hanya Angka, tanpa - atau spasi)";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_name_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_name_Mandiri?>" name="autobilling_name_Mandiri"  />
        <?php if ($autobilling_name_Mandiri =="") { echo "<span class='help-block' style='color:red'>Nama Pemilik rekening Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_user_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_user_Mandiri?>" name="autobilling_user_Mandiri" />
        <?php if ($autobilling_user_Mandiri =="") { echo "<span class='help-block' style='color:red'>Username iBanking Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

        <label class="control-label"><?php echo $text_password_bank;?></label>
        <input type="password" class="form-control" value="<?php echo $autobilling_password_Mandiri?>" name="autobilling_password_Mandiri" />
        <?php if ($autobilling_password_Mandiri =="") { echo "<span class='help-block' style='color:red'>Password iBanking Mandiri Harus Di Isi";} else { echo "<span class='help-block' style='color:green'>OK"; }?></span>

      </div></div></div>


      <div class="col-sm-12 form-group">
        <label class="control-label"><?php echo $text_mail_bank;?></label>
        <input type="text" class="form-control" value="<?php echo $autobilling_mail_bank?>" name="autobilling_mail_bank"  />
      </div>
      <div class="col-sm-12 form-group">
        <div class="col-sm-12" style="padding:30px 0 5px 0">Setting Cronjob BCA</div>
        <div class="col-sm-12" style="border:2px solid #777; border-radius:3px;padding:10px;background:#000;color:#777">
          <?php echo "0/15 * * * * php -q ".DIR_APPLICATION."autobillingbca.php"?>
        </div>
        <div class="col-sm-12" style="padding:30px 0 5px 0">Setting Cronjob Bank Mandiri</div>
        <div class="col-sm-12" style="border:2px solid #777; border-radius:3px;padding:10px;background:#000;color:#777">
          <?php echo "0/15 * * * * php -q ".DIR_APPLICATION."autobillingmandiri.php"?>
        </div>
        
      </div>
      <div class="col-sm-12 "><div class="well well-lg"><?php echo "Copyright &copy;2015 <a href='http://www.bestariweb.com'>bestariweb Studio</a> and <a href='http://www.bestariwebhost.com'>Bestariwebhost</a><br>Web Design - Web Developer - Domain Registration - Web Hosting - VPS<br>Telp: 021 - 2235 8031 | HP: 0812 9715 2571"; ?></div></div>

      
    </div> <!-- row ends -->
    
    </div> <!-- #tab-generaluser ends -->
    

    <!-- BCA /-->
    <div id="tab-bca" class="tab-pane">
      <div class="row">
        <div class="col-sm-12">
          <div class="col-sm-6">
            DATA MUTASi HARIAN BCA
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Nomor Rekening BCA:</div>
            <div class="col-sm-6">
              <?php if ($autobilling_account_BCA !=''){
                          echo $autobilling_account_BCA; 
                        } else {
                          echo "<span style='color:red'>Belum Di Isi</span>";
                        }
                 ?></div>
          </div>
        </div>
        <div class="col-sm-12">
          <div class="col-sm-6">
            &nbsp;
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Nama Pemilik Rekening:</div>
            <div class="col-sm-6">
              <?php if ($autobilling_name_BCA !=''){
                          echo $autobilling_name_BCA; 
                        } else {
                          echo "<span style='color:red'>Belum Di Isi</span>";
                        }
                 ?></div>
          </div>
        </div>
        <div class="col-sm-12">
          <div class="col-sm-6">
            &nbsp;
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Saldo:</div>
            <div class="col-sm-6"><?php echo "Rp ".number_format($saldo_bca,2,',','.'); ?></div>
          </div>
        </div>
        <div class="col-sm-12">
          <?php
            if ($listMutasiBCA){ ?>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
              <td>Tgl</td>
              <td>Keterangan</td>
              <td>Debit</td>
              <td>Kredit</td>
              <td>Berita / Pengirim</td>
              <td>Invoice</td>
              <td>Status</td>
            </tr>
            </thead>
            <tbody>
              <?php
              setlocale(LC_MONETARY, 'id_ID');
              foreach ($listMutasiBCA as $key => $tabelmutasibca) { ?>
                <tr>
                  <td><?php echo $tabelmutasibca['tgl']; ?></td>
                  <td><?php echo $tabelmutasibca['ket']; ?></td>
                  <td><?php echo "Rp ".number_format($tabelmutasibca['debit'],2,',','.'); ?></td>
                  <td><?php echo "Rp ".number_format($tabelmutasibca['kredit'],2,',','.'); ?></td>
                  <td><?php echo $tabelmutasibca['berita']; ?></td>
                  <td><?php echo $tabelmutasibca['invoice']; ?></td>
                  <td><?php echo $tabelmutasibca['tglstr']; ?></td>
                </tr>

              <?php } ?>
              
            
            
          </tbody>
        </table>
        <?php } else {echo "Tidak ada mutasi";} ?>
      </div>

      </div>
    </div> <!-- #tab-BCA ends -->
      
      <!-- MANDIRI /-->
      <div id="tab-mandiri" class="tab-pane">
      
      <div class="row">
    
        <div class="col-sm-12">
          <div class="col-sm-6">
            &nbsp;
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Nomor Rekening Mandiri:</div>
            <div class="col-sm-6">
              <?php if ($autobilling_account_Mandiri !=''){
                          echo $autobilling_account_Mandiri; 
                        } else {
                          echo "<span style='color:red'>Belum Di Isi</span>";
                        }
                 ?></div>
          </div>
        </div>
        <div class="col-sm-12">
          <div class="col-sm-6">
            &nbsp;
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Nama Pemilik Rekening:</div>
            <div class="col-sm-6">
              <?php if ($autobilling_name_Mandiri !=''){
                          echo $autobilling_name_Mandiri; 
                        } else {
                          echo "<span style='color:red'>Belum Di Isi</span>";
                        }
                 ?></div>
          </div>
        </div>
        <div class="col-sm-12">
          <div class="col-sm-6">
            &nbsp;
          </div>
          <div class="col-sm-6">
            <div class="col-sm-6" style="text-align:right;font-weight:bold">Saldo:</div>
            <div class="col-sm-6"><?php echo "Rp ".number_format($saldo_mandiri,2,',','.'); ?></div>
          </div>
        </div>
        <div class="col-sm-12">
        <?php
            if ($listMutasiMandiri){
        ?>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
              <td>Tanggal</td>
              <td>Keterangan</td>
              <td>Debit</td>
              <td>Kredit</td>
              <td>Berita</td>
              <td>Invoice</td>
            </tr>
            </thead>
            <tbody>
            
              <?php
              setlocale(LC_MONETARY, 'id_ID');
              foreach ($listMutasiMandiri as $key => $tabelmutasi) { ?>
                <tr>
                  <td><?php echo $tabelmutasi['tgl']; ?></td>
                  <td><?php echo $tabelmutasi['ket']; ?></td>
                  <td><?php echo "Rp ".number_format($tabelmutasi['debit'],2,',','.'); ?></td>
                  <td><?php echo "Rp ".number_format($tabelmutasi['kredit'],2,',','.'); ?></td>
                  <td><?php echo $tabelmutasi['berita']; ?></td>
                  <td><?php echo $tabelmutasi['invoice']; ?></td>
                </tr>

              <?php } ?>
            
            
          </tbody>
        </table>
        <?php } else {echo "Tidak ada mutasi";} ?>
        </div>
        <div class="col-sm-12 "><div class="well well-lg"><?php echo "Copyright &copy;2015 <a href='http://www.bestariweb.com'>bestariweb Studio</a> and <a href='http://www.bestariwebhost.com'>Bestariwebhost</a><br>Web Design - Web Developer - Domain Registration - Web Hosting - VPS<br>Telp: 021 - 2235 8031 | HP: 0812 9715 2571"; ?></div></div>
    
      </div>
      </div> <!-- #tab-mandiri ends -->

      <!-- Lisence Info /-->      


    <div id="tab-lisence" class="tab-pane">
    
    <div class="row">
      <div class="col-sm-12">
        <div class="alert alert-info" role="alert">LICENSE TYPE : Unlimited Licensed</div>
      </div>
      <div class="col-sm-6" >
        <div class="panel panel-default">
          <div class="panel-heading"><h3 class="panel-title">License Detail</h3></div>
          <div class="panel-body">
            <div class="col-sm-6">Nama Domain:</div><div class="col-sm-6"><?php echo $_SERVER["SERVER_NAME"]; ?> </div>
        <div class="clear"></div>
        <div class="col-sm-6">Alamat IP:</div><div class="col-sm-6"><?php echo $_SERVER["SERVER_ADDR"]; ?> </div>
        <div class="clear"></div>
          </div>
        </div>
        

      </div>

      <div class="col-sm-6">
        <div class="alert alert-info" role="alert"><b>DISCLAIMER</b><br>Modul ini dibuat tanpa ada hubungan kerjasama dengan pihak perbankan BCA maupun Mandiri. Modul ini bisa saja tidak berfungsi jika pihak perbankan mengadakan perubahan struktur web internet banking mereka.<br><br>Untuk keamanan Data Anda, sebaiknya gunakan koneksi SSL (https) di web Anda.</div>
      </div>
      
      <div class="col-sm-12 "><div class="well well-lg"><?php echo "Copyright &copy;2015 <a href='http://www.bestariweb.com'>bestariweb Studio</a> and <a href='http://www.bestariwebhost.com'>Bestariwebhost</a><br>Web Design - Web Developer - Domain Registration - Web Hosting - VPS<br>Telp: 021 - 2235 8031 | HP: 0812 9715 2571"; ?></div></div>

      
    </div> <!-- row ends -->
    
    </div> <!-- #tab-license ends -->
    

      </div> <!-- Tab content ends -->
      
     </form>
    </div> <!-- content ends -->
  </div>
  </div>
  </div>

<script type="text/javascript"><!--
$('#tabs a').tabs();
//--></script> 


<?php echo $footer; ?>