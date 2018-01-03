<?php
declare(strict_types=1);

namespace tkanstantsin\fileupload\formatter;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine;

/**
 * Class ImageProcessor
 */
class Image extends File
{
    /**
     * Like background `cover` in css.
     */
    public const RESIZE_OUTBOUND = \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
    /**
     * Like background `contain` in css.
     */
    public const RESIZE_INSET = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
    /**
     * Means that image may be smaller than defined in config, never bigger.
     */
    public const RESIZE_INSET_KEEP_RATIO = 'inset_keep_ratio';

    public const DEFAULT_EXTENSION = 'jpg';

    /**
     * @var int
     */
    public $width;
    /**
     * @var int
     */
    public $height;

    /**
     * Used when defined only height as upper limit for width
     * @todo: implement in Image::createBox() method.
     * @var int
     */
    public $widthUpperLimit;
    /**
     * Used when defined only widith as upper limit for height
     * @var int
     */
    public $heightUpperLimit;

    /**
     * @var string
     */
    public $mode = self::RESIZE_INSET;
    /**
     * Whether image must keep aspect ration when used inset mote.
     * Means that image would may be smaller than smaller
     * @var bool
     */
    public $keepRatio = true;

    /**
     * @var Imagine
     */
    protected $imagine;

    /**
     * @inheritdoc
     * @throws \UnexpectedValueException
     * @throws \Imagine\Exception\InvalidArgumentException
     * @throws \Imagine\Exception\RuntimeException
     */
    protected function getContentInternal()
    {
        $this->imagine = new Imagine();
        $image = $this->imagine->read(parent::getContentInternal());
        $image = $this->format($image);

        return $image->get($this->file->getExtension() ?? self::DEFAULT_EXTENSION);
    }

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws \UnexpectedValueException
     * @throws \Imagine\Exception\RuntimeException
     * @throws \Imagine\Exception\InvalidArgumentException
     */
    protected function format(ImageInterface $image): ImageInterface
    {
        $box = $this->createBox($image);
        if ($box === null) {
            return $image;
        }

        switch ($this->mode) {
            case self::RESIZE_OUTBOUND:
            case self::RESIZE_INSET:
                return $image->thumbnail($box, $this->mode);
            case self::RESIZE_INSET_KEEP_RATIO:
                // TODO: implement new resize mode.
                throw new \UnexpectedValueException(sprintf('Resize mode `%s` not supported yet', $this->mode));
            default:
                throw new \UnexpectedValueException(sprintf('Image resize mode `%s` not defined', $this->mode));
        }
    }

    /**
     * @param ImageInterface $image
     * @return BoxInterface|null
     * @throws \Imagine\Exception\InvalidArgumentException
     */
    protected function createBox(ImageInterface $image): ?BoxInterface
    {
        if ($this->width !== null
            && $this->height !== null
        ) {
            return new Box($this->width, $this->height);
        }

        $actualBox = $image->getSize();
        if ($this->width !== null) {
            return $actualBox->widen($this->width);
        }
        if ($this->height !== null
        ) {
            return $actualBox->heighten($this->height);
        }

        // both are null
        return null;
    }
}