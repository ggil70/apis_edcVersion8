<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetBalanceController extends Controller
{


public function getMount(Request $request){

	$customerId = $request->customerId;
	$userSTV = $request->userSTV;
	$passwordSTV = $request->passwordSTV;
	//$urlBalance = $request->urlBalance;

	$urlBalance = env('URL_BALANCE');
	
  	$timeStamp = 'PT'.date('h').'H'.date('i').'M';
  	$serverTime = date('Y').'-'.date('m').'-'.date('d').'T'.date('h').':'.date('i').':'.date('s');	

	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $urlBalance,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:v1="http://localhost/finnance/service/v1.0" xmlns:v11="http://localhost/base/data/v1.0" xmlns:v12="http://localhost/finnance/message/v1.0" xmlns:v13="http://localhost/finnance/data/v1.0">
	   <soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
	   <v1:BaseHeaderInfo>
	         <v11:SecurityToken>?</v11:SecurityToken>
	         <v11:RequestId>?</v11:RequestId>
	         <v11:SystemId>Bancos</v11:SystemId>
	         <!--Optional:-->
	         <v11:TimeStamp>'.$timeStamp.'</v11:TimeStamp>
	         <v11:Login>?</v11:Login>
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
	            <v11:Token></v11:Token>
	            <v11:UserName>'.$userSTV.'</v11:UserName>
	         </v11:AuthenticationHeader>
	         <v11:CacheControlHeader/>
	      </v1:BaseHeaderInfo>
	      <wsa:Action>GetBalance</wsa:Action></soap:Header>

	   <soap:Body>
	      <v12:GetBalanceRequest>
	         <!--Optional:-->
	         <v13:GetBalanceInfo>
	            <!--Optional:-->
	            <v13:SmartCardNumber>?</v13:SmartCardNumber>
	            <!--Optional:-->
	            <v13:CustomerId>'.$customerId.'</v13:CustomerId>
	         </v13:GetBalanceInfo>
	      </v12:GetBalanceRequest>
	   </soap:Body>
	</soap:Envelope>',
	  CURLOPT_HTTPHEADER => array(
	    'Content-Type: application/soap+xml;charset=UTF-8;action="GetBalance"',    

	  ),
	));


	$xml = curl_exec($curl);
	//$response = 'http://localhost/finnance/service/v1.0/IFinnance/GetBalanceResponse7886.232024-04-12T23:59:591911433.38';

    
    if($xml){
		$soap_response = json_encode($xml);
	    $posiResponse = strrpos($soap_response,'Response');
		if($posiResponse){
			$posiResponse = strrpos($xml,'<Balance>');
	        $cadena = substr($xml, $posiResponse);
		    $array = explode('>',$cadena);
		    $balance = str_replace('</Balance', '', $array[1]);
		    $PrepaidDisconnectDate = str_replace('</PrepaidDisconnectDate', '', $array[3]);

		    $DaysAvailableProgramming = str_replace('</DaysAvailableProgramming', '', $array[5]);

		    $AmountPrepaid = str_replace('</AmountPrepaid', '', $array[7]);
		    $arrayBalance = ['error'=>0,
		    	             'balance'=>$balance,
	                         'prepaidDate'=>$PrepaidDisconnectDate,
	                         'daysProgramming'=>$DaysAvailableProgramming,
	                         'amountPrepaid'=>$AmountPrepaid
	                        ]; 
		}else{  
	        $posiReason = strrpos($soap_response,'ME-');
	        if($posiReason){
		        $codeError = substr($soap_response, $posiReason,7);
		    }else{
		    	$posiReason = strrpos($soap_response,'MI-');
	            if($posiReason){
			    	$codeError = substr($soap_response, $posiReason,7);
			    }else{
	                $posiReason = strrpos($soap_response,'temporarily unavailable');

	                if($posiReason){
	                    $codeError = 'SC-STV-LV-GB-0000';
	                }else{
	                	$posiReason = strrpos($soap_response,'DeserializationFailed');
                        if($posiReason){
	                       $codeError = 'ERR-CID-LV-GB-8000';
  	                       $cadeMensaje ='Error Codificación Customer_Id';
                        }else{       
	                       $codeError = 'SC-LV-GB-9000';
	                    }
	                } 
			    }	
		    }    
		    $arrayBalance = ['error'=>1,
		                     'codeError'=>$codeError
		                    ];
		}
	}else{
	    $arrayBalance = ['error'=>1,
	                      'codeError' => 'SC-STV-TM GB-9999'
        ];         
	}


    return response()->json($arrayBalance);
}	


	function valores($cadena){
	   $cadena = trim($cadena);
	   $posi = strrpos($cadena,'>') + 1;
	   $cadeResult = substr($cadena, $posi);
	   return $cadeResult;

	}





}
