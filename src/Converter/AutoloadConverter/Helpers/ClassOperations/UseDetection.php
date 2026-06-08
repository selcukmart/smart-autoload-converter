<?php
/**
 * @author Selcuk Mart
 * 5.08.2022
 * 09:57
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\Helpers\RegexDefinitions;

class UseDetection
{
    private static array $instance = [];
    private array $classnames = [];

    public function __construct(
        private readonly string $classname,
        private readonly string $file_path,
        private readonly string $content,
        private readonly bool   $is_class
    )
    {
    }

    /**
     * @return array
     */
    public static function getInstance(string $classname, string $file_path, string $content, bool $is_class): self
    {
        if (empty(self::$instance[$file_path . $classname])) {
            self::$instance[$file_path . $classname] = new self($classname, $file_path, $content, $is_class);
        }
        return self::$instance[$file_path . $classname];
    }

    public function run()
    {
        if (!isset($this->classnames[$this->file_path . $this->classname])) {
            $classname = '';
            if (!$this->is_class) {
                $classname = (new InvestigateMoreSameClassname(FileObject::getInstance($this->file_path), $this->classname))->run();
            } else {

                $file_object = FileObject::getInstance($this->file_path);
                $classname = str_replace(['\\\\', '\\'], ['\\', '\\\\'], $this->classname);
                if (preg_match('@^class\s+' . $classname . '@i', $file_object->getContent())) {
                    $classname = $file_object->getClassInformation()['psr4'] ?? $this->classname;
//                    if($this->classname === 'viewController'){
//                        dump($file_object->getClassInformation($file_object->getClassInformation()));
//                    }
                }
            }
            if (empty($classname)) {
                $regex = str_replace(['{{CLASS}}', '_'], ['\\' . $this->classname, '\_'], RegexDefinitions::USE_REGEX);
                preg_match($regex, $this->content, $matches);
                $this->classnames[$this->file_path . $this->classname] = (isset($matches[1]) ? $matches[1] . '\\' . $this->classname : $this->classname);
            } else {
                $this->classnames[$this->file_path . $this->classname] = $classname;
            }

        }
        return $this->classnames[$this->file_path . $this->classname];
    }

}