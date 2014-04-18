<?php

namespace EloGank\Api\Component\Exception;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class JsonException extends \Exception
{
    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode(array(
            'success' => false,
            'error'   => array(
                'caused_by' => get_called_class(),
                'message'   => $this->getMessage()
            )
        ));
    }
} 