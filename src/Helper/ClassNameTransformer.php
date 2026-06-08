<?php
/**
 * @author selcukmart
 * 10.03.2022
 * 10:05
 */

namespace App\Helper;

class ClassNameTransformer
{
    private static array $instance = [];

    private array $covert_to = [
        'list' => 'List',
        'view' => 'View',
        'filter' => 'Filter',
        'link' => 'Link',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'add' => 'Add',
        'update' => 'Update',
        'create' => 'Create',
        'store' => 'Store',
        'destroy' => 'Destroy',
        'show' => 'Show',
        'index' => '',
        'missions' => 'Missions',
        'construct' => '',
        'Construct' => '',
        'Controller' => 'Controller',
        'controller' => 'Controller',
        'main' => 'Main',
        'Main' => 'Main',
        'connector' => 'Connector',
        'status' => 'Status',
        'sort' => 'Sort',
        'header' => 'Header',
        'footer' => 'Footer',
        'sidebar' => 'Sidebar',
        'content' => 'Content',
        'settings' => 'Settings',
        'setting' => 'Setting',
        'setting_group' => 'SettingGroup',
        'setting_group_item' => 'SettingGroupItem',
        'row' => 'Row',
        'column' => 'Column',
        'cell' => 'Cell',
        'options' => 'Options',
        'option' => 'Option',
        'tasks' => 'Tasks',
        'search' => 'Search',
        'model' => 'Model',
        'entity' => 'Entity',
        'table' => 'Table',
        'error' => 'Error',
        'input' => 'Input',
        'missionFilter' => 'MissionFilter',
        'mission' => 'Mission',
        'answer' => 'Answer',
        'answerFilter' => 'AnswerFilter',
        'answerView' => 'AnswerView',
        'timeview' => 'Timeview',
        'collector' => 'Collector',
        'group' => 'Group',
        'jobs' => 'Jobs',
        'job' => 'Job',
        'event' => 'Event',
        'File' => 'File',
        'file' => 'File',
        'Alarm' => 'Alarm',
        'Organizer' => 'Organizer',
        'String' => 'StringOperations',
        'STATIC' => 'Static',
        'Abstract' => 'Abstract',
        'Exception' => 'Exception',
        'Function' => 'Function',
        'Object' => '',
        'Class' => 'Class',
        'Interface' => 'Interface',
        'Trait' => 'Trait',
        'Namespace' => 'Namespace',
    ];
    private string
        $classname_with_namespace = '';
    /**
     * @var string
     * @author Selcuk Mart
     * 18.07.2022
     * 15:43
     */
    private string $classname = '';
    /**
     * @var string
     * @author Selcuk Mart
     * 18.07.2022
     * 16:43
     */
    private string $namespace = '';
    /**
     * @var string[]
     * @author Selcuk Mart
     * 1.08.2022
     * 17:19
     */
    private array $folder_n_filenames;
    /**
     * @var int
     * @author Selcuk Mart
     * 1.08.2022
     * 17:19
     */
    private int $total;
    /**
     * @var string
     * @author Selcuk Mart
     * 11.08.2022
     * 10:02
     */
    private string $old_state_namespace = '';

    public function __construct(private readonly string $str, private string $type = '', private readonly string $file_path = '')
    {
        if (empty($this->type)) {
            $this->type = 'class';
        }
    }

    public static function getInstance($str, string $type = '', string $file_path = ''): self
    {
        if (!isset(self::$instance[$str])) {
            self::$instance[$str] = new self($str, $type, $file_path);
        }
        return self::$instance[$str];
    }

    /**
     * @return string
     * @author Selcuk Mart
     * 18.07.2022
     * 15:43
     */
    public function prepareClassName(): ClassNameTransformer
    {
        if (!empty($this->classname_with_namespace)) {
            return $this;
        }
        $str = str_replace('_', '\\', $this->str);
        $this->folder_n_filenames = explode('\\', $str);
        $this->folder_n_filenames = namespaceFix($this->folder_n_filenames);
        $this->total = count($this->folder_n_filenames);
        $classname = trim($this->folder_n_filenames[$this->total - 1]);
        $this->determineClassName($classname);
        $total_folders = $this->total - 1;
        $this->old_state_namespace = '';
        foreach ($this->folder_n_filenames as $i => $iValue) {
            $old_state_value = $iValue;
            $iValue = $this->refactorClassname($i, $total_folders, $iValue);
            $iValue = ucfirst($iValue);
            if ($i === 0) {
                $this->classname_with_namespace = $iValue;
                $this->namespace = $iValue;
                $this->old_state_namespace .= $old_state_value;
            } else {
                $this->classname_with_namespace .= '\\' . $iValue;
                $this->old_state_namespace .= '\\' . $old_state_value;
                if ($i < $total_folders - 1) {
                    $this->namespace .= '\\' . $iValue;
                    $this->old_state_namespace .= '\\' . $old_state_value;
                }
            }
        }

//        if(str_contains($classname,'viewController')){
//            dump($this);
//        }
        return $this;
    }

