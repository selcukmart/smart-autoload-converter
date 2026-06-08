<?php
/**
 * @author Selcuk Mart
 * 6.09.2022
 * 14:28
 */

namespace App\Converter\Directions\HelperTraits\SearchAndReplace;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\ClassOperations\ChangeClassnameAndAddNamespaceInContent;
use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

trait ChangeInFilesTrait
{
    private array $regex = [
        'NEW' => [
            'replace' => 'new {{PSR4}}',
            'replace_with_slash' => 'new \{{PSR4}}',
            'regex' => RegexDefinitions::NEW_REGEX4REPLACE,
            'str' => 'new {{CLASS}}',
            'str_with_slash' => 'new \{{CLASS}}',
        ],
        'COLON' => [
            'replace' => '{{PSR4}}::',
            'replace_with_slash' => '\{{PSR4}}::',
            'regex' => RegexDefinitions::COLON_REGEX4REPLACE,
            'str' => '{{CLASS}}::',
            'str_with_slash' => '\{{CLASS}}::',
        ],
        'EXTENDS' => [
            'replace' => 'extends {{PSR4}}',
            'replace_with_slash' => 'extends \{{PSR4}}',
            'regex' => RegexDefinitions::EXTENDS_REGEX4REPLACE,
            'str' => 'extends {{CLASS}}',
            'str_with_slash' => 'extends \{{CLASS}}',
        ],
        'EXTENDS_WITH_BRACE' => [
            'replace' => "extends {{PSR4}}\n{",
            'replace_with_slash' => "extends \{{PSR4}}\n{",
            'regex' => RegexDefinitions::EXTENDS_WITH_BRACE_REGEX4REPLACE,
            'str' => "extends {{CLASS}}{",
            'str_with_slash' => "extends \{{CLASS}}{",
        ],
        'INSTANCEOF' => [
            'replace' => 'instanceof {{PSR4}}',
            'replace_with_slash' => 'instanceof \{{PSR4}}',
            'regex' => RegexDefinitions::INSTANCEOF_REGEX4REPLACE,
            'str' => 'instanceof {{CLASS}}',
            'str_with_slash' => 'instanceof \{{CLASS}}',
        ],
        'CATCH_EXCEPTION' => [
            'replace' => '} catch ({{PSR4}}',
            'replace_with_slash' => '} catch (\{{PSR4}}',
            'regex' => RegexDefinitions::CATCH_REGEX4REPLACE,
            'str' => '} catch ({{CLASS}}',
            'str_with_slash' => '} catch (\{{CLASS}}',
        ],
        'EXCEPTION_IN_CATCH' => [
            'replace' => 'catch (\Exception',
            'replace_with_slash' => 'catch (\Exception',
            'regex' => RegexDefinitions::EXCEPTION_IN_CATCH_REGEX,
            'str' => 'catch (Exception',
            'str_with_slash' => 'catch (Exception',
        ],
        'IMPLEMENTS' => [],
        'CLASS_IN_TYPE_DEFINITION' => [
            'replace' => '{{PSR4}}',
            'replace_with_slash' => '\{{PSR4}}',
            'regex' => RegexDefinitions::CLASS_NAME_REGEX4REPLACE,
            'str' => '{{CLASS}}',
            'str_with_slash' => '\{{CLASS}}',
        ],
        'CLASS_IN_METHOD' => [
            'replace' => '{{PSR4}}',
            'replace_with_slash' => '\{{PSR4}}',
            'regex' => RegexDefinitions::CLASS_NAME_REGEX4REPLACE,
            'str' => '{{CLASS}}',
            'str_with_slash' => '\{{CLASS}}',
        ],
    ];

    private function changeOverClassLists(): void
    {
        $this->list = AutoloadConverterBuilder::getInstance()->getClassListsIngredientsFileDirs();
        foreach ($this->list as $current_class_name => $items) {
            $class_information = self::detectClass($current_class_name);
            if (!$class_information) {
                continue;
            }
            $determined_class_name = $class_information['name'];
            $psr4_class_name = $class_information['psr4'];
            foreach ($items as $regex_slug => $files) {
                if ($regex_slug === 'IMPLEMENTS') {
                    foreach ($files as $this->file) {
                        $content = $this->getContent($this->file);
                        if ($this->hasUse($psr4_class_name, $content)) {
                            continue;
                        }
                        preg_match_all(RegexDefinitions::IMPLEMENTS_REGEX, $content, $matches);
                        if (!empty($matches[0][0])) {
                            $replace_with_slash = false;
                            foreach (AutoloadConverterBuilder::getInstance()->getReCreatePaths() as $re_create_path) {
                                if (preg_match('@^' . $re_create_path . '@', $this->file)) {
                                    $replace_with_slash = true;
                                    break;
                                }
                            }
                            $back_slashed_psr4 = $class_information['psr4'];
                            if ($replace_with_slash) {
                                $back_slashed_psr4 = '\\' . $back_slashed_psr4;
                            }
                            $new_interface_name_str = str_replace($current_class_name, $back_slashed_psr4, $matches[0][0]);
                            $content = str_replace($matches[0][0], $new_interface_name_str, $content);
                            $this->setContentToFile($this->file, $content);
                        }
                    }
                } else {
                    foreach ($files as $this->file) {
                        [$find, $replace] = $this->strReplace($determined_class_name, $this->regex[$regex_slug], $psr4_class_name);
                        $content = $this->getContent($this->file);
                        if ($this->hasUse($psr4_class_name, $content)) {
                            continue;
                        }

                        [$matches, $content] = $this->hideClassname($content);
                        $content = $this->strReplaceInContent($find, $replace, $content);

                        $content = $this->showClassname($matches, $content);
                        $content = ChangeClassnameAndAddNamespaceInContent::getInstance($this->file, $content)->changeClassName();
//                        if(str_contains($replace[0], '\Admindesk\AdminDesk::')) {
//                            c($content);
//                            exit;
//                        }
                        $this->setContentToFile($this->file, $content);
                    }
                }
            }
        }
    }
}