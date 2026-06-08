<?php
/**
 * @author Selcuk Mart
 * 20.07.2022
 * 09:48
 */

namespace App\Converter\Helpers;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\ClassOperations\ClassInformationObject;
use App\Converter\Helpers\ClassOperations\FileObject;
use App\Helper\ClassNameTransformer;
use App\Helper\GeneralHelpers;
use function App\Helper\c;
use function App\Helper\namespaceFix;
use function App\Helper\namespaceFixFromClassnameString;

class FileContentAnalysis
{
    private static array
        $instance = [],
        $class_states = [];

    private string $psr4;

    private string|bool $content;

    private string $file_path;

    private bool $to_be_moved;

    private string
        $determined_namespace_from_to_be_moved,
        $to_be_moved_dir;

    private mixed $namespaced;

    private mixed $namespace;

    private mixed $old_classname;

    private array
        $psr4_arr,
        $old_state_psr4_arr;
    private string $old_library = '';
    private string $classnameAfterInvestigating = '';
    /**
     * @var array
     * @author Selcuk Mart
     * 24.08.2022
     * 16:30
     */
    private array $latest_arr = [];


    public function __construct(private readonly FileObject $fileObject)
    {
        $this->file_path = $this->fileObject->getBaseFile();
        $this->content = $this->fileObject->getContent();
    }


    public static function getInstance(FileObject $fileObject): self
    {
        $file_path = $fileObject->getBaseFile();
        if (!isset(self::$instance[$file_path])) {
            self::$instance[$file_path] = new self($fileObject);
        }
        return self::$instance[$file_path];
    }

    /**
     * @return bool
     */
    public function isClass(): bool
    {
        if (!isset(self::$class_states[$this->file_path])) {
            self::$class_states[$this->file_path] = $this->isFileClass();
        }
        return self::$class_states[$this->file_path];
    }

    private function isFileClass(): bool
    {
        $controls = [
            'class' => RegexDefinitions::CLASS_REGEX,
            'abstract' => RegexDefinitions::ABSTRACT_REGEX,
            'trait' => RegexDefinitions::TRAIT_REGEX,
            'final' => RegexDefinitions::FINAL_REGEX,
            'interface' => RegexDefinitions::INTERFACE_REGEX,
            'enum' => RegexDefinitions::ENUM_REGEX,
        ];
        $return = false;

        foreach ($controls as $type => $control) {
            if (preg_match($control, $this->content, $matches)) {
                $return = $this->prepareClassInformation($matches[1], $type);
                break;
            }
        }
        return $return;
    }


    public function getClassInformation(): array|null
    {
        if (!$this->isClass()) {
            return null;
        }
        if (!ClassInformationObject::getInstance($this->file_path)->isSet()) {
            $this->isFileClass();
        }
        return ClassInformationObject::getInstance($this->file_path)->toArray();
    }

    private function determineLibrary(): string
    {
        $total = count($this->psr4_arr);
        $this->old_library = $this->detectOldLibrary();
        if ($total > 2 || str_contains($this->psr4, 'Legacy\\')) {
            $library = $this->psr4_arr[0] . '\\' . $this->psr4_arr[1];

        } else {
            $library = $this->psr4_arr[0];
        }
        if ($this->necessaryRemovable()) {
            $detected_folder = '';
            foreach (AutoloadConverterBuilder::getInstance()->getChangeDirnamesNFilenamesInDirs() as $dir) {
                if (str_contains($this->file_path, $dir)) {
                    $detected_folder = $dir;
                    break;
                }
            }
            if (!empty($detected_folder)) {
                AutoloadConverterBuilder::getInstance()->addLibrary($library, $this->old_library, $detected_folder);
            }
        }
        return $library;
    }

    private function detectOldLibrary(): string
    {
        $libraries = AutoloadConverterBuilder::getInstance()->getMainLibraryAreas();
        foreach ($libraries as $path => $namespace) {
            if (str_contains($this->file_path, $path)) {
                $path = str_replace($path, '', $this->file_path);
                break;
            }
        }
        $path = str_replace('/', '\\', $path);
        $x = explode('\\', $path);
        $old_library = $x[0];
        if (isset($x[1])) {
            $old_library .= '\\' . $x[1];
        }
        return $old_library;
    }

