<?php
/**
 * @author Selcuk Mart
 * 9.08.2022
 * 15:32
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\SearchAndReplaceInFiles;
use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

class ChangeClassnameAndAddNamespaceInContent
{
    private static array $instance;
    private array $class_regexs = [
        'class' => RegexDefinitions::CLASS_REGEX,
        'interface' => RegexDefinitions::INTERFACE_REGEX,
        'trait' => RegexDefinitions::TRAIT_REGEX,
        'abstract class' => RegexDefinitions::ABSTRACT_REGEX,
        'final class' => RegexDefinitions::FINAL_REGEX,
        'enum' => RegexDefinitions::ENUM_REGEX
    ];
    private bool $changed = false;

    public function __construct(private readonly string $file, private string $content)
    {
    }

    /**
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->changed;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public static function getInstance(string $file, string $content): self
    {
        if (!isset(self::$instance[$file])) {
            self::$instance[$file] = new self($file, $content);
        }
        self::$instance[$file]->setContent($content);
        return self::$instance[$file];
    }

    public function changeClassName(): string
    {

        if (!$this->isClass()) {
            return $this->content;
        }

        if ($this->isChanged()) {
            return $this->content;
        }


        $this->changeClassBaseInformationsInClassFile();

        return $this->content;
    }

    /**
     * @return bool
     * @author Selcuk Mart
     * 10.08.2022
     * 16:28
     */
    private function isClass(): bool
    {
        return isset(AutoloadConverterBuilder::getInstance()->getFileListIngredientsClasses()[$this->file]['type'])
            && AutoloadConverterBuilder::getInstance()->getFileListIngredientsClasses()[$this->file]['type'] === 'class';
    }

    /**
     * @param mixed $class_information
     * @author Selcuk Mart
     * 10.08.2022
     * 16:29
     */
    private function setNamespaceToClassFile(mixed $class_information): void
    {
        $namespace = empty($class_information['new_namespace']) ? $class_information['namespace'] : $class_information['new_namespace'];
        if (!empty($namespace)) {
            $this->content = preg_replace('@<\?php@i', '<?php ' . "\nnamespace " . $namespace . ';', $this->content, 1);
        }
    }

    private function changeClassnameInClassFile(mixed $class_name, string $prefix, $new_classname): void
    {
        $class_name = str_replace(['_'], ['\_'], $class_name);

        $this->content = preg_replace('@^' . $prefix . '\s+' . $class_name . '@mi', $prefix . ' ' . $new_classname, $this->content, 1);
    }

    private function changeClassBaseInformationsInClassFile(): void
    {
        foreach ($this->class_regexs as $prefix => $class_regex) {

            if (preg_match($class_regex, $this->content, $matches)) {
                [$class_name, $class_information] = $this->getClassInformation($matches[1]);
                if (!$class_information) {
                    continue;
                }
                $this->ChangeClassnameAndNamespaceInClassFile($class_name, $prefix, $class_information);
                break;
            }
        }
    }

    private function ChangeClassnameAndNamespaceInClassFile(mixed $class_name, string $prefix, mixed $class_information): void
    {
        $this->changeClassnameInClassFile($class_name, $prefix, $class_information['new_classname']);
        $this->setNamespaceToClassFile($class_information);
        $this->changed = true;
    }

    /**
     * @param $matches
     * @return array
     * @author Selcuk Mart
     * 10.08.2022
     * 16:51
     */
    private function getClassInformation($matches): array
    {
        $class_name = $matches;
        $class_information = SearchAndReplaceInFiles::detectClass($class_name);
        return [$class_name, $class_information];
    }
}