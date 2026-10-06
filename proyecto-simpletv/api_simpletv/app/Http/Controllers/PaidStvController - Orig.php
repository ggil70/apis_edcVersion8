<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaidStvController extends Controller
{
 
  public function paidStv(Request $request){  

  $customerId = $request->customerId;
  $monto = $request->monto;
  $referencia = $request->referencia;
  $userSTV = $request->userSTV;
  $passwordSTV = $request->passwordSTV;
  $ledgerAccount = $request->ledgerAccount;
  

  //$urlPaid = $request->urlPaid;
  $urlPaid = env('URL_PAID');


  $requestId = date('Ymdhisu');
  $timeStamp = 'P'.date('Y').'Y'.date('m').'M'.date('d').'DT'.date('h').'H'.date('m').'M'.date('s').'S';
  $serverTime = date('Y').'-'.date('m').'-'.date('d').'T'.date('h').':'.date('i').':'.date('s');
  $nroReferencia = date('Ymdhisu');


  $curl = curl_init();
  curl_setopt_array($curl, array(
  CURLOPT_URL => $urlPaid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:v1="http://localhost/product/service/v1.0"
xmlns:v11="http://localhost/base/data/v1.0" xmlns:v12="http://localhost/product/message/v1.0" xmlns:v13="http://localhost/product/data/v1.0">
<soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
<To soap:mustUnderstand="1" xmlns="http://www.w3.org/2005/08/addressing">https://stvproducttesting.simple.com.ve/Product.svc</To>
<v1:BaseHeaderInfo>
<v11:SecurityToken>?</v11:SecurityToken>
<v11:RequestId>'.$requestId.'</v11:RequestId>
<v11:SystemId>Bancos</v11:SystemId>
<v11:TimeStamp>'.$timeStamp.'</v11:TimeStamp>
<v11:Login>'.$userSTV.'</v11:Login>
<v11:AuthenticationHeader>
<!--Optional:-->
<v11:ClientName>?</v11:ClientName>
<!--Optional:-->
<v11:ClientProof>?</v11:ClientProof>
<!--Optional:-->
<v11:Culture>?</v11:Culture>
<v11:Dsn>?</v11:Dsn>
<!--Optional:-->
<v11:Extended>?</v11:Extended>
<v11:Proof>'.$passwordSTV.'</v11:Proof>
<v11:ServerTime>'.$serverTime.'</v11:ServerTime>
<v11:Token>?</v11:Token>
<v11:UserName>'.$userSTV.'</v11:UserName>
</v11:AuthenticationHeader>
<v11:CacheControlHeader/>
</v1:BaseHeaderInfo>
<wsa:Action>ApplyPaymentPrepaid</wsa:Action></soap:Header>
<soap:Body>
<v12:ApplyPaymentPrepaidRequest>
<!--Optional:-->
<v1:ApplyPaymentPrepaidInfo>
<!--Optional:-->
<v13:CustomerId>'.$customerId.'</v13:CustomerId>
<!--Optional:-->
<v13:BaseAmount>'.$monto.'</v13:BaseAmount>
<!--Optional:-->
<v13:FinancialBatchId>'.$referencia.'</v13:FinancialBatchId>
<!--Optional:-->
<v13:LedgerAccount>'.$ledgerAccount.'</v13:LedgerAccount>
<!--Optional:-->
<v13:PaymentReference>'.$nroReferencia.'</v13:PaymentReference>
<!--Optional:-->
<v13:CodeCurrency>VEN</v13:CodeCurrency>
</v1:ApplyPaymentPrepaidInfo>
</v12:ApplyPaymentPrepaidRequest>
</soap:Body>
</soap:Envelope>',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/soap+xml;charset=UTF-8;action="ApplyPaymentPrepaid"'),
));

/*$veces = 0;
$xml = false;
while($xml == false && $veces < 5){
    $veces = $veces + 1;
    $xml = curl_exec($curl);
} 
$xml = curl_exec($curl);
*/

//"http:\/\/localhost\/product\/service\/v1.0\/IProduct\/ApplyPaymentPrepaidResponse<\/a:Action><\/s:Header>2172484<\/b:CustomerId>1.0<\/b:BaseAmount>16964471526976109<\/b:TransactionId>2024-11-03T23:59:59<\/b:BillingDate>396<\/b:DaysAvailableProgramming><\/ApplyPaymentPrepaidResult><\/ApplyPaymentPrepaidResponse><\/s:Body><\/s:Envelope>"


$xml = curl_exec($curl);

if($xml){
    $xml = json_encode($xml);
    $posiResponse = strrpos($xml,'Response');
    if($posiResponse){
        $array = explode('<\/', $xml); 
        //CustomerId
        $customerId = $this->valores($array[2]);
        //TransactionId
        $transactionId = $this->valores($array[4]);
        //Fecha transaccion
        $daysAvailableProgramming = $this->valores($array[6]);
        $arrayPaid = [
        	   'error'=>'0',
             'transactionId'=>$transactionId,
             'daysProgramming'=>$daysAvailableProgramming
        ];
        return response()->json($arrayPaid);
    }else{
        $posiError = strrpos($xml,'<\/');
        if($posiError){
            $array = explode('<\/', $xml); 
            $codeError = $this->valores($array[5]);
        }else{
            $codeError = 'ERR-PD-8888';
        }  

        $arrayPaid = [
         	  'error'=>'1',
            'codeError'=>$codeError,
        ];
        return response()->json($arrayPaid);
    }  
}else{
    //timeout
    $arrayPaid = ['error'=>1,
                  'codeError' => 'TM-9999',
    ];  
    return response()->json($arrayPaid);
}  //fin if($xml)

}


function valores($cadena){
   $cadena = trim($cadena);
   $posi = strrpos($cadena,'>') + 1;
   $cadeResult = substr($cadena, $posi);
   return $cadeResult;
}




} //fin clase
