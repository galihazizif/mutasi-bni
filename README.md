##Script Cek mutasi BNI


Cara pakai. 

		require_once 'build/mutasi_bni.phar';
		$config = [
		        'credential' => [
		            'username' => 'jenengmu',
		            'password' => 'passwordmu'
		        ],
		        'nomor_rekening' => '0400xxxxxx', //No. Rekening
		        'range' => [
		            'tgl_akhir' => date('d-M-Y',strtotime('2018-07-31')),
		            'tgl_awal' => date('d-M-Y',strtotime('2018-07-01'))
		        ],
		];
		
		$bni = new CekBNI($config);
		var_dump($bni->toArray());

lihat example.php