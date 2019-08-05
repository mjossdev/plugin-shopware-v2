<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative
 *
 * Generic helper functions used in narrative context
 */
class Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative
{
    protected $customContextValues = ["block", "position", "class", "rewrite", "main_template"];

    public function getFormatOfElement($element)
    {
        $type='';
        $parameters = $element['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'format') {
                $type  = reset($parameter['values']);
                break;
            }
        }

        return $type;
    }

    public function getContextValues($parameters)
    {
        $values = array();
        foreach ($parameters as $parameter)
        {
            $paramName = $parameter['name'];
            if(in_array($paramName, $this->customContextValues)){
                $assignValues = $this->getDecodedValues($parameter['values']);
                $assignValues = sizeof($assignValues) == 1 ? reset($assignValues) : $assignValues;
                $values['narrative_block_' . $paramName] = $assignValues;
            }
        }

        return $values;
    }

    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function getDecodedValues($values)
    {
        if(!is_array($values))
        {
            if($this->isJson($values)) {
                return json_decode($values, true);
            }

            return $values;
        }

        foreach ($values as $i => $value) {
            if($this->isJson($value)) {
                $values[$i] = json_decode($value, true);
            }
        }

        return $values;
    }

    public function getVariantAndIndex($visualElement, $additionalParameter=array())
    {
        $variantIndex = 0;
        $parameters = isset($visualElement['visualElement']) ? $visualElement['visualElement'] : $visualElement['parameters'];

        $index = isset($additionalParameter['list_index']) ? $additionalParameter['list_index'] : 0;
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'variant') {
                $variantIndex = reset($parameter['values']);
            } else if($parameter['name'] == 'index') {
                $index = reset($parameter['values']);
            }
        }

        return array($variantIndex, $index);
    }

    public function getVariant($visualElement)
    {
        $variantIndex = 0;
        $parameters = isset($visualElement['visualElement']) ? $visualElement['visualElement']['parameters'] : $visualElement['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'variant') {
                $variantIndex = reset($parameter['values']);
                break;
            }
        }

        return $variantIndex;
    }

}
