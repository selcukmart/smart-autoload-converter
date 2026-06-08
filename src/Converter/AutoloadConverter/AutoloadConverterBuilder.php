<?php
/**
 * @author Selcuk Mart
 * 18.07.2022
 * 15:19
 */

namespace App\Converter;

use App\Converter\BuilderTraits\BuilderCommandsTraits;
use App\Converter\BuilderTraits\BuilderDirectionsTrait;
use App\Converter\Helpers\CopyFolderToSelectedFolder;
use App\GlobalTraits\ErrorMessagesWithResultTrait;
use App\Helper\ServiceHelper;
use function App\Helper\c;


class AutoloadConverterBuilder
{
    private static $instance = null;
    use
        ErrorMessagesWithResultTrait,
        BuilderCommandsTraits,
        BuilderDirectionsTrait;

    private string $temporary_copy_dir = '/../copy-of-project-auto-converter';
    private array
        $all_classes_in_project = [],
        $all_contents = [],
        $only_used_classes_except_itself_in_project = [],
        $class_lists_ingredients_file_dirs = [],
        $classname_count = [],
        $file_list_ingredients_classes = [];
    private string
        $zip_filename = '',
        $project_directory = '';

    private mixed
        $class_change_dirs,
        $to_be_moved;

    private string|array $relative_project_directory;

    private array
        $ignored_files,
        $ignored_dirs,
        $class_to;

    private string
        $to_folder,
        $from_folder;

    private array $main_library_areas;

    private mixed $change_dirnames_n_filenames_in_dirs;

    private array
        $class_libraries = [],
        $change_includes_to,
        $re_create_paths,
        $clear_class_includes_requires,
        $change_file_contents,
        $change_lib_dir;

    public function __construct(private readonly array $settings)
    {
        $this->iniSet();
        $this->project_directory = $this->settings['project_directory'];
        $project_directory = (ServiceHelper::getKernel())->getProjectDir();
        $this->relative_project_directory = str_replace($project_directory, '', $this->settings['project_directory']);
        $this->class_change_dirs = $this->settings['class_change_dirs'];
        $this->to_be_moved = $this->settings['to_be_moved'] ?? [];
        $this->ignored_files = $this->settings['ignore']['files'] ?? [];
        $this->ignored_dirs = $this->settings['ignore']['dirs'] ?? [];
        $this->class_to = $this->settings['class_to'] ?? [];
        $this->from_folder = $this->getProjectDirectory();
        $this->to_folder = $project_directory . $this->getTemporaryCopyDir();

        $this->main_library_areas = $this->settings['main_library_areas'] ?? [];
        $this->change_dirnames_n_filenames_in_dirs = $this->settings['change_dirnames_n_filenames_in_dirs'] ?? [];
        $this->change_lib_dir = $this->settings['change_lib_dir'] ?? [];
        $this->change_file_contents = $this->settings['change_file_contents'] ?? [];
        $this->clear_class_includes_requires = $this->settings['clear_class_includes_requires'] ?? [];
        $this->change_includes_to = $this->settings['change_includes_to'] ?? [];
        $this->re_create_paths = $this->settings['re_create_paths'] ?? [];
        $force = !is_null(ServiceHelper::getRequest()->get('make-changes-in-files'))
            || !is_null(ServiceHelper::getRequest()->get('copy_from_original'))
            || !is_null(ServiceHelper::getRequest()->get('create-files'));

        (new CopyFolderToSelectedFolder($this->from_folder, $this->to_folder, force: $force))->copyFolder();
    }

    /**
     * @return mixed
     */
    public function getReCreatePaths(): array
    {
        return $this->re_create_paths;
    }

    /**
     * @return mixed
     */
    public function getChangeIncludesTo(): mixed
    {
        return $this->change_includes_to;
    }

    /**
     * @return array|mixed
     */
    public function getClearClassIncludesRequires(): mixed
    {
        return $this->clear_class_includes_requires;
    }

    /**
     * @return array|mixed
     */
    public function getChangeFileContents(): mixed
    {
        return $this->change_file_contents;
    }

    /**
     * @return array|mixed
     */
    public function getChangeLibDir(): mixed
    {
        return $this->change_lib_dir;
    }

    /**
     * @return array|mixed
     */
    public function getChangeDirnamesNFilenamesInDirs(): mixed
    {
        return $this->change_dirnames_n_filenames_in_dirs;
    }

    /**
     * @return array
     */
    public function getMainLibraryAreas(): array
    {
        return $this->main_library_areas;
    }

    private function iniSet()
    {
        ini_set('memory_limit', '-1');
    }

    /**
     * @return string
     */
    public function getToFolder(): string
    {
        return $this->to_folder;
    }

    public function getToTempFolder(): string
    {
        return $this->to_folder . '2';
    }

