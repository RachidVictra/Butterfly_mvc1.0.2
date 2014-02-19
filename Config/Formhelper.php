<?php

namespace App\Config\FormHelper;

use App\Config\Application as App;

/**
 * appError File : Class Error .
 *
 * @author Rachid
 */
class Form extends App\Application {

    private $data;
    private $errors;
    private $value;
    private $attribut;
    public $post;
    private $file;
    public $action;

    public function __construct() {
        if ($this->isPosted()) {
            $this->post = $_POST;
            $this->data = $this->post;
            $this->file = $_FILES;
        }
    }

    /**
     * setFormErrors($field, $msgError) : Specify the error of each object in the form.
     * @param type $field
     * @param type $msgError
     */
    public function setFormErrors($field, $msgError) {
        $this->errors[$field][] = $msgError;
    }

    private function getFormErrors($field) {
        if (isset($this->errors[$field]))
            return '<span class="error_form">' . $this->errors[$field][0] . '</span>';
        else
            return '';
    }

    private function treatData($dataField) {
        return htmlspecialchars(stripslashes(trim($dataField)));
    }

    /**
     * start($url, $attributs = array()) : Create the form tag.
     * @param type $url : array('controller', 'action', 'id')
     * @param type $attributs
     * @return type
     */
    public function start($url, $attributs = array()) {
        $this->action = '';
        if (preg_match('/[\w+]\/[\w+]/', $url))
            $this->action = $this->parserUrl($url);
        else if($url == ''){
        	$url = parent::$requestTab['cap']['controller'].'/'.parent::$requestTab['cap']['action'];
        	$this->action = $this->parserUrl($url);
        }else
            $this->setError('Form action', 'you must specify the action');

        $token = '';
        if (!empty($_SESSION['butterfly_token']))
            $token = $_SESSION['butterfly_token'];
        else
            $_SESSION['butterfly_token'] = md5(uniqid(microtime(), true));
        $tokenInput = $this->hidden('butterfly_token', array('value' => $token));

        $getAttribut = '';
        foreach ($attributs as $key => $value)
            $getAttribut .= "$key = '$value' ";

        return "<form action = '$this->action' method = 'POST' $getAttribut>\n\t$tokenInput";
    }

    /**
     * text($field, $attributs = array()) : Create a text field.
     * @param type $field
     * @param type $label
     * @param type $attributs
     * @return type
     */
    public function text($field, $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for='$field-for' class='$classLabel'>$label</label>\n\t";

        $getAttribut = '';
        $default = '';
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<input type = 'text' name = 'data[$field]' value = '$value' $getAttribut />\n";
        $return .= $this->getFormErrors($field);
        return $return;
    }

    /**
     * password($field, $attributs = array()) : Create a password field.
     * @param type $field
     * @param type $attributs
     * @return type
     */
    public function password($field, $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for = '$field-for' class = '$classLabel'>$label</label>\n\t";

        $getAttribut = '';
        $default = '';
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<input type = 'password' name = 'data[$field]' value = '$value' $getAttribut />\n";
        $return .= $this->getFormErrors($field);
        return $return;
    }

