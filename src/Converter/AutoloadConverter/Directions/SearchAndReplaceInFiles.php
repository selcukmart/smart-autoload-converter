<?php
/**
 * @author Selcuk Mart
 * 18.07.2022
 * 10:10
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\HelperTraits\SearchAndReplace\ChangeClassesAddNamespacesTrait;
use App\Converter\Directions\HelperTraits\SearchAndReplace\ChangeInFilesTrait;
use App\Converter\Helpers\ClassOperations\ClassInformationObject;
use App\Converter\Helpers\RegexDefinitions;
use App\Helper\ServiceHelper;
use RuntimeException;
use function App\Helper\c;
use function App\Helper\namespaceFix;
use function App\Helper\namespaceFixFromClassnameString;


class SearchAndReplaceInFiles extends AbstractDirections
{
    use
        ChangeInFilesTrait,
        ChangeClassesAddNamespacesTrait;

    private static array $contents = [];

    private array
        $list,
        $changed_files = [];

    private string
        $relative_project_dir = '',
        $file = '';

    public function replaceClass(): void
    {
        $this->changeOverClassLists();
        $this->changeClassesAddNamespaces();
        if ($this->hasOutputRequest()) {
            c($this->changed_files);
        }
    }

    public static function detectClass($classname)
    {

        if (isset(AutoloadConverterBuilder::getInstance()->getAllclassesInProject()[$classname])) {
            return AutoloadConverterBuilder::getInstance()->getAllclassesInProject()[$classname];
        }

        foreach (AutoloadConverterBuilder::getInstance()->getAllclassesInProject() as $class_information) {
            if ($class_information['name'] === $classname) {
                return $class_information;
            }
        }

        if (class_exists($classname) || interface_exists($classname)) {
            ClassInformationObject::getInstance($classname)
                ->setName($classname)
                ->setOldClassname($classname)
                ->setNewClassname($classname)
                ->setPsr4(namespaceFixFromClassnameString($classname))
                ->setPsr4Filename($classname . '.php')
                ->setType('class')
                ->setNamespaced(false)
                ->setNamespace('')
                ->setNewNamespace('')
                ->setLibrary('')
                ->setToBeMoved(false)
                ->setToBeMovedDir('');
            return ClassInformationObject::getInstance($classname)->toArray();

        }

        return false;

    }

    private function makeDir(string $file): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
    }

    private function getContent(string $file): string|false
    {
        $root_file_path = AutoloadConverterBuilder::getInstance()->getToFolder() . $file;
        if (!is_file($root_file_path)) {
            return false;
        }
        return file_get_contents($root_file_path);
    }

    private function hasUse(mixed $psr4_class_name, string $content): bool
    {
        $find = 'use ' . ltrim($psr4_class_name, '\\');
        return str_contains($content, $find);
    }

    /**
     * @param mixed $current_class_name
     * @param $regex
     * @param mixed $psr4_class_name
     * @return array
     * @author Selcuk Mart
     * 9.08.2022
     * 11:48
     */
    private function strReplace(mixed $current_class_name, $regex, mixed $psr4_class_name): array
    {
        $find = [];
        $replace = [];
        if (isset($regex['str_with_slash'])) {
            $find[] = $this->cleanDoubleBackSlash(str_replace('{{CLASS}}', $current_class_name, $regex['str_with_slash']));
            $replace[] = $this->cleanDoubleBackSlash(str_replace('{{PSR4}}', $psr4_class_name, $regex['replace_with_slash']));
        }

        $find[] = str_replace('{{CLASS}}', $current_class_name, $regex['str']);
        $replace_with_slash = false;
        $re_create_paths = AutoloadConverterBuilder::getInstance()->getReCreatePaths();
        if (isset($regex['str_with_slash'])) {
            foreach ($re_create_paths as $re_create_path) {
                if (preg_match('@^' . $re_create_path . '@', $this->file)) {
                    $replace[] = $this->cleanDoubleBackSlash(str_replace('{{PSR4}}', $psr4_class_name, $regex['replace_with_slash']));
                    $replace_with_slash = true;
                    break;
                }
            }
        }

        if (!$replace_with_slash) {
            $replace[] = $this->cleanDoubleBackSlash(str_replace('{{PSR4}}', $psr4_class_name, $regex['replace']));
        }


        return [$find, $replace];
    }


    private function strReplaceInContent(mixed $find, mixed $replace, string $content): string
    {
        return str_replace($find, $replace, $content);
    }

    private function unsetContent(mixed $file): void
    {
        if (isset(self::$contents[$file])) {
            unset(self::$contents[$file]);
        }
    }

    private function getRelativeProjectDir(): string|array
    {
        if (empty($this->relative_project_dir)) {
            $this->relative_project_dir = AutoloadConverterBuilder::getInstance()->getRelativeProjectDir();
        }
        return $this->relative_project_dir;
    }

    private function setContentToFile(int|string $file, mixed $content): void
    {
        $file = $this->strReplaceInContent($this->getRelativeProjectDir(), '', $file);
        $file = AutoloadConverterBuilder::getInstance()->getToFolder() . $file;
        $this->makeDir($file);
        $this->setToChangedFilesList($file);
        file_put_contents($file, $content);
        $this->unsetContent($file);
    }


    private function setToChangedFilesList(string $file): void
    {
        if (!in_array($file, $this->changed_files, true)) {
            $this->changed_files[] = $file;
        }
    }

    private function hideClassname(bool|string $content): array
    {
        preg_match(RegexDefinitions::CLASS_REGEX, $content, $matches);
        if (isset($matches[1])) {
            $class_name = $matches[1];
            $content = str_replace('class ' . $class_name, '_XXXYYYZZZ_', $content);
        }
        return [$matches, $content];
    }

    private function showClassname(mixed $matches, string $content): string
    {
        if (isset($matches[1])) {
            $content = str_replace('_XXXYYYZZZ_', 'class ' . $matches[1], $content);
        }
        return $content;
    }

    /**
     * @return bool
     * @author Selcuk Mart
     * 6.09.2022
     * 14:32
     */
    private function hasOutputRequest(): bool
    {
        return !is_null(ServiceHelper::getRequest()->get('show-changed-file-list'));
    }

    /**
     * @param mixed $psr4_class_name
     * @return array|mixed|string|string[]
     * @author Selcuk Mart
     * 6.09.2022
     * 14:44
     */
    private function cleanDoubleBackSlash(mixed $psr4_class_name): mixed
    {
        return str_replace('\\\\', '\\', $psr4_class_name);
    }

}