    /**
     * @return string
     */
    public function getOldStateNamespace(): string
    {
        return $this->old_state_namespace;
    }

    /**
     * @return string
     */
    public function getClassnameWithNamespace(): string
    {
        return $this->classname_with_namespace;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getCurrentClass(): string
    {
        return $this->str;
    }

    /**
     * @param int|string $i
     * @param mixed $total_folders
     * @param string $iValue
     * @return string
     * @author Selcuk Mart
     * 1.08.2022
     * 17:14
     */
    private function refactorClassname(int|string $i, mixed $total_folders, string $iValue): string
    {
        if ($i === $total_folders) {
            $iValue = $this->classname;
        }
        return $iValue;
    }

    /**
     * @param string $classname
     * @param $folder_n_filenames
     * @author Selcuk Mart
     * 1.08.2022
     * 17:15
     */
    private function determineClassName(string $classname): void
    {

        $third_folder_keys = [
            'key',
            'Key',
            'type',
            'Type'
        ];
        $classname = trim($classname);
        if (in_array($classname, $third_folder_keys)) {
            $this->classname = $this->getClassNamePrefixForSpecial(3) . ucfirst($classname);
        } else {
            $third_and_two_folder_keys = [
                'view',
                'View',
                'Factory',
                'factory',
                'day',
                'month',
                'Controller',
                'controller',
                'list',
                'date',
                'viewController',
                'ViewController',
            ];

            if (in_array($classname, $third_and_two_folder_keys)) {
                $prefix = $this->getClassNamePrefixForSpecial(3, 2);
                //dump('$prefix', $prefix);
                if (str_contains($prefix, $classname)) {
                    $this->classname = $prefix;
                } else {
                    $this->classname = $prefix . ucfirst($classname);
                }
            } else {
                $this->classname = ucfirst($classname);
            }

        }

        $arr = $this->covert_to;
        if (isset($arr[$this->classname])) {
            $this->classname = $this->getClassNamePrefixForSpecial() . $arr[$this->classname];
            if (isset($arr[$this->classname])) {
                $prefix = $this->getClassNamePrefixForSpecial(3, 2);
                $this->classname = $prefix . $arr[$this->classname];
            }
        }

        $prefixed_types = [
            'interface',
            'trait',
        ];
        if (in_array($this->type, $prefixed_types)
            && !str_contains($this->classname, $this->type)
            && !str_contains($this->classname, ucfirst($this->type))) {
            $this->classname .= ucfirst($this->type);
        }

        $prefixed_types = [
            'abstract',
        ];
        if (in_array($this->type, $prefixed_types) && !str_contains($this->classname, 'Abstract')) {
            $this->classname = ucfirst($this->type) . $this->classname;
        }
    }

    private function getClassNamePrefixForSpecial(int $index = 2, int $folder_count = 1): string
    {
        $prefix = '';
        if ($this->total >= $index) {
            if ($folder_count === 1) {
                $prefix = $this->folder_n_filenames[$this->total - $index];
            } elseif ($folder_count === 2) {
                $prefix1 = $this->folder_n_filenames[$this->total - $index];
                $prefix2 = ucfirst($this->folder_n_filenames[$this->total - ($index - 1)]);
                if ($prefix1 !== $prefix2) {
                    $prefix = $prefix1 . $prefix2;
                } else {
                    $prefix = $prefix1;
                }

            }
        } elseif ($this->total >= ($index - 1)) {
            if ($folder_count === 1) {
                $prefix = $this->folder_n_filenames[$this->total - ($index - 1)] ?? $this->folder_n_filenames[$this->total - ($index - 2)];
            } elseif ($folder_count === 2) {
                $prefix1 = $this->folder_n_filenames[$this->total - ($index - 1)];
                $prefix2 = ucfirst($this->folder_n_filenames[$this->total - ($index - 2)]);
                if ($prefix1 !== $prefix2) {
                    $prefix = $prefix1 . $prefix2;
                } else {
                    $prefix = $prefix1;
                }
            }
        }
        $prefix = str_replace([
            'Interface',
            'Trait',
            'Abstract'
        ], '', $prefix);


        return ucfirst($prefix);
    }

}