    private function determineNamespace(): string
    {
        $namespace_arr = $this->psr4_arr;
        unset($namespace_arr[count($namespace_arr) - 1]);
        $namespace_arr = namespaceFix($namespace_arr);
        $new = implode('\\', $namespace_arr);
        if ($new !== $this->namespace) {
            return $new;
        }
        return '';
    }

    public function getClassName()
    {
        if (!$this->isClass()) {
            return null;
        }
        if (!ClassInformationObject::getInstance($this->file_path)->isSet()) {
            $this->isFileClass();
        }
        return ClassInformationObject::getInstance($this->file_path)->toArray()['name'];
    }

    private function determineToBeMoved(): void
    {
        $return = false;
        $this->determined_namespace_from_to_be_moved = '';
        $this->to_be_moved_dir = '';
        $to_be_moved = AutoloadConverterBuilder::getInstance()->getToBeMoved();
        if ($to_be_moved && !empty($to_be_moved['from'])) {
            foreach ($to_be_moved['from'] as $dir) {
                if (
                    preg_match('/' . str_replace('/', '\/', $dir) . '/', $this->file_path)
                    && !preg_match('/' . str_replace('/', '\/', $to_be_moved['to']) . '/', $this->file_path)
                ) {
                    $explode = explode($dir, $this->file_path);
                    $explode2 = explode('/', $explode[1]);

                    $file = $explode2[count($explode2) - 1];
                    unset($explode2[count($explode2) - 1]);
                    if (count($explode2) <= 1) {
                        $explode2 = [str_replace('.php', '', $file)];
                    }
                    $latest_arr = [];
                    foreach ($explode2 as $folder) {
                        $latest_arr[] = ucfirst($folder);
                    }

                    $this->determined_namespace_from_to_be_moved = ltrim(implode('\\', $latest_arr), '\\');
                    $this->to_be_moved_dir = str_replace('//', '/', $to_be_moved['to'] . '/' . implode('/', $latest_arr));
                    if (str_contains($this->file_path, 'Objects/List.php')) {
                        //(new GeneralHelpers($this->determined_dir_from_to_be_moved))->dump();
                        $this->to_be_moved_dir = preg_replace('@/\w+\.php@mi', '', $this->to_be_moved_dir);
                    }
                    $return = true;
                    break;
                }
            }
        }
        $this->to_be_moved = $return;
    }

    private function determinePSR4($matches, string $type): string
    {
        if (!empty($this->determined_namespace_from_to_be_moved)) {
            $psr4 = $this->determined_namespace_from_to_be_moved . '\\' . ClassNameTransformer::getInstance($matches, $type, $this->file_path)
                    ->prepareClassName()
                    ->getClassnameWithNamespace();
        } else {
            $psr4 = ($this->namespaced) ? $this->namespace . '\\' . $matches : ClassNameTransformer::getInstance($matches, $type, $this->file_path)
                ->prepareClassName()
                ->getClassnameWithNamespace();
        }

        $this->psr4_arr = explode('\\', $psr4);
        $this->psr4_arr = namespaceFix($this->psr4_arr);
        if (!$this->namespaced || !empty($this->determined_namespace_from_to_be_moved)) {
            $old_psr4 = ClassNameTransformer::getInstance($matches, $type, $this->file_path)
                ->prepareClassName()
                ->getOldStateNamespace();
            $this->old_state_psr4_arr = explode('\\', $old_psr4);
        } else {
            $this->old_state_psr4_arr = $this->psr4_arr;
        }
        return $this->fixToNewNamespace($psr4);
    }

    private function namespaceDetermine(): array
    {
        $namespaced = false;
        if (!empty($this->determined_namespace_from_to_be_moved)) {
            return [$namespaced, $this->determined_namespace_from_to_be_moved];
        }
        $namespace = '';
        if (preg_match(RegexDefinitions::NAMESPACE_REGEX, $this->content, $namespace_matches)) {
            $namespaced = true;
            $namespace = $namespace_matches[1];
        }
        return [$namespaced, $namespace];
    }

