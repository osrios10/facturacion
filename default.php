$msj='';
$data = new modPremiosHelper();    
if (!empty($_POST)) {    
    $respuestaupload = $data->upload_image('/opt/lampp/htdocs/sites/proplan/modules/mod_premios/tmpl/facturas', 'uploadImage', $_POST);
    if($respuestaupload == 'El tipo de archivo no es valido. Debe ser XML' || $respuestaupload == 'El archivo sobrepasa el limite de tama&ntilde;o permitido') {
        echo $respuestaupload;
    }else{
        if (file_exists('modules/mod_premios/tmpl/facturas/'.$respuestaupload.'.xml')) {
            
                $archivo= file_get_contents('modules/mod_premios/tmpl/facturas/'.$respuestaupload.'.xml'); 
                $xml = $data->parser($archivo);            
                unlink('modules/mod_premios/tmpl/facturas/'.$respuestaupload.'.xml');
                
                if($xml){
                    $save = $data->guardarXML($xml, $respuestaupload);
                    if($save){
                        $msj = $save;
                    }else{
                        $msj = 'Error al cargar archivo, intentelo de nuevo'; 
                    }
                }else{
                   $msj = 'Error al cargar archivo, intentelo de nuevo'; 
                } 
                
        } else {
            $msj = 'Error al cargar archivo, intentelo de nuevo';
        }     
    }
}
$facturas = $data->getFacturas();
