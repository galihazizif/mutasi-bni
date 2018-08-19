<?php  

/*********************************************************

    Script ini digunakan untuk mengecek mutasi BNI
    Author: Galih Azizi Firmansyah
    Email: galih@rempoah.com
    Phone: 085640284554
    Silahkan dimanfaatkan untuk kemaslahatan bersama.

***********************************************************/

require_once(__DIR__.DIRECTORY_SEPARATOR.'simple_html_dom.php');

class CekBNI{

	const ua = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.75 Safari/537.36";
	const urlPrepareLogin = 'https://ibank.bni.co.id/MBAWeb/FMB;jsessionid=0000bAQ5H6TjltF-OqrAf-Hv-9P:1a1li5jho?page=Thin_SignOnRetRq.xml&MBLocale=bh';

	private $ch;
	private $config;
	private $result;
    private $cookie = __DIR__.DIRECTORY_SEPARATOR.'bni-cookie.txt';
    private $dom;
    private $parameters;
    private $actionUrl;
    private $parsed;

	public function __construct($config){
        try{
    		$cookie = $this->cookie;
    		$this->dom = new simple_html_dom();
    		$this->config = $config;
    		$ch = curl_init();
        	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        	curl_setopt($ch, CURLOPT_USERAGENT, self::ua);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	$this->ch = $ch;

        	$this->prepareLogin();
        	$this->login();
        	$this->getMutasi();
        	$this->logout();
        }catch(\Exception $e){
            echo $e->getMessage().PHP_EOL;
        }

	}

    function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

	private function prepareLogin(){
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL,self::urlPrepareLogin);
		$this->result = curl_exec($ch);
	}

	private function login(){
		$dom = $this->dom;
		$dom->load($this->result);
		$form = $dom->find('form',0);
    	$config = $this->config;

    	$postdata = "Num_Field_Err=%22Please+enter+digits+only%21%22&Mand_Field_Err=%22Mandatory+field+is+empty%21%22&CorpId=".urlencode($config['credential']['username'])."&PassWord=".urlencode($config['credential']['password'])."&__AUTHENTICATE__=Login&CancelPage=HomePage.xml&USER_TYPE=1&MBLocale=bh&language=bh&AUTHENTICATION_REQUEST=True&__JS_ENCRYPT_KEY__=&JavaScriptEnabled=N&deviceID=&machineFingerPrint=&deviceType=&browserType=&uniqueURLStatus=disabled&imc_service_page=SignOnRetRq&Alignment=LEFT&page=SignOnRetRq&locale=en&PageName=Thin_SignOnRetRq.xml&serviceType=Dynamic";

    	$ch = $this->ch;
    	curl_setopt($ch, CURLOPT_URL, $form->action);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	$result = curl_exec($ch);
        $this->result = $result;

	}

	private function getMutasi(){
		$dom = $this->dom;
		$config = $this->config;
		$ch = $this->ch;
		$dom->load($this->result);
		$form = $dom->find('form',0);

        $anchor = $dom->find("#MBMenuList", 0);
        parse_str($anchor->href,$parameters);
        $this->parameters = $parameters;
    	$postdata = "Num_Field_Err=%22Please+enter+digits+only%21%22&Mand_Field_Err=%22Mandatory+field+is+empty%21%22&acc1=OPR%7C0000000".$config['nomor_rekening']."%7CTab+BNI+iB+Hasanah+Wadiah+IDR&TxnPeriod=LastMonth&Search_Option=Date&txnSrcFromDate=".$config['range']['tgl_awal']."&txnSrcToDate=".$config['range']['tgl_akhir']."&FullStmtInqRq=Lanjut&MAIN_ACCOUNT_TYPE=OPR&mbparam=".urlencode($parameters['mbparam'])."&uniqueURLStatus=disabled&imc_service_page=AccountIDSelectRq&Alignment=LEFT&page=AccountIDSelectRq&locale=bh&PageName=FullStmtInqRq&serviceType=Dynamic";
        $this->actionUrl = $form->action;
    	curl_setopt($ch, CURLOPT_URL, $form->action);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_REFERER, $this->actionUrl);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	$result = curl_exec($ch);

        $dom->clear();
        $dom->load($result);
        $finalResult = $result;

        $nextData = $dom->find("#NextData", 0);
        
        $ch = $this->ch;
        while($nextData != null){
        $nextUrl = $this->get_string_between($nextData->getAttribute('href'),"'","'");
            curl_setopt($ch, CURLOPT_URL, $nextUrl);
            curl_setopt($ch, CURLOPT_REFERER, $this->actionUrl);
            $data = curl_exec($ch);
            // echo $dom->getElementByTagName("body")->innertext();
            $finalResult = $finalResult.$data;
            $dom->clear();
            $dom->load($data);
            $nextData = $dom->getElementById('NextData');
        }

        $this->result = $finalResult;
        // file_put_contents("bni.hasil_mutasi.html",$finalResult);

	}

	private function logout(){
		
        $mbparam = $this->parameters['mbparam'];
        $postdata = "Num_Field_Err=%22Please+enter+digits+only%21%22&Mand_Field_Err=%22Mandatory+field+is+empty%21%22&__LOGOUT__=Keluar&mbparam=".urlencode($mbparam)."&uniqueURLStatus=disabled&imc_service_page=SignOffUrlRq&Alignment=LEFT&page=SignOffUrlRq&locale=bh&PageName=LoginRs&serviceType=Dynamic";

        $ch = $this->ch;
        curl_setopt($ch, CURLOPT_URL, $this->actionUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_REFERER, $this->actionUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_exec($ch);
		// echo "Logout".PHP_EOL;
	}

    private function parseResult(){
        // $this->result = file_get_contents("bni.hasil_mutasi.html");
        $dom = $this->dom;
        $dom->load($this->result);
        $transData = [];
        $orients = $dom->getElementsById('orient');

        foreach($orients as $orient){
            $str = '';
            $tables = $orient->getElementsByTagName('table');
            foreach($tables as $table){
                $span = $table->getElementByTagName('td')->getElementsByTagName('span');
                if(!empty($span[1])){
                    if(preg_match("[\d\d\-\w\w\w\-\d\d\d\d]", $span[1]->innertext())){
                        $str.="##";
                    }
                    $str.=$span[1]->innertext();
                    $str.=PHP_EOL;
                }
            }

            $exploded = explode("##",$str);
            unset($exploded[0]);

            foreach($exploded as $data){
                $d = explode(PHP_EOL,$data);
                if(isset($d[5]))
                    unset($d[5]);
                if(isset($d[6]))
                    unset($d[6]);
                $transData[] = $d;
            }
        }

        return $transData;
    }

    public function toArray(){
        try{
            return $this->parseResult();
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    public function toJson(){
        try{
            return json_encode($this->parseResult());
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

}


/*$config = [
        'credential' => [
            'username' => 'username_internet_banking',
            'password' => 'password_internet_banking'
        ],
        'nomor_rekening' => 'nomor_rekening' //0210123456,
        'range' => [
            'tgl_akhir' => date('d-M-Y',strtotime('2018-07-34')),
            'tgl_awal' => date('d-M-Y',strtotime('2018-07-01'))
        ],
];

$bni = new CekBNI($config);*/


?>