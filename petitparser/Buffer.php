<?php

namespace petitparser;

/**
 * This class implements a string buffer.
 *
 * Internally, the string is stored in UTF-32 encoding so that offsets
 * can accessed randomly without any substantial overhead - this comes
 * at the cost of memory overhead.
 *
 * @property-read int $length number of characters in buffer
 * @property-read string $encoding source string character encoding
 * @property-read string $string string buffer in original source encoding
 */
class Buffer extends Accessors
{
    /**
     * @var string UTF-32 encoded string buffer
     */
    protected $_string;

    /**
     * @var string source character encoding
     */
    protected $_encoding;

    /**
     * @var int buffer length in characters (not in bytes)
     */
    protected $_length;

    /**
     * @param string $string
     * @param string $encoding source character encoding
     */
    protected function __construct($string, $encoding)
    {
        $this->_string = mb_convert_encoding($string, 'UTF-32', $encoding);
        $this->_length = (int) mb_strlen($this->_string, '8bit') / 4;
        $this->_encoding = $encoding;
    }

    /**
     * @param string $string latin-1 8-bit string
     *
     * @return Buffer
     */
    public static function fromISO($string)
    {
        return new Buffer($string, 'ISO-8859-1');
    }

    /**
     * @param string $string UTF-8 encoded string
     *
     * @return Buffer
     */
    public static function fromUTF8($string)
    {
        return new Buffer($string, 'UTF-8');
    }

    /**
     * @param int $offset base-0 character offset
     *
     * @return string variable-length string in the original source encoding
     */
    public function charAt($offset)
    {
        return mb_convert_encoding(
            mb_substr($this->_string, 4 * $offset, 4, '8bit'),
            $this->_encoding,
            'UTF-32'
        );
    }

    /**
     * @param int $offset base-0 character offset
     *
     * @return int unsigned long (always 32 bit, machine byte order)
     */
    public function charCodeAt($offset)
    {
        $bytes = mb_substr($this->_string, 4 * $offset, 4, '8bit');

        // http://dk1.php.net/manual/en/function.unpack.php#106041

        if (PHP_INT_SIZE <= 4) {
            list(, $h, $l) = unpack('n*', $bytes);
            return ($l + ($h * 0x010000));
        } else {
            list(, $int) = unpack('N', $bytes);
            return $int;
        }
    }

    /**
     * @see $length
     * @ignore
     */
    protected function get_length()
    {
        return $this->_length;
    }

    /**
     * @see $encoding
     * @ignore
     */
    protected function get_encoding()
    {
        return $this->_encoding;
    }

    /**
     * @see $string
     * @ignore
     */
    protected function get_string()
    {
        return mb_convert_encoding($this->_string, $this->_encoding, 'UTF-32');
    }
}
