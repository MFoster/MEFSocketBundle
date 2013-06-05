<?php

namespace MEF\SocketBundle\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Encodes JSON data
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class PlainEncoder implements EncoderInterface, DecoderInterface
{
    const FORMAT = 'plain';

    /**
     * @var JsonEncode
     */
    protected $encodingImpl;

    /**
     * @var JsonDecode
     */
    protected $decodingImpl;

    public function __construct($encodingImpl = null, JsonDecode $decodingImpl = null)
    {
       
    }

    /**
     * Returns the last encoding error (if any)
     *
     * @return integer
     */
    public function getLastEncodingError()
    {
        return false;
    }

    /**
     * Returns the last decoding error (if any)
     *
     * @return integer
     */
    public function getLastDecodingError()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = array())
    {
        return "$data";
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = array())
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}