    /**
     * hidden($field, $attributs = array()) : Create a hidden field.
     * @param type $field
     * @param type $attributs
     * @return type
     */
    public function hidden($field, $attributs = array()) {
        $return = '';
        $getAttribut = '';
        $default = '';
        if (is_array($attributs))
            foreach ($attributs as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<input type = 'hidden' name = 'data[$field]' value = '$value' $getAttribut />\n";
        return $return;
    }

    /**
     * file($field, $attributs = array()) : Create a file field.
     * @param type $field
     * @param type $attributs
     * @return type
     */
    public function file($field, $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for = '$field-for' class = '$classLabel'>$label</label>\n\t";

        $getAttribut = '';
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name' || trim(strtolower($key)) === 'value')
                    continue;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $return .= "<input type = 'file' name = '$field'  $getAttribut />\n";
        $return .= $this->getFormErrors($field);
        return $return;
    }

    /**
     * textarea($field, $attributs = array()) : Create a textarea field.
     * @param type $field
     * @param type $value
     * @param type $attributs
     * @return type
     */
    public function textarea($field, $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for = '$field-for' class = '$classLabel'>$label</label>\n\t";
        $getAttribut = '';
        $default = '';
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<textarea name = 'data[$field]' $getAttribut>$value</textarea>\n";
        $return .= $this->getFormErrors($field);
        return "$return\n";
    }

    /**
     * select($field, $options = array(), $attributs = array()) : Create a list of choices.
     * @param type $name
     * @param type $options
     * @param type $selected
     * @param type $params
     * @return type
     */
    public function select($field, $options = array(), $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for='$field-for' class='$classLabel'>$label</label>\n\t";
        $getAttribut = '';
        $default = '';
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<select name ='data[$field]' $getAttribut >\n";
        foreach ($options as $key => $option) {
            $return.='<option value = "' . $key . '"' . ($value != $key ? '' : ' selected = "selected"') . '>' . $option . "</option>\n";
        }
        return "$return</select>" . $this->getFormErrors($field) . "\n";
    }

    /**
     * multiSelect($field, $options = array(), $attributs = array()) : Create a list of multi choices.
     * @param type $field
     * @param type $options
     * @param type $attributs
     * @return type
     */
    public function multiSelect($field, $options = array(), $attributs = array()) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for='$field-for' class='$classLabel'>$label</label>\n\t";
        $getAttribut = '';
        $default = array();
        if (array_key_exists('params', $attributs))
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value') {
                    if (!is_array($val))
                        $this->setError('Form - multiselect', 'default must be a array()');
                    $default = $val;
                }
                else
                    $getAttribut .= "$key = '$val' ";
            }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $return .= "<select name = 'data[$field][]' $getAttribut multiple>\n";
        foreach ($options as $key => $option) {
            $return.="<option value = $key " . (in_array($key, $value) ? 'selected' : '') . ">$option</option>\n";
        }
        return "$return</select>" . $this->getFormErrors($field) . "\n";
    }

    /**
     * radio($field, $items = array(), $attributs = array(), $align = 1) : Create a Radio Button.
     * @param type $field
     * @param type $items
     * @param type $attributs
     * @param type $align
     * @return type
     */
    public function radio($field, $items = array(), $attributs = array(), $align = 1) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for='$field-for' class='$classLabel'>$label</label>\n\t";
        $getAttribut = '';
        $default = '';
        if (array_key_exists('params', $attributs)) {
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value')
                    $default = $val;
                else
                    $getAttribut .= "$key = '$val' ";
            }
        }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        foreach ($items as $key => $item)
            $return .= ($align ? '<br>' : '') . "<input type = 'radio' name = 'data[$field]' value = '$key' $getAttribut " . (strtolower($key) == strtolower($value) ? 'checked' : '') . " />$item\n";
        $return .= ($align) ? '<br>' . $this->getFormErrors($field) : $this->getFormErrors($field);
        return "$return\n";
    }

    /**
     * checkbox($field, $items = array(), $attributs = array(), $align = 1) : Create a chckbox.
     * @param type $field
     * @param type $items
     * @param type $attributs
     * @param type $align
     * @return type
     */
    public function checkbox($field, $items = array(), $attributs = array(), $align = 1) {
        $return = '';
        $label = ucfirst($field) . ' : ';
        if (array_key_exists('label', $attributs)) {
            $label = $attributs['label'];
        }
        $classLabel = $field . '-label';
        $return .= "<label for='$field-for' class='$classLabel'>$label</label>\n\t";
        $getAttribut = '';
        $default = array();
        if (array_key_exists('params', $attributs)) {
            foreach ($attributs['params'] as $key => $val) {
                if (trim(strtolower($key)) === 'name')
                    continue;
                if (trim(strtolower($key)) === 'value') {
                    if (!is_array($val))
                        $this->setError('Form - checkbox', 'default must be a array()');
                    $default = $val;
                }
                else
                    $getAttribut .= "$key = '$val' ";
            }
        }
        $dt = $this->data['data'];
        $value = (isset($dt[$field])) ? $dt[$field] : $default;
        $i = 0;
        foreach ($items as $key => $item) {
            $return .= ($align ? '<br>' : '') . "<input type = 'checkbox' name = 'data[$field][$i]' value = '$key' $getAttribut " . (in_array($key, $value) ? 'checked' : '') . " />$item\n";
            $i++;
        }
        $return .= ($align) ? '<br>' . $this->getFormErrors($field) : $this->getFormErrors($field);
        return "$return\n";
    }

    /**
     * button($name, $attributs = array()) : Create a Button.
     * @param type $value
     * @param type $attributes
     * @return string
     */
    public function button($name, $attributs = array()) {
        $getAttribut = '';
        foreach ($attributs as $key => $val) {
            if (trim(strtolower($key)) === 'name')
                continue;
            $getAttribut .= "$key = '$val' ";
        }
        return "<input type = 'button' name = '$name' $getAttribut/>\n";
    }

    /**
     * submit($name, $attributs = array()) : Create a submit Button.
     * @param type $value
     * @param type $attributes
     * @return string
     */
    public function submit($name, $attributs = array()) {
        $getAttribut = '';
        foreach ($attributs as $key => $val) {
            if (trim(strtolower($key)) === 'name')
                continue;
            $getAttribut .= "$key = '$val' ";
        }
        return "<input type = 'submit' name = '$name' $getAttribut/>\n";
    }

    /**
     * reset($name, $attributs = array()) : Create a reset Button.
     * @param type $value
     * @param type $attributes
     * @return string
     */
    public function reset($name, $attributs = array()) {
        $getAttribut = '';
        foreach ($attributs as $key => $val) {
            if (trim(strtolower($key)) === 'name')
                continue;
            $getAttribut .= "$key = '$val' ";
        }
        return "<input type = 'reset' name='$name' $getAttribut/>\n";
    }

    /**
     * end($submit = null, $attributs = array()) : Close form. With param create submit button and close form.
     * @param type $submit
     * @param type $attributes
     * @return string
     */
    public function end($submit = null, $attributs = array()) {
        $end = '';
        $getAttribut = '';
        foreach ($attributs as $key => $val) {
            if (trim(strtolower($key)) === 'name')
                continue;
            $getAttribut .= "$key = '$val' ";
        }
        if (!empty($submit))
            $end .= "<input type = 'submit' name = '$submit' $getAttribut/>\n";
        $end .= "</form>\n";
        return $end;
    }

    /**
     * isPosted() : Verifie the form is posted or not yet!
     * @return True : posted  False : not posted
     */
    public function isPosted() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return TRUE;
        else
            return FALSE;
    }

    /**
     * setAttribut($field) : Specify the field
     * @param type $field
     * @return \App\Config\FormHelper\Form
     */
    public function setAttribut($field) {
        if (key_exists('data', $this->post))
            if (key_exists($field, $this->post['data'])) {
                $this->value = $this->post['data'][$field];
                $this->attribut = $field;
            }
            else
                $this->setError("Form Field", "This field <i>$field</i> is not exist in this form !");
        return $this;
    }

    private function attributSelected() {
        if ($this->isPosted()) {
            if (empty($this->attribut))
                $this->setError("Form Field", "You must specify the field through the setAttribut(field) function !");
        }
        else
            $this->setError("Form", "This form has not yet been posted !");
    }

    /**
     * checkEmpty($msgError = NULL) : Check that the field that was selected is empty
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkEmpty($msgError = NULL) {
        $this->attributSelected();
        if (empty($this->value))
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? ' * This field must be not null.' : $msgError);
        return $this;
    }

    /**
     * checkMaxchar($taille, $msgError = NULL) : Verified that the size field value does not exceed the size specified in parameter
     * @param type $taille
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkMaxchar($taille, $msgError = NULL) {
        $this->attributSelected();
        if (!is_numeric($taille))
            $this->setError("Error parameter type maxChar", "This parameter must be numeric!");
        if (strlen($this->value) > $taille)
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * This field must not pass $taille character(s)." : $msgError);

        return $this;
    }

    /**
     * checkDate($msgError = NULL, $format = NULL) : verifies the date field
     * @param type $msgError
     * @param string $format
     * @return \App\Config\FormHelper\Form
     */
    public function checkDate($msgError = NULL, $format = NULL) {
        $this->attributSelected();
        $formats = array('d.m.Y', 'd/m/Y', 'd/M/Y', 'd/m/y', 'd/M/y', 'd/MM/Y', 'd-m-y', 'd-m-Y', 'd-M-Y', 'd-M-y');
        if ($format == NULL) {
            foreach ($formats as $format) {
                if ($this->validateDate($this->value, $format))
                    return $this;
            }
        }
        else {
            if ($this->validateDate($this->value, $format))
                return $this;
        }

        $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * This date is not valid." : $msgError);

        return $this;
    }

    private function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * checkMinchar($taille, $msgError = NULL) : verifies that the field value is not less than the size specified in the parameter
     * @param type $taille
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkMinchar($taille, $msgError = NULL) {
        $this->attributSelected();
        if (!is_numeric($taille))
            $this->setError("Error parameter type minChar", "This parameter must be numeric!");
        if (strlen($this->value) < $taille)
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * This field must have at least $taille character(s)." : $msgError);

        return $this;
    }

    /**
     * checkEmail($msgError = NULL) : Check the validity of an email
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkEmail($msgError = NULL) {
        $this->attributSelected();
        if (!preg_match('|^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$|i', $this->value))
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * This email isn't valide." : $msgError);

        return $this;
    }

    /**
     * checkInt($msgError = NULL) : Check the validity of an integer
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkInt($msgError = NULL) {
        $this->attributSelected();
        if (!preg_match('/^\d+$/', $this->value))
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? ' * This field must not be integer.' : $msgError);
        return $this;
    }

    /**
     * checkDouble($msgError = NULL) : Check the validity of a double
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkDouble($msgError = NULL) {
        $this->attributSelected();
        if (!is_numeric($this->value))
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? ' * This field must not be double.' : $msgError);
        return $this;
    }

    /**
     * getValue() : Recover the value of  selected field
     * @return value of 
     */
    public function getValue($field) {
        if (key_exists('data', $this->post))
            if (key_exists($field, $this->post['data'])) {
                return $this->post['data'][$field];
            }
            else
                $this->setError("Form Field getValue('$field')", "This field <i>$field</i> is not exist in this form !");
    }

    /**
     * getDataForm($field) : Recovery values ​​of form fields posted.
     * @param type $field
     * @return type
     */
    public function getDataForm($field) {
        if ($this->isPosted()) {
            if (key_exists('data', $this->post)) {
                if (key_exists($field, $this->post['data']))
                    return $this->post['data'][$field];
            }
            else
                $this->setError("Form Data", "Any Data Field not posted !");
        }
        else
            $this->setError("Form", "This form has not yet been posted !");
    }

    /**
     * setFile($field) : specified the file field
     * @param type $field
     * @return type
     */
    public function setFile($field) {
        if ($this->isPosted()) {
            if (key_exists($field, $this->file))
                $this->attribut = $field;
            else
                $this->setError("Form File Field", "This file field <i>$field</i> is not exist in this form !");
        }
        else
            $this->setError("Form", "This form has not yet been posted !");

        return $this;
    }

    /**
     * checkExtension($extensions, $msgError = NULL) : check the corresponding extension file extensions specified in the table extensions
     * @param type $extensions
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkExtension($extensions, $msgError = NULL) {
        $ext = strrchr($this->file[$this->attribut]['name'], '.');
        if (!is_array($extensions))
            $this->setError('Form File Extension checking', 'The extension must be array()');
        if (!in_array($ext, $extensions))
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * This field must have a file with the extension " . implode(' ', $extensions) : $msgError);

        return $this;
    }

    /**
     * checkSize($maxSize, $msgError = NULL) : check that the file size does not exceed the maximum size
     * @param type $maxSize
     * @param type $msgError
     * @return \App\Config\FormHelper\Form
     */
    public function checkSize($maxSize, $msgError = NULL) {
        $size = filesize($this->file[$this->attribut]['tmp_name']);
        if (!preg_match('/^\d+$/', $maxSize))
            $this->setError('Form File Max size checking', 'The max size must be integer');
        if ($size > $maxSize)
            $this->setFormErrors($this->attribut, ($msgError == NULL) ? " * The file must not exceed $maxSize bytes(octets)" : $msgError);

        return $this;
    }

    /**
     * setUploadFolder($folder, $nameFile = NULL) : specify the folder(which will be created if it does not exist) in which the file will be loaded, with a possible reappointment
     * @param type $folder
     * @param type $nameFile
     */
    public function setUploadFolder($field, $folder, $nameFile = NULL) {
        if (key_exists($field, $this->file)) {
            if (!is_dir($folder))
                mkdir($folder, 0777, true) or $this->setFormErrors($field, " * Failure Creating Folder");
            $folder .= ($nameFile != NULL) ? $nameFile : $this->file[$field]['name'];
            move_uploaded_file($this->file[$field]['tmp_name'], $folder) or $this->setFormErrors($field, " * Failure Uploading");
        }else
            $this->setError("Form Field setUploadFolder('$field', 'Folder', 'Msg Error')", "This field <i>$field</i> is not exist in this form !");
    } 
    
    /**
     * getFileForm($field) : 
     * @param type $field
     * @return type
     */
    public function getFileForm($field) {
        if ($this->isPosted()) {
            if (key_exists($field, $this->file))
                return $this->file[$field];
            else
                $this->setError("Form File Field", "This file field <i>$field</i> is not exist in this form !");
        }
        else
            $this->setError("Form", "This form has not yet been posted !");
    }

    /**
     * isValid() : Check if the form is validated.
     * @return type Boolean : is valid "True" or not "False"
     */
    public function isValid() {
        if (isset($this->post['data']['butterfly_token']) && !empty($this->post['data']['butterfly_token']))
            return ($this->verifyFormToken() && empty($this->errors)) ? TRUE : FALSE;
        else
            $this->setError('Error token', 'breach of security token');
    }

    private function verifyFormToken() {
        $jetonSession = $_SESSION['butterfly_token'];
        $jetonForm = $this->post['data']['butterfly_token'];
        for ($i = 0; $i < strlen($jetonSession); $i++) {
            if ($jetonSession[$i] != $jetonForm[$i])
                return FALSE;
        }
        return TRUE;
    }

    /**
     * resetForm(): empty the contents of the form to a new use.
     */
    public function resetForm() {
        unset($this->post);
        unset($this->file);
        header("location: $this->action");
    }

}

