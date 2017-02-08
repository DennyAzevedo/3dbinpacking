<?php


//
// @param  $cab string	cadena
// @return string en mayusculas sin acentos ni espacios
function TextoCab($cad)
{
	$cad = cleanString($cad);
	$cad = trim(strtoupper($cad));
	$cad = str_replace ( " " , "" , $cad );
	$cad = str_replace ( ":" , "" , $cad );
  $cad = str_replace ( "/" , "" , $cad );
		
	return $cad;
}

// @param array 	$arr  array con el contenido del excel
// @param int		$cini columna inicial en la que se busca
// @return -1 no encuentra ninguna tabla , >-1 indice del array $tiposTabla encontrada
function buscaCabeceraTabla($arr, $cini)
{

//global 	$tiposTabla ;
$tiposTabla = array(
    array("W","H","D"),        
    array("width","heigth","depth"),  
    array("ancho","alto","fondo"), 
); 





//echo "--------",print_r($tiposTabla);

	foreach ($tiposTabla as $clave => $cabs)  // tipos de tablas clave
	{
		$is = true;
		$c = $cini;
		foreach ($cabs as $cab){  // columnas      
			 			
     	 $ar = TextoCab($arr[$c]);      	            
        if ($ar!= $cab ) {  $is=false; break;}else{$c+=1; }    		      
		}						
		if($is==true){		
			//echo "<br> tabla encontrada  clave $clave";// print_r($arr);				echo "<br>is-->"; print_r($cabs);
			return $clave;
		}			
	} 
	return -1;
} // end function




function buscaDatosSheet($arr,$she)
{	
	$arrRet = array();
	$f = 1;	
	do{
	  $ret = 0;
		//echo "------------".$arr->sheets[$she]['cells'][$f][1];
		if(strlen($arr->sheets[$she]['cells'][$f][1])){			  
			  $arrR= array( $arr->sheets[$she]['cells'][$f][1] ,$arr->sheets[$she]['cells'][$f][2] ); 
			  array_push($arrRet , $arrR ); 
				$ret = 1;
				$f+=1;
		}			
	}while($ret);				
	//echo "<pre>",print_r($arrRet);
	return $arrRet;
}