    /**
     * @return string
     */
    public function getFromFolder(): string
    {
        return $this->from_folder;
    }

    /**
     * @return string
     */
    public function getTemporaryCopyDir(): string
    {
        return $this->temporary_copy_dir;
    }

    /**
     * @return array|mixed
     */
    public function getClassTo(): array
    {
        return $this->class_to;
    }

    /**
     * @return mixed
     */
    public static function getInstance(array $settings = null): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($settings);
        }
        return self::$instance;
    }


    /**
     * @return array
     */
    public function getClassnameCount(): array
    {
        return $this->classname_count;
    }

    public function getClassnameCountCoupled(): array
    {
        $coupled = [];
        foreach ($this->classname_count as $classname => $count) {
            if ($count['count'] > 1) {
                $coupled[$classname] = $count;
            }

        }
        return $coupled;
    }

    /**
     * @return array
     */
    public function getIgnoredDirs(): array
    {
        return $this->ignored_dirs;
    }

    /**
     * @return array
     */
    public function getIgnoredFiles(): array
    {
        return $this->ignored_files;
    }

    public function setAllContents(array $all_contents): self
    {
        $this->all_contents = $all_contents;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllContents(): array
    {
        return $this->all_contents;
    }

    /**
     * @param array $class_lists_ingredients_file_dirs
     */
    public function setClassListsIngredientsFileDirs(array $class_lists_ingredients_file_dirs): self
    {
        $this->class_lists_ingredients_file_dirs = $class_lists_ingredients_file_dirs;
        return $this;
    }

    /**
     * @param array $file_list_ingredients_classes
     */
    public function setFileListIngredientsClasses(array $file_list_ingredients_classes): self
    {
        $this->file_list_ingredients_classes = $file_list_ingredients_classes;
        return $this;
    }

    /**
     * @return array
     */
    public function getClassListsIngredientsFileDirs(): array
    {
        return $this->class_lists_ingredients_file_dirs;
    }

    /**
     * @return array
     */
    public function getFileListIngredientsClasses(): array
    {
        return $this->file_list_ingredients_classes;
    }

    /**
     * @return array|string
     */
    public function getRelativeProjectDirectory(): array|string
    {
        return $this->relative_project_directory;
    }

    /**
     * @return array|string
     */
    public function getRelativeProjectDir(): array|string
    {
        return $this->relative_project_directory;
    }

    /**
     * @return mixed
     */
    public function getToBeMoved(): mixed
    {
        return $this->to_be_moved;
    }

    /**
     * @return mixed
     */
    public function getClassChangeDirs(): mixed
    {
        return $this->class_change_dirs;
    }

    public function getProjectDir(): string
    {
        return $this->project_directory;
    }

    /**
     * @return string
     */
    public function getProjectDirectory(): string
    {
        return $this->project_directory;
    }

    public function getSettings(string $setting): mixed
    {
        return $this->settings[$setting];
    }

    public function setFileList(array $file_list): self
    {
        $this->file_list = $file_list;
        return $this;
    }


    public function addLibrary(string $library, string $old_library, $detected_dir): void
    {
        if (
            str_contains($library, '$')
            || str_contains($library, '=')
            || str_contains($library, ')')
        ) {
            return;
        }
        if (str_contains($old_library, '/Zend/Zend')) {
            return;
        }
        $old_library = str_replace('//', '/', $detected_dir . '/' . str_replace('\\', '/', $old_library));
        if (!isset($this->class_libraries[$old_library])) {
            $this->class_libraries[$old_library] = $library;
        }
    }

    public function getClassLibraries(): array
    {
        return $this->class_libraries;
    }

    public function setToAllClassesListInProject(array|null $class_information): self
    {
        if (is_null($class_information)) {
            return $this;
        }
        if (!isset($this->classname_count[$class_information['psr4']])) {
            $this->classname_count[$class_information['psr4']]['count'] = 1;
        } else {
            $this->classname_count[$class_information['psr4']]['count']++;
        }
        $this->classname_count[$class_information['psr4']][] = $class_information;
        $this->all_classes_in_project[$class_information['psr4']] = $class_information;
        return $this;
    }

    /**
     * @param array $only_used_classes_except_itself_in_project
     */
    public function setOnlyUsedClassesExceptItselfInProject(string $classname): self
    {
        if (in_array($classname, $this->only_used_classes_except_itself_in_project, true)) {
            return $this;
        }
        $this->only_used_classes_except_itself_in_project[] = $classname;
        return $this;
    }

    /**
     * @return array
     */
    public function getOnlyUsedClassesExceptItselfInProject(): array
    {
        return $this->only_used_classes_except_itself_in_project;
    }


    /**
     * @return array
     */
    public function getAllclassesInProject(): array
    {
        return $this->all_classes_in_project;
    }

}