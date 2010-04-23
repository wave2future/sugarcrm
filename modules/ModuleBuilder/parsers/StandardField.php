<?php
if (! defined ( 'sugarEntry' ) || ! sugarEntry)
    die ( 'Not A Valid Entry Point' ) ;
/*********************************************************************************
 * SugarCRM is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

require_once ('modules/DynamicFields/DynamicField.php') ;

class StandardField extends DynamicField
{
	var $custom_def = array();
	var $base_path = "";
	

    function __construct($module = '') {
        $this->module = (! empty ( $module )) ? $module :( (isset($_REQUEST['module']) && ! empty($_REQUEST['module'])) ? $_REQUEST ['module'] : '');
        $this->base_path = "custom/Extension/modules/{$this->module}/Ext/Vardefs";
    }
    
    protected function loadCustomDef($field){
    	global $beanList;
    	if (!empty($beanList[$this->module]) && is_file("custom/Extension/modules/{$this->module}/Ext/Vardefs/sugarfield_$field.php"))
    	{
    		$dictionary = array($beanList[$this->module] => array("fields" => array($field => array())));
            include("$this->base_path/sugarfield_$field.php");
            if (!empty($dictionary[$beanList[$this->module]]) && isset($dictionary[$beanList[$this->module]]["fields"][$field]))
                $this->custom_def = $dictionary[$beanList[$this->module]]["fields"][$field];
    	}
    }
    
    /**
     * Adds a custom field using a field object
     *
     * @param Field Object $field
     * @return boolean
     */
    function addFieldObject(&$field){
        global $dictionary, $beanList;
        if (empty($beanList[$this->module]))
            return false;
        
        $bean_name = $beanList[$this->module];
        if (empty($dictionary[$bean_name]) || empty($dictionary[$bean_name]["fields"][$field->name]))
            return false;

        $currdef = $dictionary[$bean_name]["fields"][$field->name];
        $this->loadCustomDef($field->name);
        
        foreach ($field->vardef_map as $property => $fmd_col){
            if ($property == "label_value" || $property == "label" || (substr($property, 0,3) == 'ext' && strlen($property) == 4)) continue;
            // Bug 37043 - Avoid writing out unneeded vardef defintions.
            if ($property == "action") continue;
            
        	if (isset($field->$property) && ((!isset($currdef[$property]) && !empty($field->$property)) || (isset($currdef[$property]) && $currdef[$property] != $field->$property)))
            {
                // Bug 37043 - Avoid writing out vardef defintions that are the default value.
                if ( !$this->_isDefaultValue($property,$field->$property) ) {
                    $this->custom_def[$property] = is_string($field->$property) ? htmlspecialchars_decode($field->$property, ENT_QUOTES) : $field->$property;
                }
            }
        }
        if ( empty($this->custom_def) ) {
            return true;
        }
        
		$file_loc = "$this->base_path/sugarfield_{$field->name}.php";
        $out =  "<?php\n // created: " . date('Y-m-d H:i:s') . "\n";
        foreach ($this->custom_def as $property => $val) 
        {
        	$out .= override_value_to_string_recursive(array($bean_name, "fields", $field->name, $property), "dictionary", $val) . "\n";
        }
        
        $out .= "\n ?>";
        
        if (!file_exists($this->base_path))
            mkdir_recursive($this->base_path);
            
        if( $fh = @sugar_fopen( $file_loc, 'w' ) )
	    {
	        fputs( $fh, $out);
	        fclose( $fh );
	        return true ;
	    }
	    else
	    {
	        return false ;
	    }
    }
    
    private function _isDefaultValue(
        $property,
        $value
        )
    {
        switch ($property) {
        case "importable": case "reportable":
            return ( $value == 'true' || $value == '1' || $value == true ); break;
        case "len":
            return ( $value == "255" ); break;
        }
        
        return false;
    }
}

?>
