<?php

require_once 'src/CekBNI.php';
$config = [
        'credential' => [
            'username' => 'username-internet-banking',
            'password' => 'password-internet-banking'
        ],
        'nomor_rekening' => '040xxxxxx', //No. Rekening
        'range' => [
            'tgl_akhir' => date('d-M-Y',strtotime('2018-07-31')),
            'tgl_awal' => date('d-M-Y',strtotime('2018-07-01'))
        ],
];
		
$bni = new CekBNI($config);
var_dump($bni->toArray());
