<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ValidarConsultaController extends Controller
{



public function validConsulta(Request $request){

  $nroContrato = $request->nroContrato;
  $userSTV = env('USER_SIMPLETV');
  $passwordSTV = env('PWD_SIMPLETV');
  $urlCustomer = env('URL_CUSTOMER');



  $timeStamp = 'PT'.date('h').'H'.date('i').'M';
  $serverTime = date('Y').'-'.date('m').'-'.date('d').'T'.date('h').':'.date('i').':'.date('s');  


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $urlCustomer,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT =>30,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:v1="http://localhost/customerservice/service/v1.0" xmlns:v11="http://localhost/base/data/v1.0" xmlns:v12="http://localhost/customerservice/message/v1.0" xmlns:v13="http://localhost/customerservice/data/v1.0">
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
            <v11:Token>?</v11:Token>
            <v11:UserName>'.$userSTV.'</v11:UserName>
         </v11:AuthenticationHeader>
         <v11:CacheControlHeader/>
      </v1:BaseHeaderInfo>
   <wsa:Action>GetCustomersBySerialNumber</wsa:Action></soap:Header>
   <soap:Body>
      <v12:GetCustomerBySerialNumberRequest>
         <!--Optional:-->
         <v1:GetCustomerBySerialNumberInfo>
            <!--Optional:-->
            <v13:SmartCardNumber>'.$nroContrato.'</v13:SmartCardNumber>
         </v1:GetCustomerBySerialNumberInfo>
      </v12:GetCustomerBySerialNumberRequest>
   </soap:Body>
</soap:Envelope>',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/soap+xml;charset=UTF-8;action="GetCustomersBySerialNumber"',
    'Accept-Encoding: gzip,deflate'
  ),
));

$xml = curl_exec($curl);
$error = 0;

if($xml){
    $xml = json_encode($xml);
    $arrayCode = explode('<\/b:',$xml);
    if(isset($arrayCode[1])){
        $customID = $this->procesarCustomID($xml); 
        $arrayContrato = ['error'=>0,
                          'CustomerId'=>$customID
                         ];
    }else{    
        $codeError = '';
        $posiReason = strrpos($xml,'ME-');
        if($posiReason){
            $error = 2;
            $codeError = substr($xml, $posiReason,7);
            $mensajeError = $this->errorMensaje($codeError);
            $codeError = 'CC-' . $codeError;

        }else{
            $posiReason = strrpos($xml,'MI-');
            if($posiReason){
                $error = 3;
                $codeError = substr($xml, $posiReason,7);
                $mensajeError = $this->errorMensaje($codeError);
                $codeError = 'CC-' . $codeError;
            }else{
                $posiReason = strrpos($xml,'temporarily unavailable');
                if($posiReason){
                    $error = 4;
                    $codeError = 'S/C STV/LV CC 0000';
                    $mensajeError ='Estimado cliente, ha ocurrido un error inesperado. Por favor intente de nuevo mas tarde.';
                }else{
                    $error = 5;
                    $codeError = 'S/C LV CC 9000';
                    $mensajeError ='Estimado cliente, ha ocurrido un error inesperado. Por favor intente de nuevo mas tarde.';
                }  
            }
        }
        $arrayContrato = ['error'=>$error,
        	                'codeError'=>$codeError,
                          'mensajeError'=>$mensajeError
                         ];
    }
}else{
    $arrayContrato = ['error'=>1,
                      'codeError' => 'S/C STV/TM CC 9999',
                      'mensajeError'=>'Time Out'
    ];
}    
return response()->json($arrayContrato);  


}


function procesarCustomID($xml){
    $xxl = json_decode($xml);  
    $arrayCode = explode('<b:',$xxl);
    $cadena = $arrayCode[3];
    $cade = explode('>',$cadena);
    $cadena2 = $cade[1];
    $cadena3 = str_replace('</b:CustomerId','',$cadena2);
    return $cadena3;
}


function errorMensaje($codeError){
  $mensaje='';
  switch($codeError){
    case 'MI-0021':
        $mensaje = 'El numero de Smart Card que ha introducido no se encuentra asociado a ningun cliente.';
        break;

    case 'MI-0061':
        $mensaje = 'El numero de tarjeta de acceso es invalido. Por favor ingrese a nuestra pagina web www.simple.com.ve.';
        break;

    case 'ME-0006':
        $mensaje = 'Ha ocurrido un error interno. Por favor valide los parametros de llamada al servicio';
        break;

    case 'ME-0062':
        $mensaje = 'Disculpe, para realizar este proceso debe comunicarse con el administrador de la cuenta';
        break;        
    default:
        $mensaje = 'Ha ocurrido un error inesperado, intente mas tarde.';
  }
  $mensaje = $mensaje . " [STV]";
  return $mensaje;
}
   
}
