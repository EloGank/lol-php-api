<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Formatter;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ResultFormatter
{
    /**
     * @param array $results
     *
     * @return array
     */
    public function format(array $results)
    {
        return $this->toArray($results['data']->getData()->body);
    }

    /**
     * @param $object
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function toArray($object)
    {
        if ($object instanceof \SabreAMF_TypedObject) {
            $result = $object->getAMFData();

            foreach ($result as &$data) {
                if (is_object($data)) {
                    $data = $this->toArray($data);
                }
            }

            return $result;
        }
        elseif ($object instanceof \SabreAMF_ArrayCollection) {
            $array = [];
            foreach ($object as $key => $data) {
                if (is_object($data)) {
                    $array[$key] = $this->toArray($data);
                }
                else {
                    $array[$key] = $data;
                }
            }

            return $array;
        }
        elseif ($object instanceof \DateTime) {
            return $object->format('Y-m-d H:i:s');
        }
        elseif ($object instanceof \stdClass) {
            $array = get_object_vars($object);
            foreach ($array as &$data) {
                if (is_object($data)) {
                    $data = $this->toArray($data);
                }
            }

            return $array;
        }

        throw new \RuntimeException('Unknown object class "' . get_class($object) . '"');
    }
}