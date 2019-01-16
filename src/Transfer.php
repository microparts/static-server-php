<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-16
 */

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
    private $content = '';

    /**
     * @var mixed
     */
    private $data;

    /**
     * Transfer constructor.
     *
     * @param string $filename
     * @param string $realpath
     * @param string $extension
     * @param string $content
     */
    public function __construct(string $filename, string $realpath, string $extension, string $content = '')
    {
        $this->filename = $filename;
        $this->extension = $extension;
        $this->realpath = $realpath;
        $this->content = $content;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @param string $realpath
     */
    public function setRealpath(string $realpath): void
    {
        $this->realpath = $realpath;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
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
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