// @param	 	array $arr  array con el contenido del excel
// @param 	int 	$she  hoja 
// @param  	int 	$row 	fila de la cabecera 
// @param  	int 	$col 	culumna de la cabecera
// @param  	int 	$tab 	clave del array tiposTabla de la tabla encontrada
// @return 	array 			datos de la tabla encontrada
function buscaDatosTabla($arr, $she, $row, $col, $tab)
{
//global $tiposTabla;		
$tiposTabla = array(
    array("W","H","D"),        
    array("width","heigth","depth"),  
    array("ancho","alto","fondo"), 
); 


				
	$arrRet = array();
	$f = $row +1;
	$ff=0;
	do{
		$ret = 1;	
		$c= $col;		
		foreach ($tiposTabla[$tab] as $titcab){   // muestra encabezado de la tabla encontrada		
				$val = $arr->sheets[$she]['cells'][$f][$c];			
				if(!strlen($val)) $ret = 0;				
				$c+=1;
		}				
		if($ret){		
			$c= $col;	 $cc = 0;						
			if(count($tiposTabla[$tab])==1){
			  $arrRet[$ff][$cc] = $arr->sheets[$she]['cells'][--$f][++$c];
				$ret = 0;
			}else{						
				foreach ($tiposTabla[$tab] as $titcab){   // muestra encabezado de la tabla encontrada		
					$arrRet[$ff][$cc] = $arr->sheets[$she]['cells'][$f][$c];
					$c += 1; $cc+=1;
				}		
				// termina de a�adir campos
			
			}
		}
		$ff+=1; // fila del array devuelto
		$f+=1;  // fila siguiente		
	}while($ret);		
	
	//echo "<pre>",print_r($arrRet);
	
	return $arrRet;
	
} // end function

	
// @param $filePath string ruta del archivo xls 
// @return array con la posicion e indice de las tablas encontradas
function buscaLineasPresupuestoDesdeExcel($filePath)
{
	global $tiposTabla;
	$pila  = array();				
	require_once './excel/excel_reader.lib.php';				
	$excel = new PhpExcelReader;
	$excel->read($filePath);
	
	//echo "<pre>",print_r($excel);
	$nr_sheets = count($excel->sheets);   									
	$excel_data = '';              				
				for($i=0; $i<$nr_sheets; $i++) 
				{			// hojas			  	  
		  	  $sheet = $excel->sheets[$i];
		  	  $x = 1;			  	  
		  	  //echo '<h4>Sheet '. ($i + 1) .' (<em>'. $excel->boundsheets[$i]['name'] .'</em>)</h4>';  				  				  	  
				  while($x <= $sheet['numRows']) 
				  { // filas
				    $y = 1;
				    while($y <= $sheet['numCols']) 
				    { // columnas					    	
				      $cell = isset($sheet['cells'][$x][$y]) ? $sheet['cells'][$x][$y] : '';					      
				      //echo "<br>--->".TextoCab($cell)  ;					      					      
							// solo se busca la tabla si la culumna de la izq esta en blanco o es la primera columna (A)								
							if( $y==1 || ( $y>1 && $sheet['cells'][$x][$y-1]=="" ) )
							{
								$in = buscaCabeceraTabla($sheet['cells'][$x],$y);
								if($in != -1 )
								{									   
									$datos = buscaDatosTabla($excel, $i, $x, $y, $in);	// retorna array con los datos de la tabla encontrada													
									if(count($datos))
									{
										$datosSheet = buscaDatosSheet($excel, $i);	//Hoja Excel desde An,Bn n= 1...n++ hasta An=null
										//echo "<pre>....",print_r($datosSheet)."</pre>";
										
									}									  										
									$dataDes = $sheet['cells'][$x-1][$y]; 											
									$hash_file = hash_file('md5', $filePath);																								
									$elemento = array(sheet=>$i,row=>$x,column=>$y, table=>$in, path=>$filePath,hashFile=>$hash_file, dataH=>$tiposTabla[$in], data=>$datos, dataDes=>$dataDes, dataSheet=>$datosSheet );									  
								  array_push($pila, $elemento);									   									   
							  }									
							}								
				      $y++;
				    } // fin columnas 
				    $x++;
				  } // fin filas 
				}	// fin hojas			
				
				//echo "<pre>",print_r($pila);
				
				return $pila;		
} // end function 








