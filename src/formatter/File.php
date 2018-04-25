<?php
declare(strict_types=1);

namespace tkanstantsin\fileupload\formatter;

use League\Flysystem\FilesystemInterface;
use tkanstantsin\fileupload\config\InvalidConfigException;
use tkanstantsin\fileupload\formatter\adapter\IFormatAdapter;
use tkanstantsin\fileupload\model\BaseObject;
use tkanstantsin\fileupload\model\Container;
use tkanstantsin\fileupload\model\ICacheStateful;
use tkanstantsin\fileupload\model\IFile;

/**
 * Class FileProcessor
 * TODO: create callbacks or interfaces for getting customized file name or
 * filepath
 */
class File extends BaseObject
{
    /**
     * Additional dynamic config for processor class.
     * @var array
     */
    public $config = [];
    /**
     * @var IFormatAdapter[]|array
     */
    public $formatAdapterArray = [];

    /**
     * @see Factory::DEFAULT_FORMATTER_ARRAY
     * @example file, _normal, _product_preview
     * @var string
     */
    public $name;
    /**
     * Path to original file in contentFS
     * @var string
     */
    public $path;
    /**
     * ```
     * <?php function (IFile $file, bool $cached) {} ?>
     * ```
     * @var callable|null
     */
    public $afterCacheCallback;

    /**
     * @var IFile
     */
    protected $file;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * FileProcessor constructor.
     * @param IFile $file
     * @param FilesystemInterface $filesystem
     * @param array $config
     * @throws \tkanstantsin\fileupload\config\InvalidConfigException
     * @throws \ReflectionException
     */
    public function __construct(IFile $file, FilesystemInterface $filesystem, array $config = [])
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
        parent::__construct($config);
    }

    /**
     * Initialize app
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public function init(): void
    {
        parent::init();

        if ($this->name === null || !\is_string($this->name) || $this->name === '') {
            throw new InvalidConfigException('Formatter name must be defined');
        }
        if ($this->path === null) {
            throw new InvalidConfigException('File path property must be defined and be not empty');
        }
        foreach ($this->formatAdapterArray as $i => $formatAdapter) {
            $this->formatAdapterArray[$i] = Container::createObject($formatAdapter);

            if (!($this->formatAdapterArray[$i] instanceof IFormatAdapter)) {
                throw new InvalidConfigException(sprintf('Format adapter must be instance of %s.', IFormatAdapter::class));
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return resource|string|bool
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function getContent()
    {
        if (!$this->filesystem->has($this->path)) {
            return false;
        }

        $content = $this->getContentInternal();
        foreach ($this->formatAdapterArray as $formatAdapter) {
            $content = $formatAdapter->exec($this->file, $content);
        }

        return $content;
    }

    /**
     * Call user function after saving cached file
     * @param bool $cached
     */
    public function afterCacheCallback(bool $cached): void
    {
        if (!($this->file instanceof ICacheStateful)) {
            return;
        }
        if (!\is_callable($this->afterCacheCallback)) {
            return;
        }

        \call_user_func($this->afterCacheCallback, $this->file, $cached);
    }

    /**
     * @return resource|bool
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function getContentInternal()
    {
        return $this->filesystem->readStream($this->path);
    }
}