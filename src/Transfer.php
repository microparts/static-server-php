<?php declare(strict_types=1);

namespace StaticServer;

final class Transfer
{
    /**
     * @var string
     */
    private $filename = '';

    /**
     * @var string
     */
    private $extension = '';

    /**
     * @var string
     */
    private $realpath = '';

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $content = '';

    /**
     * Transfer constructor.
     *
     * @param string $filename
     * @param string $realpath
     * @param string $extension
     * @param string $location
     * @param string $content
     */
    public function __construct(string $filename, string $realpath, string $extension, string $location, string $content = '')
    {
        $this->filename  = $filename;
        $this->extension = $extension;
        $this->realpath  = $realpath;
        $this->location  = $location;
        $this->content   = $content;
    }

    /**
     * @param string $filename
     *
     * @return void
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @param string $content
     *
     * @return void
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @param string $extension
     *
     * @return void
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @param string $realpath
     *
     * @return void
     */
    public function setRealpath(string $realpath): void
    {
        $this->realpath = $realpath;
    }

    /**
     * @param string $location
     *
     * @return void
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string
     *
     * @return void
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getRealpath(): string
    {
        return $this->realpath;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }
}
