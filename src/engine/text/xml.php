<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

class XML
{
    public static function encode($name, $data)
    {
        $document = new \DOMDocument();
 
        self::append($document, $document, $name, $data);
 
        return $document->saveXML();
    }

    private static function append($document, $parent, $name, $data)
    {
        // Append value node
        if (!is_array($data)) {
            if ($name !== null) {
                $element = $document->createElement($name);
            } else {
                $element = $parent;
            }

            $element->appendChild($document->createTextNode(mb_convert_encoding((string)$data, 'UTF-8')));

            if ($name !== null) {
                $parent->appendChild($element);
            }
        }

        // Append repeated node
        elseif (array_reduce(array_keys($data), function (&$result, $item) {
            return $result === $item ? $item + 1 : null;
        }, 0) === count($data)) {
            foreach ($data as $item) {
                if ($name !== null) {
                    $element = $document->createElement($name);
                } else {
                    $element = $parent;
                }

                self::append($document, $element, null, $item);

                if ($name !== null) {
                    $parent->appendChild($element);
                }
            }
        }

        // Append child nodes
        else {
            if ($name !== null) {
                $element = $document->createElement($name);
            } else {
                $element = $parent;
            }

            foreach ($data as $key => $value) {
                if (mb_substr($key, 0, 1) === '@') {
                    $element->setAttribute(mb_substr($key, 1), (string)$value);
                } else {
                    self::append($document, $element, is_numeric($key) ? '_' . $key : $key, $value);
                }
            }

            if ($name !== null) {
                $parent->appendChild($element);
            }
        }
    }
}