    private function fixToNewNamespace(string $psr4): string
    {
        if (count($this->psr4_arr) === 1) {
            $to_be_moved = AutoloadConverterBuilder::getInstance()->getToBeMoved();
            if ($this->isToBeMoved($to_be_moved)) {
                $psr4 = $this->determineNamespaceCore($to_be_moved['to'], $psr4);
                $this->to_be_moved = true;
                $this->to_be_moved_dir = $to_be_moved['to'] . '/' . ltrim(implode('/', $this->latest_arr), '/');
            } else {
                $libraries = AutoloadConverterBuilder::getInstance()->getMainLibraryAreas();
                $root_dir = '';
                foreach ($libraries as $path => $namespace) {
                    if (str_contains($this->file_path, $path)) {
                        $root_dir = $path;
                        break;
                    }
                }
                $psr4 = $this->determineNamespaceCore($root_dir, $psr4);
            }
        }

        return $psr4;
    }

    private function getClassnameAfterInvestigating(mixed $classname, $type): mixed
    {
        $this->old_classname = '';
        if (isset(AutoloadConverterBuilder::getInstance()->getClassTo()[$this->file_path]['new'])) {
            $classname = AutoloadConverterBuilder::getInstance()->getClassTo()[$this->file_path]['new'];
            $this->old_classname = AutoloadConverterBuilder::getInstance()->getClassTo()[$this->file_path]['old'];
            $this->psr4 = $this->determinePSR4($classname, $type);

        } else {
            $this->psr4 = $this->determinePSR4($classname, $type);
            $classname = $this->namespaced ? $this->psr4 : $classname;
        }

        return $classname;
    }

    private function getNewClassname(): string
    {
        return $this->psr4_arr[count($this->psr4_arr) - 1];
    }

    private function prepareClassInformation($classname, mixed $type): bool
    {
        $this->determineToBeMoved();
        [$this->namespaced, $this->namespace] = $this->namespaceDetermine();
        $this->classnameAfterInvestigating = $this->getClassnameAfterInvestigating($classname, $type);

        $new_classname = $this->getNewClassname();

        ClassInformationObject::getInstance($this->file_path)
            ->setName($this->classnameAfterInvestigating)
            ->setOldClassname($this->old_classname)
            ->setNewClassname($new_classname)
            ->setPsr4(namespaceFixFromClassnameString($this->psr4))
            ->setPsr4Filename($new_classname . '.php')
            ->setType($type)
            ->setNamespaced($this->namespaced)
            ->setNamespace($this->namespace)
            ->setNewNamespace($this->determineNamespace())
            ->setLibrary($this->determineLibrary())
            ->setOldLibrary($this->old_library)
            ->setToBeMoved($this->to_be_moved)
            ->setToBeMovedDir($this->to_be_moved_dir);
        return true;
    }

    /**
     * @return bool
     * @author Selcuk Mart
     * 16.08.2022
     * 09:46
     */
    private function necessaryRemovable(): bool
    {
        return !$this->to_be_moved && !$this->namespaced;
    }

    /**
     * @param mixed $to_be_moved
     * @return bool
     * @author Selcuk Mart
     * 24.08.2022
     * 16:09
     */
    private function isToBeMoved(mixed $to_be_moved): bool
    {
        $first_check = !empty($to_be_moved['to'])
            && !str_contains($this->file_path, $to_be_moved['to']);
        if ($first_check) {
            foreach ($to_be_moved['from'] as $item) {
                if (str_contains($this->file_path, $item)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function determineNamespaceCore($to, string $psr4): string
    {
        $psr4 = namespaceFixFromClassnameString($psr4);
        $explode = explode($to, $this->file_path);
        $folder_name = str_replace('.php', '', ltrim($explode[1], '/'));
        $x = explode('/', $folder_name);
        if (count($x) > 1) {
            unset($x[count($x) - 1]);
        }
        $this->latest_arr = [];
        foreach ($x as $item) {
            $this->latest_arr[] = ucfirst($item);
        }
        $this->latest_arr = namespaceFix($this->latest_arr);
        $namespace = ltrim(implode('\\', $this->latest_arr), '\\');

        $psr4 = $namespace . '\\' . $psr4;
        $this->psr4_arr = explode('\\', $psr4);
        return $psr4;
    }

}