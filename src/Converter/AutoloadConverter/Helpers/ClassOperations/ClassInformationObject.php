<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 15:32
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\AutoloadConverterBuilder;

class ClassInformationObject
{
    private static array $instance = [];

    private string
        $namespace = '',
        $new_namespace = '',
        $library = '',
        $psr4 = '',
        $psr4_filename = '',
        $old_classname = '',
        $new_classname = '',
        $type = '',
        $to_be_moved_dir = '',
        $name = '',
        $new_file_path;

    private bool
        $to_be_moved = false,
        $namespaced = false;

    private array
        $class_information = [];
    /**
     * @var string
     * @author Selcuk Mart
     * 11.08.2022
     * 10:24
     */
    private string $old_library = '';

    public function __construct(private readonly string $file_path)
    {
    }

    public function toArray(): array
    {
        $this->class_information[$this->file_path] = [
            'name' => $this->name,
            'old_classname' => $this->old_classname,
            'new_classname' => $this->new_classname,
            'file_path' => $this->file_path,
            'new_file_path' => $this->determineNewFilePath(),
            'is_change_file_n_path_necessary' => $this->new_file_path !== $this->file_path,
            'psr4' => $this->psr4,
            'psr4_filename' => $this->psr4_filename,
            'type' => $this->type,
            'namespaced' => $this->namespaced,
            'namespace' => $this->namespace,
            'new_namespace' => $this->new_namespace,
            'library' => $this->library,
            'old_library' => $this->old_library,
            'to_be_moved' => $this->to_be_moved,
            'to_be_moved_dir' => $this->to_be_moved_dir,
        ];
        return $this->class_information[$this->file_path];
    }


    public static function getInstance($file_path): self
    {
        if (!isset(self::$instance[$file_path])) {
            self::$instance[$file_path] = new self($file_path);
        }
        return self::$instance[$file_path];
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->$name);
    }

    public function isSet(): bool
    {
        return isset($this->class_information[$this->file_path]);
    }

    public function setClassname(string $classname): self
    {
        $this->classname = $classname;
        return $this;
    }

    public function setLibrary(string $library): self
    {
        $this->library = $library;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function setNewNamespace(string $new_namespace): self
    {
        $this->new_namespace = $new_namespace;
        return $this;
    }

    public function setOldClassname(string $old_classname): self
    {
        $this->old_classname = $old_classname;
        return $this;
    }

    public function setPsr4(string $psr4): self
    {
        $this->psr4 = $psr4;
        return $this;
    }

    public function setPsr4Filename(string $psr4_filename): self
    {
        $this->psr4_filename = $psr4_filename;
        return $this;
    }

    public function setToBeMoved(bool $to_be_moved): self
    {
        $this->to_be_moved = $to_be_moved;
        return $this;
    }

    public function setToBeMovedDir(string $to_be_moved_dir): self
    {
        $this->to_be_moved_dir = $to_be_moved_dir;
        return $this;
    }

    public function setNamespaced($namespaced): self
    {
        $this->namespaced = $namespaced;
        return $this;
    }

    public function setNewClassname($new_classname): self
    {
        $this->new_classname = $new_classname;
        return $this;
    }

    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     * @author Selcuk Mart
     * 10.08.2022
     * 18:18
     */
    private function determineNewFilePath(): string
    {
        if (!empty($this->new_file_path)) {
            return $this->new_file_path;
        }
        if(str_contains($this->file_path, '/legacy_api/module')){
            $this->new_file_path = $this->file_path;
            return $this->new_file_path;
        }
        if ($this->to_be_moved) {
            return $this->new_file_path = $this->to_be_moved_dir . DIRECTORY_SEPARATOR . $this->psr4_filename;
        }

        return $this->new_file_path = rtrim($this->detectRootLibrary(), '/') . '/' . str_replace('\\', '/', $this->determineNamespace()) . '/' . $this->new_classname . '.php';
    }

    private function detectRootLibrary(): string
    {
        $libraries = AutoloadConverterBuilder::getInstance()->getMainLibraryAreas();
        foreach ($libraries as $path => $namespace) {
            if (str_contains($this->file_path, $path)) {
                return str_replace($namespace, '', $path);
            }
        }
        return '';
    }

    /**
     * @return string
     * @author Selcuk Mart
     * 10.08.2022
     * 18:37
     */
    private function determineNamespace(): string
    {
        return empty($this->new_namespace) ? $this->namespace : $this->new_namespace;
    }

    public function setOldLibrary(string $old_library): self
    {
        $this->old_library = $old_library;
        return $this;
    }

}