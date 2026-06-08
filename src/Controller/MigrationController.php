<?php

namespace App\Controller;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\ReadFolder;
use App\CustomLogger\CustomLogger;
use App\Helper\ServiceHelper;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use function App\Helper\c;

class MigrationController extends AbstractController
{
    /**
     * @throws JsonException
     */
    #[Route('/migration', name: 'app_migration')]
    public function index(KernelInterface $appKernel, Request $request, CustomLogger $logger): JsonResponse
    {
        ServiceHelper::setInstance($appKernel, 'kernel');
        ServiceHelper::setInstance($request, 'request');
        ServiceHelper::setInstance($logger, 'logger');
        $project_directory = $appKernel->getProjectDir();
        $settings = [
            'project_directory' => realpath($project_directory . '/../legacy-project'),
            'class_change_dirs' => [
                '/legacy-project',
                '/scripts',
                '/Zend',
                '/legacy_api',
            ],
            'library_dirs' => [
                '/',
            ],
            'main_library_areas' => [
                '/legacy-project/include/' => '',
                '/Zend' => 'Zend',
                '/legacy_api/module/mylegacyapp/src/mylegacyapp' => 'mylegacyapp'
            ],
            'change_dirnames_n_filenames_in_dirs' => [
                '/legacy-project/include/',
                '/Zend',
            ],
            'to_be_moved' => [
                'from' => [
                    '/legacy-project'
                ],
                'to' => '/legacy-project/include'
            ],
            'change_lib_dir' => [
                [
                    'from' => '/legacy-project/include',
                    'to' => '/LegacyLib'
                ]
            ],
            'ignore' => [
                'files' => [
                    '/legacy-project/wiredTools/myWatchDog/CopyOfwatchDogViewController.php',
                    '/legacy-project/wiredTools/mymissions/CopyOfmyMissionsView.php',
                    '/legacy-project/include/Legacy/survey/analyzer/CopyOfmain.php',
                    '/legacy-project/include/Legacy/survey/analyzer/CopyOfanswerview.php',
                    '/legacy-project/sedcard/images/sedcardImageViewController_old.php',
                    '/legacy_api/module/Application/src/Application/Controller/IndexController.php',
                    '/legacy_api/configs/staging/IndexController.php',
                    '/legacy_api/configs/production/IndexController.php',
                    '/legacy_api/module/mylegacyapp/src/mylegacyapp/Module.php',
                    '/legacy_api/module/Application/Module.php',
                ],
                'dirs' => [
                    '/legacy-project/include/TCPDF/examples',
                    '/legacy-project/include/excel/PHPExcel/Shared/JAMA/examples',
                ]
            ],
            // manuel fixed
            'class_to' => [
            ],
            'change_file_contents' => [
                'Legacy_System.php' => [
                    'file_path' => '/LegacyLib/Legacy_System.php',
                    'content' => "<?php
require __DIR__ . '/../vendor/autoload.php';"
                ],
            ],
            'change_includes_to' => [
                "Legacy_System.php" => '/LegacyLib/Legacy_System.php'
            ],
            'remove_unnecessary_files_folders' => [
                '/LegacyLib/vendor',
                '/LegacyLib/composer.json',
                '/LegacyLib/config.php',
                '/LegacyLib/charts.swf',
                '/LegacyLib/Legacy/main.php',
                '/LegacyLib/Legacy/locale.php',
                '/LegacyLib/charts_library'
            ],
            'copy_necessary_files_folders' => [
                '/legacy-project/include/Legacy/UI/action.php' => '/legacy-project/include/UI/action.php',
                '/legacy-project/include/Legacy/main.php' => '/legacy-project/include/Legacy/main.php',
            ],
            'clear_class_includes_requires' => [
                'active' => true,
                'dirs' => [
                    '/legacy-project',
                    '/Zend',
                ]
            ],
            're_create_paths' => [
                '/legacy-project',
                '/Zend',
            ],
            'backup' => true,
        ];
        AutoloadConverterBuilder::getInstance($settings)->convertDirectory();
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MigrationController.php',
        ]);
    }
}
