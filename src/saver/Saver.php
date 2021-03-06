<?php
declare(strict_types=1);

namespace tkanstantsin\fileupload\saver;

use League\Flysystem\FilesystemInterface;
use tkanstantsin\fileupload\config\InvalidConfigException;
use tkanstantsin\fileupload\formatter\File;
use tkanstantsin\fileupload\model\IFile;

/**
 * Class Saver allows store processed files.
 * E.g. store uploaded files or cache cropped/prepared images.
 *
 * @todo add option to force rewriting existed file.
 */
class Saver
{
    /**
     * @var IFile
     */
    public $file;
    /**
     * Filesystem where file will be stored
     * @var FilesystemInterface
     */
    public $filesystem;
    /**
     * File path in $filesystem
     * @var string
     */
    public $path;

    /**
     * Saver constructor.
     * @param IFile $file
     * @param FilesystemInterface $filesystem
     * @param string $path
     * @throws InvalidConfigException
     */
    public function __construct(IFile $file, FilesystemInterface $filesystem, string $path)
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
        $this->path = $path;

        if ($this->path === '') {
            throw new InvalidConfigException('Saver path must be not empty');
        }
    }

    /**
     * Copies, processes and saves file in $filesystem
     * @param \tkanstantsin\fileupload\formatter\File $formatter
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function save(File $formatter): bool
    {
        $isCached = $this->isSaved();
        $isEmpty = $this->isEmpty();
        if ($isCached && !$isEmpty) {
            $formatter->triggerEvent(File::EVENT_CACHED);

            return true;
        }
        if ($isEmpty) {
            $formatter->triggerEvent(File::EVENT_EMPTY);

            return false;
        }

        // checks if path is writable
        // create new empty file or override existed one
        // also caches empty result for non-formatted files
        $this->filesystem->put($this->path, null);

        $content = $formatter->getContent();
        if ($content === false) {
            $formatter->triggerEvent(File::EVENT_NOT_FOUND);

            return false;
        }
        if ($content === '') {
            $formatter->triggerEvent(File::EVENT_ERROR);

            return false;
        }

        $saved = $this->write($content);

        $formatter->triggerEvent($saved ? File::EVENT_CACHED : File::EVENT_ERROR);

        return $saved;
    }

    /**
     * @return bool
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function isEmpty(): bool
    {
        return $this->filesystem->has($this->path)
            && $this->filesystem->getSize($this->path) === 0;
    }

    /**
     * Checks if file is already in $path.
     * @return bool
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function isSaved(): bool
    {
        return $this->filesystem->has($this->path)
            && $this->filesystem->getTimestamp($this->path) > $this->file->getUpdatedAt();
    }

    /**
     * Saves file into $filesystem
     * @param $content
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function write($content): bool
    {
        if ($content === false || $content === null) {
            return false;
        }

        return \is_resource($content)
            ? $this->filesystem->putStream($this->path, $content)
            : $this->filesystem->put($this->path, $content);
    }
}