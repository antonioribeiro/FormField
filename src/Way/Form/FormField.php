<?php namespace Way\Form;

use Form, Config;

class FormField {

    /**
     * Instance
     *
     * @var Way\Form\FormField
     */
    protected static $instance;
    
    protected $translator;
    
    public function __construct()
    {
        $this->translator = \config::$app['translator'];
    }
    
    /**
     * Make the form field
     *
     * @param string $name
     * @param array $args
     */
    public function make($name, array $args)
    {
        $type = array_get($args, 'type') ?: $this->guessInputType($name);
        
        if($type == 'checkbox')
        {
            $wrapper = Config::get('form::wrapper');
            $wrapperClass = Config::get('form::wrapperClass');
            
            $label = array_get($args, 'label');
            is_null($label) and $label = $this->prettifyFieldName($name);
            return '<'.$wrapper.' class="'.$wrapperClass.' group-' . $name . ' checkbox"><label>'.$this->createInput('checkbox', $args, $name).' '.$label.'</label></'.$wrapper.'>';
        }
        
        $wrapper = $this->createWrapper($name);
        $field = $this->createField($name, $args);

        return str_replace('{{FIELD}}', $field, $wrapper);
    }

    /**
     * Prepare the wrapping container for
     * each field.
     */
    protected function createWrapper($name)
    {
        $wrapper = Config::get('form::wrapper');
        $wrapperClass = Config::get('form::wrapperClass');

        return '<' . $wrapper . ' class="' . $wrapperClass . ' group-' . $name . '">{{FIELD}}</' . $wrapper . '>';
    }

    /**
     * Create the form field
     *
     * @param string $name
     * @param array $args
     */
    protected function createField($name, $args)
    {
        // If the user specifies an input type, we'll just use that.
        // Otherwise, we'll take a best guess approach.
        $type = array_get($args, 'type') ?: $this->guessInputType($name);

        // We'll default to Bootstrap-friendly input class names
        $args = array_merge(['class' => Config::get('form::inputClass')], $args);

        $field = $this->createLabel($args, $name);

        unset($args['label']);
        
        return $field .= $this->createInput($type, $args, $name);
    }

    /**
     * Handle of creation of the label
     *
     * @param array $args
     * @param string $name
     */
    protected function createLabel($args, $name)
    {
        $label = array_get($args, 'label');

        // If no label was provided, let's do our best to construct
        // a label from the method name.
        is_null($label) and $label = $this->prettifyFieldName($name) . ' ';

        return $label ? Form::label($name, $label, array('class' => 'control-label')) : '';
    }

    /**
     * Manage creation of input
     *
     * @param string $type
     * @param array $args
     * @param string $name
     */
    protected function createInput($type, $args, $name)
    {
        switch($type)
        {
            case 'password':
                return Form::password($name, $args);
            case 'select':
                $options = (isset($args['options']) ? $args['options'] : []);
                unset($args['options']);
                return Form::select($name, $options, (isset($args['default']) ? $args['default'] : null), $args);
            case 'checkbox':
                return Form::checkbox($name, 'true', (isset($args['checked']) ? $args['checked'] : null), $args);
            case 'date':
                $args['class'] .= ' cms-input-date';
                return Form::text($name, (isset($args['default']) ? $args['default'] : null), $args);
            default:
                return Form::text($name, (isset($args['default']) ? $args['default'] : null), $args);
        }
    }

    /**
     * Provide a best guess for what the
     * input type should be.
     *
     * @param string $name
     */
    protected function guessInputType($name)
    {
        return array_get(Config::get('form::commonInputsLookup'), $name) ?: 'text';
    }

    /**
     * Clean up the field name for the label
     *
     * @param string $name
     */
    protected function prettifyFieldName($name)
    {
        if($this->translator->has('validation.attributes.'.$name))
        {
            return ucfirst($this->translator->get('validation.attributes.'.$name));
        }
        
        return ucwords(preg_replace('/(?<=\w)(?=[A-Z])/', " $1", $name));
    }

    /**
     * Handle dynamic method calls
     *
     * @param string $name
     * @param array $args
     */
    public static function __callStatic($name, $args)
    {
        $args = empty($args) ? [] : $args[0];

        $instance = static::$instance;
        if ( ! $instance) $instance = static::$instance = new static;

        return $instance->make($name, $args);
    }

}
