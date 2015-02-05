<?php

namespace petitparser;

/**
 * This class implements an immutable string buffer.
 *
 * Internally, the string is stored in UTF-32 encoding so that offsets
 * can accessed randomly without any substantial overhead - this comes
 * at the cost of (4x) memory overhead, but internally, the string data
 * is a reference, not a copy, which should make it more memory efficient
 * overall, e.g. compared to a substrings taken from a string scalar.
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
     * @var int buffer start in characters (not in bytes; inclusive)
     */
    protected $_start;

    /**
     * @var int buffer end in characters (not in bytes; exclusive)
     */
    protected $_end;

    /**
     * @param string $string reference to UTF-32 encoded string
     * @param string $encoding source character encoding
     * @param int $start buffer start in characters (inclusive)
     * @param int $end buffer end in characters (exclusive)
     */
    protected function __construct($string, $encoding, $start, $end)
    {
        $this->_string = $string;
        $this->_encoding = $encoding;
        $this->_start = $start;
        $this->_end = $end;
    }

    /**
     * Create a new Buffer instance from a string in a specified character encoding.
     *
     * If no encoding is specified, PHP's internal encoding is used by default, e.g.
     * the encoding returned by {@link mb_convert_encoding()}
     *
     * @param string $string source string in the specified encoding
     * @param string|null $encoding source character encoding (or NULL to use PHP's internal encoding)
     *
     * @return Buffer
     *
     * @see fromISO()
     * @see fromUTF8()
     */
    public static function create($string, $encoding = null)
    {
        if ($encoding === null) {
            $encoding = mb_internal_encoding();
        }

        $string = mb_convert_encoding($string, 'UTF-32', $encoding);

        return new Buffer(
            $string,
            $encoding,
            0,
            mb_strlen($string, 'UTF-32')
        );
    }

    /**
     * @param string $string latin-1 8-bit string
     *
     * @return Buffer
     *
     * @see create()
     * @see fromUTF8()
     */
    public static function fromISO($string)
    {
        return self::create($string, 'ISO-8859-1');
    }

    /**
     * @param string $string UTF-8 encoded string
     *
     * @return Buffer
     *
     * @see create()
     * @see fromISO()
     */
    public static function fromUTF8($string)
    {
        return self::create($string, 'UTF-8');
    }

    /**
     * @param int $offset base-0 character offset
     *
     * @return string variable-length string in the original source encoding
     */
    public function charAt($offset)
    {
        return mb_convert_encoding(
            mb_substr($this->_string, 4 * ($offset + $this->_start), 4, '8bit'),
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
        $bytes = mb_substr($this->_string, 4 * ($offset + $this->_start), 4, '8bit');

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
     * @param int $start start in characters (inclusive)
     * @param int $end end in characters (exclusive)
     *
     * @return Buffer
     */
    public function slice($start, $end = null)
    {
        return new Buffer(
            $this->_string,
            $this->_encoding,
            $this->_start + $start,
            $end === null
                ? $this->_end
                : $this->_start + $end
        );
    }

    /**
     * @see $length
     * @ignore
     */
    protected function get_length()
    {
        return $this->_end - $this->_start;
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
        return mb_convert_encoding(
            mb_substr($this->_string, 4 * $this->_start, 4 * $this->get_length(), '8bit'),
            $this->_encoding,
            'UTF-32'
        );
    }
}
