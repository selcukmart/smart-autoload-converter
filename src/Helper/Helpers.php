<?php

namespace App\Helper;


/**
 * @author Selcuk Mart
 * 16.08.2022
 * 09:55
 */
function c(): void
{
    foreach (func_get_args() as $param) {
        (new GeneralHelpers($param))->customDump();
    }
}

function remove_dir_recursive(string $from_folder): void
{
    if (!is_dir($from_folder)) {
        return;
    }
    foreach (array_diff(scandir($from_folder, SCANDIR_SORT_NONE), ['..', '.']) as $file) {
        if (is_dir($from_folder . '/' . $file)) {
            remove_dir_recursive($from_folder . '/' . $file);
            if (is_dir($from_folder . '/' . $file)) {
                rmdir($from_folder . '/' . $file);
            }
        } else {
            unlink($from_folder . '/' . $file);
        }
    }
    rmdir($from_folder);
}

function namespaceFixFromClassnameString(string $classname): string
{
    return implode('\\', namespaceFix(explode('\\', $classname)));
}

function namespaceFix($namespace_arr)
{
    $replace = [
        'Abstract' => 'Abstracts',
        'Interface' => 'Interfaces',
        'Trait' => 'Traits',
        'Php' => 'PhpCodes',
        'Class' => 'Classes',
    ];
    foreach ($namespace_arr as $key => $value) {
        if (isset($replace[$value])) {
            $namespace_arr[$key] = $replace[$value];
        }
    }
    return $namespace_arr;

}