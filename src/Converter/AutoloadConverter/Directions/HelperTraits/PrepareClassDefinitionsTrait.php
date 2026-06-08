<?php
/**
 * @author Selcuk Mart
 * 8.08.2022
 * 15:41
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\Helpers\ClassOperations\FileObject;

trait PrepareClassDefinitionsTrait
{
    private mixed $class_to;
    private array
        $file_list_ingredients_classes,
        $class_lists_ingredients_file_dirs,
        $content_list = [],
        $exceptions = [
        'as',
        'bool',
        'int',
        'string',
        'object',
        'void',
        'mixed',
    ];

    /**
     * @var string
     * @author Selcuk Mart
     * 18.07.2022
     * 14:01
     */
    private string $filename_slug = '';
    /**
     * @var string
     * @author Selcuk Mart
     * 18.07.2022
     * 14:02
     */
    private string $filename = '';
    /**
     * @var array
     * @author Selcuk Mart
     * 29.07.2022
     * 12:35
     */
    private array $clean_class_lists;
    /**
     * @var mixed
     * @author Selcuk Mart
     * 1.08.2022
     * 15:29
     */
    private mixed $dir;

    /**
     * @var bool
     * @author Selcuk Mart
     * 1.08.2022
     * 15:53
     */
    private bool $detect_unused_classes = false;
    /**
     * @var false|string
     * @author Selcuk Mart
     * 1.08.2022
     * 16:26
     */
    private string|false $content;
    /**
     * @var string
     * @author Selcuk Mart
     * 2.08.2022
     * 15:30
     */
    private string $root_file_or_dir = '';
    /**
     * @var mixed|null
     * @author Selcuk Mart
     * 2.08.2022
     * 16:06
     */
    private mixed $class_information = [];
    /**
     * @var string
     * @author Selcuk Mart
     * 2.08.2022
     * 17:02
     */
    private string $file_type = '';
    /**
     * @var false|string
     * @author Selcuk Mart
     * 3.08.2022
     * 10:26
     */
    private string|false $original_content;
    /**
     * @var array|string|string[]
     * @author Selcuk Mart
     * 5.08.2022
     * 14:22
     */
    private string|array $base_file_or_dir;
    /**
     * @var FileObject
     * @author Selcuk Mart
     * 5.08.2022
     * 15:03
     */
    private FileObject $FileObject;

    /**
     * @return false|string
     */
    public function getContent(): bool|string
    {
        return $this->content;
    }

    private function getClassName()
    {
        if ($this->isClass()) {
            return $this->class_information['name'];
        }
    }

}