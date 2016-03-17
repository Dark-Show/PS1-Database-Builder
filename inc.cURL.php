<?php
function cURLcheckBasicFunctions() 
{ 
  if( !function_exists("curl_init") && 
      !function_exists("curl_setopt") && 
      !function_exists("curl_exec") && 
      !function_exists("curl_close") ) die("cURL: Error"); 
  else return true; 
} 

function HTTP_GET($url) 
{ 
  if( !cURLcheckBasicFunctions() ) return(0); 
  $ch = curl_init(); 
  
  if($ch) 
  { 
      if( !curl_setopt($ch, CURLOPT_URL, $url) ) 
      { 
        curl_close($ch);
        return(0);
      } 
      if( !curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ) return(0);
      if( !curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ) return(0);
      if( !curl_setopt($ch, CURLOPT_ENCODING, "") ) return(0);	  
      if( !curl_setopt($ch, CURLOPT_HEADER, 0) ) return(0); 
      
      $data=curl_exec($ch);
      
      curl_close($ch); 
	  return($data);
  } 
  else return(0);
} 


?> 
