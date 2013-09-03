<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_facturas
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class modPremiosHelper{
      	static function getType(){
      		$user = JFactory::getUser();
      		return (!$user->get('guest')) ? 'logout' : 'login';
      	}
        
        function parser($contents, $get_attributes=1, $priority = 'tag') {
                if (!$contents)
                    return array();
                if (!function_exists('xml_parser_create')) {
                    //print "'xml_parser_create()' function not found!";
                    return array();
                }
                //Get the XML parser of PHP - PHP must have this module for the parser to work
                $parser = xml_parser_create();
                xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
                xml_parse_into_struct($parser, trim($contents), $xml_values);
                xml_parser_free($parser);

                if (!$xml_values)
                    return; //Hmm...         
                //Initializations

                $xml_array = array();
                $parents = array();
                $opened_tags = array();
                $arr = array(); 

                $current = &$xml_array; //Refference
                //Go through the tags.
                $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
                foreach ($xml_values as $data) {
                    unset($attributes, $value); //Remove existing values, or there will be trouble
                    //This command will extract these variables into the foreach scope
                    // tag(string), type(string), level(int), attributes(array).
                    extract($data); //We could use the array by itself, but this cooler.

                    $result = array();
                    $attributes_data = array();

                    if (isset($value)) {
                        if ($priority == 'tag')
                            $result = $value;
                        else
                            $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
                    }

                    //Set the attributes too.
                    if (isset($attributes) and $get_attributes) {
                        foreach ($attributes as $attr => $val) {
                            if ($priority == 'tag')
                                $attributes_data[$attr] = $val;                 
                            else
                                $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                        }
                    }

                    //See tag status and do the needed.

                    if ($type == "open") {//The starting of the tag '<tag>'

                        $parent[$level - 1] = &$current;

                        if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag

                            $current[$tag] = $result;

                            if ($attributes_data)

                                $current[$tag . '_attr'] = $attributes_data;

                            $repeated_tag_index[$tag . '_' . $level] = 1;


                            $current = &$current[$tag];

                        } else { //There was another element with the same tag name

                            if (isset($current[$tag][0])) {//If there is a 0th element it is already an array

                                $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                                $repeated_tag_index[$tag . '_' . $level]++;

                            } else {//This section will make the value an array if multiple tags with the same name appear together

                                $current[$tag] = array($current[$tag], $result); //This will combine the existing item and the new item together to make an array

                                $repeated_tag_index[$tag . '_' . $level] = 2;



                                if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                    $current[$tag]['0_attr'] = $current[$tag . '_attr'];

                                    unset($current[$tag . '_attr']);

                                }

                            }

                            $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;

                            $current = &$current[$tag][$last_item_index];

                        }

                    } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'

                        //See if the key is already taken.

                        if (!isset($current[$tag])) { //New Key

                            $current[$tag] = ($result);

                            $repeated_tag_index[$tag . '_' . $level] = 1;

                            if ($priority == 'tag' and $attributes_data)

                                $current[$tag . '_attr'] = $attributes_data;

                        } else { //If taken, put all things inside a list(array)

                            if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                                // ...push the new element into that array.

                                $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = ($result);



                                if ($priority == 'tag' and $get_attributes and $attributes_data) {

                                    $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = ($attributes_data);

                                }

                                $repeated_tag_index[$tag . '_' . $level]++;

                            } else { //If it is not an array...

                                $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value

                                $repeated_tag_index[$tag . '_' . $level] = 1;

                                if ($priority == 'tag' and $get_attributes) {

                                    if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                        $current[$tag]['0_attr'] = ($current[$tag . '_attr']);

                                        unset($current[$tag . '_attr']);

                                    }



                                    if ($attributes_data) {

                                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = ($attributes_data);

                                    }

                                }

                                $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken

                            }

                        }

                    } elseif ($type == 'close') { //End of tag '</tag>'

                        $current = &$parent[$level - 1];

                    }

                } 

                return($xml_array);

            }
            
            function upload_image($destination_dir, $name_media_field, $post) {
                $user = JFactory::getUser();
                $username = $user->username;
                $tmp_name = $_FILES[$name_media_field]['tmp_name'];

                if (is_dir($destination_dir) && is_uploaded_file($tmp_name)) {
                    //echo "1<br>";
                    $tal = explode(".", $_FILES[$name_media_field]['name']);
                    $img_type = $_FILES[$name_media_field]['type'];
                    //echo $img_type."<br>";
                    switch ($img_type) {
                        case "text/xml":
                            $terminacion = ".xml";
                            break;
//                        case "application/pdf":
//                            $terminacion = ".pdf";
//                            break;
                    }
                    $renombre = uniqid($tal[0]);
                    if ($_FILES[$name_media_field]['size'] < 3100000) {
                        //echo "2<br>";
                        if (strpos($img_type, "xml")) {
                            //echo "3<br>";
                            if (move_uploaded_file($tmp_name, $destination_dir . '/' . $renombre . $terminacion)) {
                                return $renombre;
                            }
                        } else {
                            return "El tipo de archivo no es valido. Debe ser XML";
                        }
                    } else {
                        return "El archivo sobrepasa el limite de tama&ntilde;o permitido";
                    }
                }
            }
            
            function guardarXML($xml, $name){
                $user = JFactory::getUser();
                $dbo = & JFactory::getDBO();
                if($xml != ''){
                    
                    $folio = $xml['cfdi:Comprobante_attr']['folio'];
                    $fecha = $xml['cfdi:Comprobante_attr']['fecha'];
                    $fecha_sub = date('Y-m-d h:i:s');
                    
                    $query = 'SELECT factura FROM #__facturas WHERE factura = "' . $folio . '"; ';
                    $dbo->setQuery($query);
                    $exist = $dbo->loadObject();
                    
                    if(!$exist){
                        
                        $f = substr($fecha, 0, 10);
                        
                        if($f >= '2013-09-01'){
                            $a=0;$b=0;
                            foreach($xml['cfdi:Comprobante']['cfdi:Conceptos']['cfdi:Concepto'] as $i){
                                $cant       = $i['cantidad'];
                                $unidad     = $i['unidad'];
                                $ident      = $i['noIdentificacion'];
                                $desc       = $i['descripcion'];
                                $valor      = $i['valorUnitario'];
                                $importe    = $i['importe'];  
                                
                                if($cant != '' || !empty($cant)){
                                    
                                    $query = 'SELECT puntos FROM #__codigos_proplan WHERE nosap = "' . $ident . '"; ';
                                    $dbo->setQuery($query);
                                    $pts = $dbo->loadObject();
                                    
                                    if($pts->puntos == ''){ $punts = 0; }else{ $punts = $pts->puntos*$cant; }
                                    
                                    $query = 'INSERT INTO #__facturas (name, user, factura, fecha_fac, fecha_act, cantidad, unidad, no_ident, descripcion, valor, importe, puntos) ';
                                    $query .= 'VALUES ("'.$name.'", "'.$user->username.'", "'.$folio.'", "'.$fecha.'", "'.$fecha_sub.'", '.$cant.', "'.$unidad.'", "'.$ident.'", "'.$desc.'", '.$valor.', '.$importe.', '.$punts.');';
                                    $dbo->setQuery($query);
                                    
                                    if($dbo->query()){
                                        $a++;
                                    }else{
                                        $b++;
                                    }  
                                    
                                    if($b == 0){
                                        $ret = 'Factura cargada correctamente.';
                                    }elseif($a == 0){
                                        $ret = 'Factura cargada incorrectamente.';
                                    }elseif($a>0 && $b>0){
                                        $ret = 'Carga incompleta. intentalo de nuevo.';
                                    }  
                                    
                                }

                            }
                            
                        }else{
                           $ret = 'Solo son validas las facturas posteriores al primero de Septiembre del presente a&ntilde;o.'; 
                        }
                        
                    }else{
                        $ret = 'La factura ya fue cargada anteriormente.';
                    }                    
                    
                }else{
                    $ret = 'Archivo sin datos';
                }                              
                
                return $ret;
                
            }
            
            function getFacturas(){
                $user = JFactory::getUser();
                $dbo = & JFactory::getDBO();
                
                $query = 'SELECT name, factura, fecha_fac FROM #__facturas WHERE user = "' . $user->username . '" GROUP BY factura; ';
                $dbo->setQuery($query);
                $fact = $dbo->loadObjectList();
                
                if($fact != '' || $fact != Null){
                    
                    $c = 1;
                    $tbl = '';
                    foreach($fact as $f) : 
                        
                         $tbl .= '<tr>
                                    <td width="50px">'.$c.'.</td>
                                    <td width="350px">'.$f->name.'.xml</td>
                                    <td>'.$f->fecha_fac.'</td>
                                 </tr>';  
                        $c++;     
                    endforeach; 
                    
                }else{
                    $tbl = '';
                }
                
                
                return $tbl;
                
            }

}
?>
