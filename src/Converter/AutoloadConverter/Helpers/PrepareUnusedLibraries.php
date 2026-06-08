<?php
/**
 * @author Selcuk Mart
 * 2.08.2022
 * 10:55
 */

namespace App\Converter\Helpers;


class PrepareUnusedLibraries
{

    private array $used_libraries = [];
    private array $unused_libraries = [];
    private array $unknown_libraries = [];

    public function __construct(private readonly array $only_used_classes_except_itself_in_project, private readonly array $all_classes_in_project)
    {
    }

    public function getUnusedLibraries(): array
    {

        foreach ($this->only_used_classes_except_itself_in_project as $only_used_classname) {
            if (!isset($this->all_classes_in_project[$only_used_classname])) {
                if (class_exists($only_used_classname) || is_numeric($only_used_classname) || !preg_match(RegexDefinitions::CLASS_NAME_REGEX, $only_used_classname)) {
                    continue;
                }
                $this->unknown_libraries[] = $only_used_classname;
            } else {
                $this->used_libraries[] = $this->all_classes_in_project[$only_used_classname]['library'];
            }
        }

        foreach ($this->all_classes_in_project as $library_information) {
            if ($library_information['type'] === 'class' && !in_array($library_information['library'], $this->used_libraries, true)) {
                $this->unused_libraries[] = $library_information;
            }
        }

        return $this->unused_libraries;
    }

    /**
     * @return array
     */
    public function getUnknownLibraries(): array
    {
        return $this->unknown_libraries;
    }


    /**
     * @return array
     */
    public function getUsedLibraries(): array
    {
        return $this->used_libraries;
    }
}