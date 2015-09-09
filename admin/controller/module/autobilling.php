<?php
class ControllerModuleAutobilling extends Controller {
	private $error = array();

	
	public function install() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "autobilling_saldo");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "autobilling_saldo` (
		  `saldo_id` int(2) NOT NULL AUTO_INCREMENT,
		  `tgl` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `saldo_bca` float(15,2) NOT NULL DEFAULT '0',
		  `saldo_mandiri` float(15,2) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`saldo_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");

	
		$this->db->query("INSERT INTO " . DB_PREFIX . "autobilling_saldo SET 
			tgl = '" . date('Y-m-d H:i:s'). "', 
			saldo_bca = '0',
			saldo_mandiri = '0'");

			
 		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "autobilling_mutasibca");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "autobilling_mutasibca` (
		  `mutasi_id` int(11) NOT NULL AUTO_INCREMENT,
		  `tgl` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `tglstr` varchar(20) NULL DEFAULT '2015/09/01',
		  `ket` varchar(255) COLLATE utf8_bin DEFAULT NULL,
		  `debit` float(15,2) NOT NULL DEFAULT '0',
		  `kredit` float(15,2) NOT NULL DEFAULT '0',
		  `berita` varchar(100) COLLATE utf8_bin DEFAULT NULL,
		  `invoice` varchar(20) COLLATE utf8_bin DEFAULT NULL,
		  PRIMARY KEY (`mutasi_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "autobilling_mutasimandiri");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "autobilling_mutasimandiri` (
		  `mutasi_id` int(11) NOT NULL AUTO_INCREMENT,
		  `tgl` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `tglstr` varchar(20) NULL DEFAULT '2015/09/01',
		  `ket` varchar(255) COLLATE utf8_bin DEFAULT NULL,
		  `debit` float(10,2) NOT NULL DEFAULT '0',
		  `kredit` float(10,2) NOT NULL DEFAULT '0',
		  `berita` varchar(100) COLLATE utf8_bin DEFAULT NULL,
		  `invoice` varchar(20) COLLATE utf8_bin DEFAULT NULL,
		  PRIMARY KEY (`mutasi_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");


	}

	public function index() {
		$this->load->language('module/autobilling');
		$this->load->model('localisation/language');
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('autobilling', $this->request->post);	
			
			if (isset($this->request->post['save']) && $this->request->post['save'] == 'stay') {
				$this->session->data['success'] = $this->language->get('text_success');
				$this->response->redirect($this->url->link('module/autobilling', 'token=' . $this->session->data['token'], 'SSL')); 
			}
				
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}


		$data['heading_title'] = $this->language->get('heading_title');
		$data['button_save_stay'] = $this->language->get('button_save_stay');

		$saldo = $this->getsaldo();
		$data['saldo_bca'] = $saldo['saldo_bca'];
		$data['saldo_mandiri'] = $saldo['saldo_mandiri'];
		$data['text_module']       = $this->language->get('text_module');
		$data['text_success']      = $this->language->get('text_success');
		$data['text_acc_bank']      = $this->language->get('text_acc_bank');
		$data['text_name_bank']     = $this->language->get('text_name_bank');
		$data['text_user_bank']     = $this->language->get('text_user_bank');
		$data['text_password_bank'] = $this->language->get('text_password_bank');
		$data['text_tab_user']      = $this->language->get('text_tab_user');
		$data['text_tab_bca']       = $this->language->get('text_tab_bca');
		$data['text_tab_mandiri']   = $this->language->get('text_tab_mandiri');
		$data['text_mail_bank'] = $this->language->get('text_mail_bank');

		$data['column_saldo']  = $this->language->get('column_saldo');
		$data['column_tgl_mutasi']  = $this->language->get('column_tgl_mutasi');
		$data['column_desc_mutasi']  = $this->language->get('column_desc_mutasi');
		$data['column_kredit_mutasi']  = $this->language->get('column_kredit_mutasi');
		$data['column_debit_mutasi']  = $this->language->get('column_debit_mutasi');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
			
		} 
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		
		$data['token'] = $this->session->data['token'];

		// USER settings

		if (isset($this->request->post['autobilling_mail_bank'])) {
			$data['autobilling_mail_bank'] = $this->fixREKENING($this->request->post['autobilling_mail_bank']);
		} else {
			$data['autobilling_mail_bank'] = $this->fixREKENING($this->config->get('autobilling_mail_bank'));

		}

		// data Nomor Rekening Mandiri
		if (isset($this->request->post['autobilling_account_Mandiri'])) {
			$data['autobilling_account_Mandiri'] = $this->fixREKENING($this->request->post['autobilling_account_Mandiri']);
		} else {
			$data['autobilling_account_Mandiri'] = $this->fixREKENING($this->config->get('autobilling_account_Mandiri'));

		} 
			


		// data Nomor Rekening BCA      autobilling_account_BCA
		if (isset($this->request->post['autobilling_account_BCA'])) {
			$data['autobilling_account_BCA'] = $this->fixREKENING($this->request->post['autobilling_account_BCA']);
		} else {
			$data['autobilling_account_BCA'] = $this->fixREKENING($this->config->get('autobilling_account_BCA'));
		}


		// data Nama pemilik Rekening Mandiri
		if (isset($this->request->post['autobilling_name_Mandiri'])) {
			$data['autobilling_name_Mandiri'] = $this->request->post['autobilling_name_Mandiri'];
		} else {
			$data['autobilling_name_Mandiri'] = $this->config->get('autobilling_name_Mandiri');
		}

		// data Nama pemilik Rekening BCA
		if (isset($this->request->post['autobilling_name_BCA'])) {
			$data['autobilling_name_BCA'] = $this->request->post['autobilling_name_BCA'];
		} else {
			$data['autobilling_name_BCA'] = $this->config->get('autobilling_name_BCA');
		}


		// data user Mandiri
		if (isset($this->request->post['autobilling_user_Mandiri'])) {
			$data['autobilling_user_Mandiri'] = $this->request->post['autobilling_user_Mandiri'];
		} else {
			$data['autobilling_user_Mandiri'] = $this->config->get('autobilling_user_Mandiri');
		}

		// data user BCA
		if (isset($this->request->post['autobilling_user_BCA'])) {
			$data['autobilling_user_BCA'] = $this->request->post['autobilling_user_BCA'];
		} else {
			$data['autobilling_user_BCA'] = $this->config->get('autobilling_user_BCA');
		}

		// data Passwd Mandiri
		if (isset($this->request->post['autobilling_password_Mandiri'])) {
			$data['autobilling_password_Mandiri'] = $this->request->post['autobilling_password_Mandiri'];
		} else {
			$data['autobilling_password_Mandiri'] = $this->config->get('autobilling_password_Mandiri');
		}

		// data Passwd BCA
		if (isset($this->request->post['autobilling_password_BCA'])) {
			$data['autobilling_password_BCA'] = $this->request->post['autobilling_password_BCA'];
		} else {
			$data['autobilling_password_BCA'] = $this->config->get('autobilling_password_BCA');
		}
		$data['action'] = $this->url->link('module/autobilling', 'token=' . $this->session->data['token'], 'SSL');
		
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['listMutasiMandiri'] = array();
		
		$results = $this->getListMandiri();
                
		foreach ($results as $result) {
			$data['listMutasiMandiri'][] = array(
				'tgl' => $result['tgl'],
					'ket' => $result['ket'],
					'debit' => $result['debit'],
					'kredit' => $result['kredit'],
					'berita' => $result['berita'],
					'invoice' => $result['invoice']
			);
		}

		$data['listMutasiBCA'] = array();
		
		$results2 = $this->getListBCA();
                
		foreach ($results2 as $result) {
			$data['listMutasiBCA'][] = array(
				'tgl' => $result['tgl'],
				'tglstr' => $result['tglstr'],
					'ket' => $result['ket'],
					'debit' => $result['debit'],
					'kredit' => $result['kredit'],
					'berita' => $result['berita'],
					'invoice' => $result['invoice']
			);
		}
		$this->ambildata();
		$this->response->setOutput($this->load->view('module/autobilling.tpl', $data));
	}

	public function ambildata(){
		$accmandiri = $this->config->get('autobilling_account_mandiri');
		$usermandiri = $this->config->get('autobilling_user_mandiri');
		$passmandiri = $this->config->get('autobilling_password_mandiri');
		$saldo = $this->getsaldo();
		$saldoakhirbca = $saldo['saldo_bca'];
		$saldoakhirmandiri = $saldo['saldo_mandiri'];
		
			$data['footermessage'] = "Last Updating data...";
/*			$ch = curl_init();

			$params = 'user='.$usermandiri.'&pass='.$passmandiri.'&nomoracc='.$accmandiri.'&trxcode=1';
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_URL, HTTP_SERVER.'autobillingmandiri.php' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
			$hasil = curl_exec( $ch );

*/			
			
		} 

	public function getsaldo() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "autobilling_saldo WHERE saldo_id = '1'");
		return $query->row;
	}

	public function fixREKENING($string) {
		$find[]     = '-';
		$replace[] = '';

		$find[]     = ' ';
		$replace[] = '';

		return $string = str_replace($find, $replace, $string);
	}


	public function getListMandiri() {
	/*	$mandiri_data = "test";
		$mandiri_data = $this->cache->get('autobilling_mutasimandiri');
	    

		if (!$mandiri_data) {*/
			$mandiri_data = array();
		
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "autobilling_mutasimandiri ORDER BY tgl DESC");
		
			foreach ($query->rows as $result) {
				$mandiri_data[] = array(
					'tgl' => $result['tgl'],
					'ket' => $result['ket'],
					'debit' => $result['debit'],
					'kredit' => $result['kredit'],
					'berita' => $result['berita'],
					'invoice' => $result['invoice']
					
				);
			
				//$mandiri_data = array_merge($mandiri_data, $this->getBlogCategories($result['mandiri_id']));
			}	
	
			//$this->cache->set('autobilling_mutasimandiri');
		// }
		
		return $mandiri_data;
	}

	public function getListBCA() {
	/*	$mandiri_data = "test";
		$mandiri_data = $this->cache->get('autobilling_mutasimandiri');
	    

		if (!$mandiri_data) {*/
			$bca_data = array();
		
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "autobilling_mutasibca ORDER BY tgl DESC");
		
			foreach ($query->rows as $result) {
				$bca_data[] = array(
					'tgl' => $result['tgl'],
					'tglstr' => $result['tglstr'],
					'ket' => $result['ket'],
					'debit' => $result['debit'],
					'kredit' => $result['kredit'],
					'berita' => $result['berita'],
					'invoice' => $result['invoice']
					
				);
			
				//$mandiri_data = array_merge($mandiri_data, $this->getBlogCategories($result['mandiri_id']));
			}	
	
			//$this->cache->set('autobilling_mutasimandiri');
		// }
		
		return $bca_data;
	}



	private function validate() {
		if (!$this->user->hasPermission('modify', 'module/autobilling')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
						
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}