<?php
/**
 * @author Selcuk Mart
 * 5.08.2022
 * 14:54
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\FileContentAnalysis;
use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

class FileObject
{

    private static $instance = [];

    private string
        $content,
        $root_file;

    private string|false $original_content;

    private ?array $class_information;

    public function __construct(private readonly string $base_file)
    {
        $this->root_file = AutoloadConverterBuilder::getInstance()->getToFolder() . $this->base_file;

    }

    /**
     * @return array
     */
    public static function getInstance(string $base_file): self
    {
        if (empty(self::$instance[$base_file])) {
            self::$instance[$base_file] = new self($base_file);
        }
        return self::$instance[$base_file];
    }


    /**
     * @return false|string
     */
    public function getOriginalContent(): bool|string
    {
        if (!empty($this->original_content)) {
            return $this->original_content;
        }
        if (!is_file($this->root_file)) {
            return '';
        }
        $this->original_content = file_get_contents($this->root_file);
        return $this->original_content;
    }

    private function cleanComments($content): string
    {
        $replaced_content = str_replace(["\'", '\"'], '', $content);
        return preg_replace([RegexDefinitions::COMMENTS_REGEX, RegexDefinitions::CONTENT_REGEX], '', $replaced_content);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        if (!empty($this->content)) {
            return $this->content;
        }

        if (!is_file($this->root_file)) {
            return '';
        }

        $this->original_content = file_get_contents($this->root_file);
        $this->content = $this->cleanComments($this->original_content);
        $this->original_content = '';
        return $this->content;
    }

    /**
     * @return string
     */
    public function getBaseFile(): string
    {
        return $this->base_file;
    }


    public function getClassInformation(): ?array
    {
        if (empty($this->class_information)) {
            $file_analyse = FileContentAnalysis::getInstance($this);
            $this->class_information = $file_analyse->getClassInformation();
        }
        return $this->class_information;
    }

    public function writeOriginalContent(): void
    {
        if (!empty($this->original_content)) {
            file_put_contents($this->root_file, $this->original_content);
        }
    }

    /**
     * @param false|string $original_content
     */
    public function setOriginalContent(bool|string $original_content): self
    {
        $this->original_content = $original_content;
        return $this;
    }
}