function htmlCode($out)
{
$out = str_replace("�","&iexcl;",$out);
$out = str_replace("�","&cent;",$out);
$out = str_replace("�","&pound;",$out);
$out = str_replace("�","&curren;",$out);
$out = str_replace("�","&yen;",$out);
$out = str_replace("�","&brvbar;",$out);
$out = str_replace("�","&sect;",$out);
$out = str_replace("�","&uml;",$out);
$out = str_replace("�","&copy;",$out);
$out = str_replace("�","&ordf;",$out);
$out = str_replace("�","&laquo;",$out);
$out = str_replace("�","&not;",$out);
$out = str_replace("","&shy;",$out);
$out = str_replace("�","&reg;",$out);
$out = str_replace("�","&macr;",$out);
$out = str_replace("�","&deg;",$out);
$out = str_replace("�","&plusmn;",$out);
$out = str_replace("�","&sup2;",$out);
$out = str_replace("�","&sup3;",$out);
$out = str_replace("�","&acute;",$out);
$out = str_replace("�","&micro;",$out);
$out = str_replace("�","&para;",$out);
$out = str_replace("�","&middot;",$out);
$out = str_replace("�","&cedil;",$out);
$out = str_replace("�","&sup1;",$out);
$out = str_replace("�","&ordm;",$out);
$out = str_replace("�","&raquo;",$out);
$out = str_replace("�","&frac14;",$out);
$out = str_replace("�","&frac12;",$out);
$out = str_replace("�","&frac34;",$out);
$out = str_replace("�","&iquest;",$out);
$out = str_replace("�","&Agrave;",$out);
$out = str_replace("�","&Aacute;",$out);
$out = str_replace("�","&Acirc;",$out);
$out = str_replace("�","&Atilde;",$out);
$out = str_replace("�","&Auml;",$out);
$out = str_replace("�","&Aring;",$out);
$out = str_replace("�","&AElig;",$out);
$out = str_replace("�","&Ccedil;",$out);
$out = str_replace("�","&Egrave;",$out);
$out = str_replace("�","&Eacute;",$out);
$out = str_replace("�","&Ecirc;",$out);
$out = str_replace("�","&Euml;",$out);
$out = str_replace("�","&Igrave;",$out);
$out = str_replace("�","&Iacute;",$out);
$out = str_replace("�","&Icirc;",$out);
$out = str_replace("�","&Iuml;",$out);
$out = str_replace("�","&ETH;",$out);
$out = str_replace("�","&Ntilde;",$out);
$out = str_replace("�","&Ograve;",$out);
$out = str_replace("�","&Oacute;",$out);
$out = str_replace("�","&Ocirc;",$out);
$out = str_replace("�","&Otilde;",$out);
$out = str_replace("�","&Ouml;",$out);
$out = str_replace("�","&times;",$out);
$out = str_replace("�","&Oslash;",$out);
$out = str_replace("�","&Ugrave;",$out);
$out = str_replace("�","&Uacute;",$out);
$out = str_replace("�","&Ucirc;",$out);
$out = str_replace("�","&Uuml;",$out);
$out = str_replace("�","&Yacute;",$out);
$out = str_replace("�","&THORN;",$out);
$out = str_replace("�","&szlig;",$out);
$out = str_replace("�","&agrave;",$out);
$out = str_replace("�","&aacute;",$out);
$out = str_replace("�","&acirc;",$out);
$out = str_replace("�","&atilde;",$out);
$out = str_replace("�","&auml;",$out);
$out = str_replace("�","&aring;",$out);
$out = str_replace("�","&aelig;",$out);
$out = str_replace("�","&ccedil;",$out);
$out = str_replace("�","&egrave;",$out);
$out = str_replace("�","&eacute;",$out);
$out = str_replace("�","&ecirc;",$out);
$out = str_replace("�","&euml;",$out);
$out = str_replace("�","&igrave;",$out);
$out = str_replace("�","&iacute;",$out);
$out = str_replace("�","&icirc;",$out);
$out = str_replace("�","&iuml;",$out);
$out = str_replace("�","&eth;",$out);
$out = str_replace("�","&ntilde;",$out);
$out = str_replace("�","&ograve;",$out);
$out = str_replace("�","&oacute;",$out);
$out = str_replace("�","&ocirc;",$out);
$out = str_replace("�","&otilde;",$out);
$out = str_replace("�","&ouml;",$out);
$out = str_replace("�","&divide;",$out);
$out = str_replace("�","&oslash;",$out);
$out = str_replace("�","&ugrave;",$out);
$out = str_replace("�","&uacute;",$out);
$out = str_replace("�","&ucirc;",$out);
$out = str_replace("�","&uuml;",$out);
$out = str_replace("�","&yacute;",$out);
$out = str_replace("�","&thorn;",$out);
$out = str_replace("�","&yuml;",$out);
return $out;
}


function mes_castellano($out)
{
	$out = str_replace("January","Enero",$out);
	$out = str_replace("February","Febrero",$out);
	$out = str_replace("March","Marzo",$out);
	$out = str_replace("April","Abril",$out);
	$out = str_replace("May","Mayo",$out);
	$out = str_replace("June","Junio",$out);
	$out = str_replace("July","Julio",$out);
	$out = str_replace("August","Agosto",$out);
	$out = str_replace("September","Septiembre",$out);	
	$out = str_replace("October","Octubre",$out);
	$out = str_replace("November","Noviembre",$out);
	$out = str_replace("December","Diciembre",$out);
	return $out;
}

?>