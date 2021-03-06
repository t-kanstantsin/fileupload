<?php
declare(strict_types=1);

namespace tkanstantsin\fileupload\formatter\adapter;

use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Image\Point;
use tkanstantsin\fileupload\config\InvalidConfigException;
use tkanstantsin\fileupload\model\IFile;

/**
 * Class BackgroundFixer replaces transparent background in PNG image that
 * would be converted in JPG
 */
class BackgroundFixer extends AbstractImageAdapter
{
    public const DEFAULT_EXTENSION = 'jpg';

    /**
     * Used for jpg images which may be png originally and have transparency.
     * @var string
     */
    public $transparentBackground = 'ffffff';

    /**
     * @inheritdoc
     * @throws \Imagine\Exception\RuntimeException
     * @throws \tkanstantsin\fileupload\config\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->transparentBackground === null) {
            throw new InvalidConfigException('Background color not defined');
        }
    }

    /**
     * Applies filters or something to content and return it
     *
     * @param IFile $file
     * @param       $content
     *
     * @return mixed
     * @throws \Imagine\Exception\OutOfBoundsException
     * @throws \UnexpectedValueException
     * @throws \Imagine\Exception\InvalidArgumentException
     * @throws \Imagine\Exception\RuntimeException
     * @throws \ImageOptimizer\Exception\Exception
     */
    public function exec(IFile $file, $content)
    {
        $image = $this->imagine->load($content);

        if (!\in_array(mb_strtolower($file->getExtension() ?? self::DEFAULT_EXTENSION), ['jpg', 'jpeg'], true)) {
            return $image;
        }

        $palette = new RGBPalette();
        $backgroundColor = $palette->color($this->transparentBackground, 100);
        $background = $this->imagine->create($image->getSize(), $backgroundColor);

        return $background->paste($image, new Point(0, 0));
    }
}