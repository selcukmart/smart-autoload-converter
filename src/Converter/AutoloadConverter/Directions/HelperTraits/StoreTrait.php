<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 14:55
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\AutoloadConverterBuilder;
use function App\Helper\c;

trait StoreTrait
{
    private function addAsKeyClassAsValueFile(mixed $class, string $type, mixed $file): void
    {
        /**
         * add as key=class[], value=file
         */
        if (!isset($this->class_lists_ingredients_file_dirs[$class][$type])) {
            $this->class_lists_ingredients_file_dirs[$class][$type] = [];
        }

        if (!in_array($file, $this->class_lists_ingredients_file_dirs[$class][$type], true)) {
            $this->class_lists_ingredients_file_dirs[$class][$type][] = $file;
        }
    }

    private function addToOnlyUsedClassnameList(mixed $classname): void
    {
        $classname_exceptions = [
            'self',
            'static',
            'parent',
            'DOMDocument',
            'DOMNode',
            'DOMNodeList',
            'DOMElement',
            'array',
            'stdClass',
            'object',
            'int',
            'float',
            'string',
            'bool',
            'ReflectionClass',
            'ReflectionMethod',
            'ZendApi_Queue',
            'IteratorIterator',
            'window',
            'event',
            'PEAR',
            'PEAR_Error',
            'PEAR_Exception',
            'PEAR_ErrorStack',
            'eventName',
            'eventArgs',
            'eventSender',
            'eventHandler',
            'eventHandlerList',
            'eventHandlerListIterator',
            'eventHandlerListIterator_',
            'APCIterator',
            'APC_Cache_Iterator',
            'APC_Cache_Iterator_',
            'APC_Cache_Iterator_',
            'APC_Cache_Iterator_',
            'Memcache',
            'Memcache_',
            'Redis',
            'classname'
        ];

        if (in_array($classname, $classname_exceptions, true)) {
            return;
        }

        $this_classname = $this->getClassName();
        if (!empty($this_classname) && $this_classname !== $classname) {
            AutoloadConverterBuilder::getInstance()->setOnlyUsedClassesExceptItselfInProject($classname);
        }
    }

    private function addAsKeyFileAsValueClass(mixed $file, string $type): void
    {
        /**
         * add as key=file[], value=class
         */
        $this->prepareFileListsArray($file, $type);
        $this->addToFileListsDeterminedClasses($type);
    }

    private function addToClassLists($matched_classes, string $type): void
    {
        if (!$this->isEmptyMatched($matched_classes)) {
            $file = $this->base_file_or_dir;

            foreach ($this->clean_class_lists as $class) {
                if (empty($class)) {
                    continue;
                }
                if (in_array($class, ['self', 'static', 'parent', 'classname'])) {
                    continue;
                }

                $class = $this->useToClassname($class);
                if (isset($this->class_to[$file]['new'])) {
                    $class = $this->class_to[$file]['new'];
                }
//                if($type === 'EXTENDS' &&str_contains($file, 'Zend/Uri/Http.php')) {
//                    c($class);
//                    exit;
//                }
                $this->addAsKeyClassAsValueFile($class, $type, $file);
                $this->addToOnlyUsedClassnameList($class);

            }
            $this->addAsKeyFileAsValueClass($file, $type);
        }

    }

    private function addToFileListsDeterminedClasses(string $type): void
    {
        foreach ($this->clean_class_lists as $clean_class) {
            if (!in_array($clean_class, $this->file_list_ingredients_classes[$this->base_file_or_dir][$type], true)) {
                if (in_array($clean_class, ['self', 'static', 'parent'])) {
                    continue;
                }
                $clean_class = $this->useToClassname($clean_class);
                $this->file_list_ingredients_classes[$this->base_file_or_dir][$type][] = $clean_class;
            }
        }

    }

    /**
     * @param mixed $file
     * @param string $type
     * @author Selcuk Mart
     * 18.08.2022
     * 11:40
     */
    private function prepareFileListsArray(mixed $file, string $type = null): void
    {
        if (!isset($this->file_list_ingredients_classes[$file])) {
            $this->file_list_ingredients_classes[$file] = [];
        }
        if (!is_null($type) && !isset($this->file_list_ingredients_classes[$file][$type])) {
            $this->file_list_ingredients_classes[$file][$type] = [];
        }
    }
}